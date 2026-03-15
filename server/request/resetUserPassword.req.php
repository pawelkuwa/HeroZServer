<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Srv\Config;
use Srv\Mail;

class resetUserPassword {

    public function __request($player) {
        $email = trim(getField('email') ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Look up user
        $stmt = DB::$connection->prepare("SELECT id, email, locale FROM user WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return;
        }

        // Cooldown: 60s between requests for the same user
        $stmt = DB::$connection->prepare(
            "SELECT MAX(ts_created) FROM password_reset_tokens WHERE user_id = ?"
        );
        $stmt->execute([$user['id']]);
        $lastRequest = (int) $stmt->fetchColumn();

        if ($lastRequest > 0 && (time() - $lastRequest) < 60) {
            return;
        }

        // Rate limit: max 20 requests per email per hour
        $oneHourAgo = time() - 3600;
        $stmt = DB::$connection->prepare(
            "SELECT COUNT(*) FROM password_reset_tokens WHERE user_id = ? AND ts_created > ?"
        );
        $stmt->execute([$user['id'], $oneHourAgo]);
        $recentRequests = (int) $stmt->fetchColumn();

        if ($recentRequests >= 20) {
            return;
        }

        // Clean up old tokens (used or expired)
        $stmt = DB::$connection->prepare(
            "DELETE FROM password_reset_tokens WHERE user_id = ? AND (used = 1 OR ts_expires < ?)"
        );
        $stmt->execute([$user['id'], time()]);

        // Generate token
        $token = bin2hex(random_bytes(32));
        $now = time();
        $ttl = (int) (Config::get('email.reset_token_ttl') ?: 3600);
        $expires = $now + $ttl;

        // Store token
        $stmt = DB::$connection->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, ts_created, ts_expires, used) VALUES (?, ?, ?, ?, 0)"
        );
        $stmt->execute([$user['id'], $token, $now, $expires]);

        // Build reset link
        $resetUrl = Config::get('email.reset_url') ?: 'http://localhost/pages/reset-password.php';
        $resetLink = $resetUrl . '?resetpassword=' . $user['id'] . '&code=' . $token;

        // Queue email with link
        $ttlMinutes = (int) ($ttl / 60);
        Mail::queue(
            (int) $user['id'],
            $user['email'],
            'Password Reset — HeroZero',
            'password-reset',
            [
                'reset_link' => $resetLink,
                'ttl_minutes' => $ttlMinutes,
                'user_id' => $user['id'],
                'locale' => $_COOKIE['web-lang'] ?? $user['locale'],
            ],
            1
        );
    }
}
