<?php
namespace Admin;

use Srv\DB;

class GuildsController
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
            $where = "WHERE g.name LIKE ? OR g.id = ?";
            $params = ["%{$search}%", $search];
        }

        $total = db_value("SELECT COUNT(*) FROM guild g {$where}", $params);
        $guilds = db_query("SELECT g.* FROM guild g {$where} ORDER BY g.id DESC LIMIT {$this->perPage} OFFSET {$offset}", $params);
        $totalPages = max(1, ceil($total / $this->perPage));

        foreach ($guilds as &$g) {
            $leader = db_query("SELECT name FROM `character` WHERE id = " . intval($g['leader_character_id']) . " LIMIT 1");
            $g['leader_name'] = $leader[0]['name'] ?? 'N/A';
            $g['member_count'] = db_value("SELECT COUNT(*) FROM `character` WHERE guild_id = " . intval($g['id']));
        }

        return [
            'viewFile'   => ADMIN_DIR . '/views/guilds/list.php',
            'guilds'     => $guilds,
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
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid guild ID.'];
            header('Location: index.php?page=guilds');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_guild'])) {
            if (!csrf_verify()) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'CSRF token invalid.'];
            } else {
                $update = [
                    'name'              => trim($_POST['name'] ?? ''),
                    'description'       => trim($_POST['description'] ?? ''),
                    'note'              => trim($_POST['note'] ?? ''),
                    'status'            => intval($_POST['status'] ?? 1),
                    'accept_members'    => intval($_POST['accept_members'] ?? 0),
                    'honor'             => max(0, intval($_POST['honor'] ?? 0)),
                    'game_currency'     => max(0, intval($_POST['game_currency'] ?? 0)),
                    'premium_currency'  => max(0, intval($_POST['premium_currency'] ?? 0)),
                    'missiles'          => max(0, intval($_POST['missiles'] ?? 0)),
                    'stat_guild_capacity' => max(1, intval($_POST['stat_guild_capacity'] ?? 10)),
                ];
                DB::table('guild')->update($update)->where('id', $id)->execute();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Guild updated successfully.'];
                header("Location: index.php?page=guilds&action=edit&id={$id}");
                exit;
            }
        }

        $rows = db_query("SELECT * FROM guild WHERE id = {$id} LIMIT 1");
        $guild = $rows[0] ?? null;
        if (!$guild) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Guild not found.'];
            header('Location: index.php?page=guilds');
            exit;
        }

        $members = db_query("SELECT c.*, u.email as user_email FROM `character` c LEFT JOIN user u ON u.id = c.user_id WHERE c.guild_id = {$id} ORDER BY c.guild_rank ASC");
        $leader = db_query("SELECT name FROM `character` WHERE id = " . intval($guild['leader_character_id']) . " LIMIT 1");
        $guild['leader_name'] = $leader[0]['name'] ?? 'N/A';

        return [
            'viewFile' => ADMIN_DIR . '/views/guilds/edit.php',
            'guild'    => $guild,
            'members'  => $members,
        ];
    }

    public function delete(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::sql("UPDATE `character` SET guild_id=0, guild_rank=0 WHERE guild_id={$id}");
            DB::table('guild')->delete()->where('id', $id)->execute();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Guild #{$id} deleted."];
        }
        header('Location: index.php?page=guilds');
        exit;
    }

    public function adjustCurrency(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            $field = $_POST['currency_type'] === 'premium' ? 'premium_currency' : 'game_currency';
            $amount = intval($_POST['amount'] ?? 0);
            $mode = $_POST['mode'] ?? 'add';

            $guild = DB::table('guild')->select([$field])->where('id', $id)->one();
            if ($guild) {
                $newVal = $mode === 'set' ? $amount : ($guild[$field] + $amount);
                $newVal = max(0, $newVal);
                DB::table('guild')->update([$field => $newVal])->where('id', $id)->execute();
                $_SESSION['flash'] = ['type' => 'success', 'message' => ucfirst($field) . " updated to {$newVal}."];
            }
        }
        header("Location: index.php?page=guilds&action=edit&id={$id}");
        exit;
    }
}
