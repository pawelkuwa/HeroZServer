<?php
namespace Admin;

use Srv\DB;
use Srv\Config;
use Srv\Mail;

class EmailController
{
    public function index(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $stats = Mail::getQueueStats();
        $recentSent = db_query("SELECT * FROM email_log ORDER BY ts_sent DESC LIMIT 10");
        $recentQueue = db_query("SELECT * FROM email_queue ORDER BY id DESC LIMIT 5");

        return [
            'viewFile' => ADMIN_DIR . '/views/email/dashboard.php',
            'stats'    => $stats,
            'recentSent'  => $recentSent,
            'recentQueue' => $recentQueue,
            'flash'    => $flash,
        ];
    }

    public function queue(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($status && in_array($status, ['pending', 'sending', 'sent', 'failed'])) {
            $where = "WHERE status = ?";
            $params = [$status];
        }

        $total = (int)db_value("SELECT COUNT(*) FROM email_queue {$where}", $params);
        $emails = db_query("SELECT * FROM email_queue {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}", $params);
        $totalPages = max(1, ceil($total / $perPage));

        return [
            'viewFile'   => ADMIN_DIR . '/views/email/queue.php',
            'emails'     => $emails,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'status'     => $status,
            'flash'      => $flash,
        ];
    }

    public function log(): array
    {
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search) {
            $where = "WHERE to_email LIKE ? OR subject LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $like];
        }

        $total = (int)db_value("SELECT COUNT(*) FROM email_log {$where}", $params);
        $logs = db_query("SELECT * FROM email_log {$where} ORDER BY ts_sent DESC LIMIT {$perPage} OFFSET {$offset}", $params);
        $totalPages = max(1, ceil($total / $perPage));

        return [
            'viewFile'   => ADMIN_DIR . '/views/email/log.php',
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'search'     => $search,
        ];
    }

    public function settings(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $emailConfig = [];
        try {
            $emailConfig = [
                'driver'     => Config::get('email.driver'),
                'smtp_host'  => Config::get('email.smtp_host'),
                'smtp_port'  => Config::get('email.smtp_port'),
                'smtp_encryption' => Config::get('email.smtp_encryption'),
                'smtp_auth'  => Config::get('email.smtp_auth'),
                'smtp_username' => Config::get('email.smtp_username'),
                'from_email' => Config::get('email.from_email'),
                'from_name'  => Config::get('email.from_name'),
                'queue_batch_size' => Config::get('email.queue_batch_size'),
                'max_attempts' => Config::get('email.max_attempts'),
            ];
        } catch (\Exception $e) {}

        return [
            'viewFile'    => ADMIN_DIR . '/views/email/settings.php',
            'emailConfig' => $emailConfig,
            'flash'       => $flash,
        ];
    }

    public function broadcast(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $totalUsers = (int)db_value("SELECT COUNT(*) FROM user WHERE ts_banned = 0");

        return [
            'viewFile'   => ADMIN_DIR . '/views/email/broadcast.php',
            'totalUsers' => $totalUsers,
            'flash'      => $flash,
        ];
    }

    public function sendTest(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=email&action=settings');
            exit;
        }

        $to = trim($_POST['test_email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid email address.'];
            header('Location: index.php?page=email&action=settings');
            exit;
        }

        $ok = Mail::send($to, 'Test Email - HeroZero Server', '', '', '');

        // If direct send fails, try via queue render
        if (!$ok) {
            $rendered = Mail::renderTemplate('test', [
                'subject' => 'Test Email - HeroZero Server',
                'smtp_host' => Config::get('email.smtp_host'),
                'smtp_port' => Config::get('email.smtp_port'),
                'from_email' => Config::get('email.from_email'),
            ]);
            if ($rendered) {
                $ok = Mail::send($to, 'Test Email - HeroZero Server', $rendered['html'], $rendered['text']);
            }
        }

        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => "Test email sent to {$to}!"]
            : ['type' => 'danger', 'message' => 'Failed to send test email. Check SMTP settings and ensure MailPit is running.'];

        header('Location: index.php?page=email&action=settings');
        exit;
    }

    public function processQueue(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=email');
            exit;
        }

        $result = Mail::processQueue();

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => "Queue processed: {$result['sent']} sent, {$result['failed']} failed, {$result['remaining']} remaining."
        ];

        header('Location: index.php?page=email');
        exit;
    }

    public function sendBroadcast(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=email&action=broadcast');
            exit;
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Subject and message are required.'];
            header('Location: index.php?page=email&action=broadcast');
            exit;
        }

        // Get all active users with email notifications enabled
        $users = db_query("SELECT id, email, locale FROM user WHERE ts_banned = 0 AND email_notifications = 1");
        $queued = 0;

        foreach ($users as $user) {
            Mail::queue(
                (int)$user['id'],
                $user['email'],
                $subject,
                'admin-broadcast',
                ['message' => $message, 'subject' => $subject, 'user_id' => $user['id'], 'locale' => $user['locale']],
                3 // lower priority = higher importance
            );
            $queued++;
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Broadcast queued for {$queued} users. Use 'Process Queue' to send."];
        header('Location: index.php?page=email');
        exit;
    }

    public function clearQueue(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=email&action=queue');
            exit;
        }

        DB::sql("DELETE FROM email_queue WHERE status IN ('pending', 'failed')");
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pending/failed queue entries cleared.'];
        header('Location: index.php?page=email&action=queue');
        exit;
    }

    public function deleteOldLogs(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
            header('Location: index.php?page=email&action=log');
            exit;
        }

        $cutoff = time() - (30 * 86400); // 30 days
        DB::sql("DELETE FROM email_log WHERE ts_sent < {$cutoff}");
        DB::sql("DELETE FROM email_queue WHERE status = 'sent' AND ts_sent < {$cutoff}");

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Logs older than 30 days deleted.'];
        header('Location: index.php?page=email&action=log');
        exit;
    }
}
