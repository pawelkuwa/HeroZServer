<?php
namespace Admin;

use Srv\DB;

class CharactersController
{
    private int $perPage = 20;

    public function index(): array
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $offset = ($page - 1) * $this->perPage;
        $search = trim($_GET['search'] ?? '');

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE c.name LIKE ? OR c.id = ?";
            $params = ["%{$search}%", $search];
        }

        $total = db_value("SELECT COUNT(*) FROM `character` c {$where}", $params);
        $characters = db_query("SELECT c.*, u.email as user_email FROM `character` c LEFT JOIN user u ON u.id = c.user_id {$where} ORDER BY c.id DESC LIMIT {$this->perPage} OFFSET {$offset}", $params);
        $totalPages = max(1, ceil($total / $this->perPage));

        return [
            'viewFile'   => ADMIN_DIR . '/views/characters/list.php',
            'characters' => $characters,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'search'     => $search,
        ];
    }

    public function edit(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid character ID.'];
            header('Location: index.php?page=characters');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_character'])) {
            if (!csrf_verify()) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'CSRF token invalid.'];
            } else {
                $fields = [
                    'name', 'gender', 'level', 'xp', 'description', 'game_currency', 'honor',
                    'stat_points_available', 'stat_base_stamina', 'stat_base_strength',
                    'stat_base_critical_rating', 'stat_base_dodge_rating',
                    'stat_bought_stamina', 'stat_bought_strength',
                    'stat_bought_critical_rating', 'stat_bought_dodge_rating',
                    'stat_trained_stamina', 'stat_trained_strength',
                    'stat_trained_critical_rating', 'stat_trained_dodge_rating',
                    'quest_energy', 'max_quest_energy', 'duel_stamina', 'max_duel_stamina',
                    'training_count', 'max_training_count', 'league_stamina', 'max_league_stamina',
                    'appearance_skin_color', 'appearance_hair_color', 'appearance_hair_type',
                    'appearance_head_type', 'appearance_eyes_type', 'appearance_eyebrows_type',
                    'appearance_nose_type', 'appearance_mouth_type', 'appearance_facial_hair_type',
                ];
                $sets = [];
                $params = [];
                foreach ($fields as $f) {
                    if (isset($_POST[$f])) {
                        $sets[] = "`{$f}` = ?";
                        $params[] = trim($_POST[$f]);
                    }
                }
                if (!empty($sets)) {
                    $params[] = $id;
                    DB::sql("UPDATE `character` SET " . implode(', ', $sets) . " WHERE id = ?", $params);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Character updated successfully.'];
                }
                header("Location: index.php?page=characters&action=edit&id={$id}");
                exit;
            }
        }

        $rows = db_query("SELECT c.*, u.email as user_email FROM `character` c LEFT JOIN user u ON u.id = c.user_id WHERE c.id = {$id} LIMIT 1");
        $character = $rows[0] ?? null;

        if (!$character) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Character not found.'];
            header('Location: index.php?page=characters');
            exit;
        }

        $guild = null;
        if (!empty($character['guild_id'])) {
            $g = db_query("SELECT id, name FROM guild WHERE id = " . intval($character['guild_id']));
            $guild = $g[0] ?? null;
        }

        $items = db_query("SELECT * FROM items WHERE character_id = {$id}");

        $inv = db_query("SELECT * FROM inventory WHERE character_id = {$id}");
        $inv = $inv[0] ?? [];
        $bank = db_query("SELECT * FROM bank_inventory WHERE character_id = {$id}");
        $bank = $bank[0] ?? [];

        $equippedIds = [];
        foreach (['mask','cape','suit','belt','boots','weapon','gadget','missiles','missiles1','missiles2','missiles3','missiles4','sidekick'] as $slot) {
            $key = $slot === 'sidekick' ? 'sidekick_id' : "{$slot}_item_id";
            $v = intval($inv[$key] ?? 0);
            if ($v > 0) $equippedIds[$v] = $slot;
        }

        $bagIds = [];
        for ($i = 1; $i <= 18; $i++) {
            $v = intval($inv["bag_item{$i}_id"] ?? 0);
            if ($v > 0) $bagIds[$v] = true;
        }

        $shopIds = [];
        for ($i = 1; $i <= 9; $i++) {
            $v = intval($inv["shop_item{$i}_id"] ?? 0);
            if ($v > 0) $shopIds[$v] = true;
            $v2 = intval($inv["shop2_item{$i}_id"] ?? 0);
            if ($v2 > 0) $shopIds[$v2] = true;
        }

        $bankIds = [];
        for ($i = 1; $i <= 90; $i++) {
            $v = intval($bank["bank_item{$i}_id"] ?? 0);
            if ($v > 0) $bankIds[$v] = true;
        }

        $equipped = $bagItems = $shopItems = $bankItems = [];
        foreach ($items as $item) {
            $iid = intval($item['id']);
            if (isset($equippedIds[$iid])) {
                $item['_slot'] = $equippedIds[$iid];
                $equipped[] = $item;
            } elseif (isset($bagIds[$iid])) {
                $bagItems[] = $item;
            } elseif (isset($shopIds[$iid])) {
                $shopItems[] = $item;
            } elseif (isset($bankIds[$iid])) {
                $bankItems[] = $item;
            } else {
                $shopItems[] = $item;
            }
        }

        $gs = \Cls\GameSettings::getConstant('item_templates', []);
        $itemTemplates = [];
        if (is_array($gs)) {
            foreach ($gs as $ident => $data) {
                $t = intval($data['type'] ?? 0);
                if ($t >= 1 && $t <= 8) {
                    $itemTemplates[] = [
                        'identifier'    => $ident,
                        'type'          => $t,
                        'quality'       => intval($data['quality'] ?? 1),
                        'required_level'=> intval($data['required_level'] ?? 1),
                    ];
                }
            }
        }

        return [
            'viewFile'      => ADMIN_DIR . '/views/characters/edit.php',
            'character'     => $character,
            'guild'         => $guild,
            'equipped'      => $equipped,
            'bagItems'      => $bagItems,
            'shopItems'     => $shopItems,
            'bankItems'     => $bankItems,
            'itemTemplates' => $itemTemplates,
        ];
    }

    public function maxStats(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::sql("UPDATE `character` SET stat_base_stamina=9999, stat_base_strength=9999, stat_base_critical_rating=9999, stat_base_dodge_rating=9999, stat_points_available=0 WHERE id={$id}");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'All base stats maxed to 9999.'];
        }
        header("Location: index.php?page=characters&action=edit&id={$id}");
        exit;
    }

    public function maxCurrency(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::sql("UPDATE `character` SET game_currency=999999999 WHERE id={$id}");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Game currency maxed.'];
        }
        header("Location: index.php?page=characters&action=edit&id={$id}");
        exit;
    }

    public function maxLevel(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::sql("UPDATE `character` SET level=999, xp=49850100 WHERE id={$id}");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Level maxed to 999.'];
        }
        header("Location: index.php?page=characters&action=edit&id={$id}");
        exit;
    }

    public function giveItem(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: index.php?page=characters');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['give_item']) && csrf_verify()) {
            $identifier = trim($_POST['identifier'] ?? '');
            if (empty($identifier)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Select an item to give.'];
                header("Location: index.php?page=characters&action=edit&id={$id}");
                exit;
            }

            $templates = \Cls\GameSettings::getConstant('item_templates', []);
            if (!isset($templates[$identifier])) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid item identifier.'];
                header("Location: index.php?page=characters&action=edit&id={$id}");
                exit;
            }

            $charExists = db_value("SELECT COUNT(*) FROM `character` WHERE id = ?", [$id]);
            if (!$charExists) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Character not found.'];
                header("Location: index.php?page=characters&action=edit&id={$id}");
                exit;
            }

            $tpl = $templates[$identifier];
            $level = intval(db_value("SELECT level FROM `character` WHERE id = ?", [$id]));
            $type = intval($tpl['type']);
            $quality = intval($tpl['quality']);

            $itemLevel = max(1, round(mt_rand(70, 110) / 100 * $level));
            $statsPerLevel = floatval(\Srv\Config::get('constants.item_stats_per_level') ?? 3);
            $totalStats = $itemLevel * $statsPerLevel;
            $isPremium = mt_rand(1, 10) < 3;
            $allStats = ceil($totalStats) * $quality * ($isPremium ? 2 : 1);

            $stats = ['stat_stamina' => 0, 'stat_strength' => 0, 'stat_critical_rating' => 0, 'stat_dodge_rating' => 0];
            $statKeys = array_keys($stats);
            shuffle($statKeys);
            foreach ($statKeys as $st) {
                if (mt_rand(0, 1) === 0) continue;
                $val = ceil((mt_rand(1, 100) / 100) * $allStats);
                $stats[$st] = $val;
                $allStats = max($allStats - $val, 0);
            }
            $stats[$statKeys[mt_rand(0, 3)]] += $allStats;

            $weaponDamage = 0;
            if ($type === 6) {
                $weaponDamage = round($itemLevel * floatval(\Srv\Config::get('constants.item_weapon_damage_factor') ?? 1));
            }

            if ($isPremium) {
                $qualityName = ['1' => 'common', '2' => 'rare', '3' => 'epic'][$quality] ?? 'common';
                $buyPrice = intval(\Srv\Config::get("constants.item_buy_price_premium_{$qualityName}") ?? 100);
            } else {
                $buyPrice = ceil($totalStats * 1.65);
            }
            $sellPrice = $isPremium ? 0 : ceil($buyPrice / 2);

            $data = [
                'character_id'        => $id,
                'identifier'          => $identifier,
                'type'                => $type,
                'quality'             => $quality,
                'required_level'      => intval($tpl['required_level'] ?? 1),
                'item_level'          => $itemLevel,
                'premium_item'        => $isPremium ? 1 : 0,
                'buy_price'           => $buyPrice,
                'sell_price'          => $sellPrice,
                'stat_stamina'        => $stats['stat_stamina'],
                'stat_strength'       => $stats['stat_strength'],
                'stat_critical_rating'=> $stats['stat_critical_rating'],
                'stat_dodge_rating'   => $stats['stat_dodge_rating'],
                'stat_weapon_damage'  => $weaponDamage,
                'charges'             => ($type === 8) ? 100 : 0,
                'ts_availability_start' => 0,
                'ts_availability_end'   => 0,
            ];

            DB::table('items')->insert($data)->execute();
            $itemId = DB::lastInsertId();

            $destination = trim($_POST['destination'] ?? 'bag');
            $placed = false;

            if ($destination === 'bank') {
                $bankInv = DB::table('bank_inventory')->select()->where('character_id', $id)->one();
                if ($bankInv) {
                    for ($i = 1; $i <= 90; $i++) {
                        $slot = "bank_item{$i}_id";
                        if (empty($bankInv[$slot]) || intval($bankInv[$slot]) <= 0) {
                            DB::table('bank_inventory')->update([$slot => $itemId])->where('character_id', $id)->execute();
                            $placed = true;
                            break;
                        }
                    }
                }
            } else {
                $inv = DB::table('inventory')->select()->where('character_id', $id)->one();
                if ($inv) {
                    for ($i = 1; $i <= 18; $i++) {
                        $slot = "bag_item{$i}_id";
                        if (empty($inv[$slot]) || intval($inv[$slot]) <= 0) {
                            DB::table('inventory')->update([$slot => $itemId])->where('character_id', $id)->execute();
                            $placed = true;
                            break;
                        }
                    }
                }
            }

            $destLabel = $destination === 'bank' ? 'bank' : 'bag';
            $msg = "Item '{$identifier}' (#{$itemId}, Lv.{$itemLevel}) given to {$destLabel}.";
            if (!$placed) $msg .= " Warning: no empty {$destLabel} slot found.";
            $_SESSION['flash'] = ['type' => 'success', 'message' => $msg];
            header("Location: index.php?page=characters&action=edit&id={$id}");
            exit;
        }

        header("Location: index.php?page=characters&action=edit&id={$id}");
        exit;
    }
}
