<?php
namespace Admin;

use Srv\DB;
use Srv\Config;
use Srv\Mail;

class UsersController
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
            $where = "WHERE email LIKE ? OR id = ? OR last_login_ip LIKE ? OR registration_ip LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $search, $like, $like];
        }

        $total = db_value("SELECT COUNT(*) FROM user {$where}", $params);
        $users = db_query("SELECT * FROM user {$where} ORDER BY id DESC LIMIT {$this->perPage} OFFSET {$offset}", $params);
        $totalPages = max(1, ceil($total / $this->perPage));

        return [
            'viewFile'   => ADMIN_DIR . '/views/users/list.php',
            'users'      => $users,
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
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid user ID.'];
            header('Location: index.php?page=users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
            if (!csrf_verify()) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'CSRF token invalid.'];
            } else {
                $update = [
                    'email'            => trim($_POST['email'] ?? ''),
                    'premium_currency' => intval($_POST['premium_currency'] ?? 0),
                    'locale'           => trim($_POST['locale'] ?? 'en_US'),
                    'trusted'          => intval($_POST['trusted'] ?? 0),
                ];
                DB::table('user')->update($update)->where('id', $id)->execute();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
                header("Location: index.php?page=users&action=edit&id={$id}");
                exit;
            }
        }

        $user = DB::table('user')->select()->where('id', $id)->one();
        if (!$user) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'User not found.'];
            header('Location: index.php?page=users');
            exit;
        }

        $characters = db_query("SELECT * FROM `character` WHERE user_id = {$id}");

        return [
            'viewFile'   => ADMIN_DIR . '/views/users/edit.php',
            'user'       => $user,
            'characters' => $characters,
        ];
    }

    public function ban(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::table('user')->update(['ts_banned' => time() + 999999999])->where('id', $id)->execute();
            if(Config::get('email.notify_ban')){
                $u = db_query("SELECT email, email_notifications, locale FROM user WHERE id = {$id}");
                if(!empty($u) && $u[0]['email_notifications'])
                    Mail::queue($id, $u[0]['email'], 'Account Suspended — HeroZero', 'ban-notice', ['user_id'=>$id, 'locale'=>$u[0]['locale']]);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => "User #{$id} has been banned."];
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=users'));
        exit;
    }

    public function unban(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            DB::table('user')->update(['ts_banned' => 0])->where('id', $id)->execute();
            if(Config::get('email.notify_unban')){
                $u = db_query("SELECT email, email_notifications, locale FROM user WHERE id = {$id}");
                if(!empty($u) && $u[0]['email_notifications'])
                    Mail::queue($id, $u[0]['email'], 'Account Restored — HeroZero', 'unban-notice', ['user_id'=>$id, 'locale'=>$u[0]['locale']]);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => "User #{$id} has been unbanned."];
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=users'));
        exit;
    }

    public function delete(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            $chars = db_query("SELECT id FROM `character` WHERE user_id = {$id}");
            foreach ($chars as $c) {
                DB::table('items')->delete()->where('character_id', $c['id'])->execute();
                DB::table('inventory')->delete()->where('character_id', $c['id'])->execute();
            }
            DB::sql("DELETE FROM `character` WHERE user_id = {$id}");
            DB::table('user')->delete()->where('id', $id)->execute();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "User #{$id} and all associated data deleted."];
        }
        header('Location: index.php?page=users');
        exit;
    }

    public function resetPassword(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            $pass = trim($_POST['new_password'] ?? '');
            if (strlen($pass) < 4) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Password must be at least 4 characters.'];
            } else {
                $hash = sha1('q!*1IYn1eZr#A?#FGlqkt' . md5($pass) . $pass);
                DB::table('user')->update(['password_hash' => $hash])->where('id', $id)->execute();
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Password reset for user #{$id}."];
            }
        }
        header("Location: index.php?page=users&action=edit&id={$id}");
        exit;
    }

    public function adjustCurrency(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            $amount = intval($_POST['amount'] ?? 0);
            $mode = $_POST['mode'] ?? 'add';
            $user = DB::table('user')->select(['premium_currency'])->where('id', $id)->one();
            if ($user) {
                $newVal = $mode === 'set' ? $amount : ($user['premium_currency'] + $amount);
                $newVal = max(0, $newVal);
                DB::table('user')->update(['premium_currency' => $newVal])->where('id', $id)->execute();
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Premium currency updated to {$newVal}."];
            }
        }
        header("Location: index.php?page=users&action=edit&id={$id}");
        exit;
    }
}
