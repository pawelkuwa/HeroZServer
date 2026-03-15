<?php
namespace Admin;

use Srv\DB;

class VouchersController
{
    public function index(): array
    {
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';

        $sql = "SELECT v.*, (SELECT COUNT(*) FROM voucher_redemptions vr WHERE vr.voucher_id = v.id) as redemption_count FROM vouchers v WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND v.code LIKE ?";
            $params[] = "%{$search}%";
        }
        if ($status !== '') {
            $sql .= " AND v.status = ?";
            $params[] = (int)$status;
        }

        $sql .= " ORDER BY v.id DESC LIMIT 100";
        $vouchers = DB::sql($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $stats = [
            'total' => db_value("SELECT COUNT(*) FROM vouchers"),
            'active' => db_value("SELECT COUNT(*) FROM vouchers WHERE status = 1"),
            'total_redemptions' => db_value("SELECT COUNT(*) FROM voucher_redemptions"),
        ];

        return [
            'viewFile' => ADMIN_DIR . '/views/vouchers/index.php',
            'vouchers' => $vouchers,
            'stats' => $stats,
            'search' => $search,
            'statusFilter' => $status,
        ];
    }

    public function create(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $rewards = [];

            if (!empty($_POST['game_currency'])) $rewards['game_currency'] = (int)$_POST['game_currency'];
            if (!empty($_POST['premium_currency'])) $rewards['premium_currency'] = (int)$_POST['premium_currency'];
            if (!empty($_POST['stat_points'])) $rewards['stat_points'] = (int)$_POST['stat_points'];
            if (!empty($_POST['quest_energy'])) $rewards['quest_energy'] = (int)$_POST['quest_energy'];
            if (!empty($_POST['training_sessions'])) $rewards['training_sessions'] = (int)$_POST['training_sessions'];

            if (empty($code)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Code is required.'];
                header('Location: index.php?page=vouchers&action=create');
                exit;
            }

            $existing = db_value("SELECT COUNT(*) FROM vouchers WHERE code = '" . addslashes($code) . "'");
            if ($existing > 0) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Code already exists.'];
                header('Location: index.php?page=vouchers&action=create');
                exit;
            }

            $usesMax = (int)($_POST['uses_max'] ?? 1);
            $minLevel = (int)($_POST['min_level'] ?? 0);
            $locale = trim($_POST['locale'] ?? '');
            $userId = (int)($_POST['user_id'] ?? 0);
            $tsStart = !empty($_POST['ts_start']) ? strtotime($_POST['ts_start']) : 0;
            $tsEnd = !empty($_POST['ts_end']) ? strtotime($_POST['ts_end']) : 0;

            DB::sql("INSERT INTO vouchers (code, rewards, uses_max, min_level, locale, user_id, ts_start, ts_end, ts_creation, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$code, json_encode($rewards), $usesMax, $minLevel, $locale, $userId, $tsStart, $tsEnd, time()]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => "Voucher {$code} created."];
            header('Location: index.php?page=vouchers');
            exit;
        }

        return [
            'viewFile' => ADMIN_DIR . '/views/vouchers/create.php',
        ];
    }

    public function view(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        $voucher = DB::sql("SELECT * FROM vouchers WHERE id = ?", [$id])->fetch(\PDO::FETCH_ASSOC);
        if (!$voucher) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Voucher not found.'];
            header('Location: index.php?page=vouchers');
            exit;
        }

        $redemptions = DB::sql("SELECT vr.*, u.email, c.name as character_name FROM voucher_redemptions vr LEFT JOIN user u ON u.id = vr.user_id LEFT JOIN `character` c ON c.id = vr.character_id WHERE vr.voucher_id = ? ORDER BY vr.ts_redeemed DESC", [$id])->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'viewFile' => ADMIN_DIR . '/views/vouchers/view.php',
            'voucher' => $voucher,
            'redemptions' => $redemptions,
        ];
    }

    public function toggle(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=vouchers');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $voucher = DB::sql("SELECT id, status, code FROM vouchers WHERE id = ?", [$id])->fetch(\PDO::FETCH_ASSOC);
        if (!$voucher) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Voucher not found.'];
            header('Location: index.php?page=vouchers');
            exit;
        }

        $newStatus = $voucher['status'] == 1 ? 0 : 1;
        DB::sql("UPDATE vouchers SET status = ? WHERE id = ?", [$newStatus, $id]);

        $label = $newStatus == 1 ? 'activated' : 'deactivated';
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Voucher {$voucher['code']} {$label}."];
        header('Location: index.php?page=vouchers');
        exit;
    }

    public function delete(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=vouchers');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        DB::sql("DELETE FROM voucher_redemptions WHERE voucher_id = ?", [$id]);
        DB::sql("DELETE FROM vouchers WHERE id = ?", [$id]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Voucher deleted.'];
        header('Location: index.php?page=vouchers');
        exit;
    }
}
