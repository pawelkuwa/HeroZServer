<?php
namespace Srv;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail {

    private static $mailer = null;

    /**
     * Create a configured PHPMailer instance
     */
    public static function createMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $driver = Config::get('email.driver') ?: 'smtp';

        if ($driver === 'smtp') {
            $mail->isSMTP();
            $mail->Host       = Config::get('email.smtp_host') ?: 'localhost';
            $mail->Port       = Config::get('email.smtp_port') ?: 1025;
            $mail->SMTPAuth   = (bool)Config::get('email.smtp_auth');
            $mail->Username   = Config::get('email.smtp_username') ?: '';
            $mail->Password   = Config::get('email.smtp_password') ?: '';
            $encryption = Config::get('email.smtp_encryption') ?: '';
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
        } else {
            $mail->isMail();
        }

        $mail->setFrom(
            Config::get('email.from_email') ?: 'noreply@heroz.local',
            Config::get('email.from_name') ?: 'HeroZero Server'
        );
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        return $mail;
    }

    /**
     * Send an email directly (for admin/CLI use)
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = '', string $toName = ''): bool {
        try {
            $mail = static::createMailer();
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Queue an email for async sending (safe to call inside game requests)
     */
    public static function queue(int $userId, string $toEmail, string $subject, string $template, array $data = [], int $priority = 5): bool {
        $rendered = static::renderTemplate($template, $data);
        if (!$rendered) return false;

        $now = time();
        $stmt = DB::$connection->prepare(
            "INSERT INTO email_queue (user_id, to_email, to_name, subject, body_html, body_text, template, priority, status, ts_created, ts_scheduled)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
        );
        $inserted = $stmt->execute([
            $userId,
            $toEmail,
            $data['to_name'] ?? '',
            $subject,
            $rendered['html'],
            $rendered['text'],
            $template,
            $priority,
            $now,
            $data['ts_scheduled'] ?? $now
        ]);

        if ($inserted) {
            $emailId = DB::$connection->lastInsertId();
            try {
                $mail = static::createMailer();
                $mail->addAddress($toEmail, $data['to_name'] ?? '');
                $mail->Subject = $subject;
                $mail->Body    = $rendered['html'];
                $mail->AltBody = $rendered['text'];
                $mail->send();

                DB::$connection->prepare(
                    "UPDATE email_queue SET status = 'sent', ts_sent = ?, attempts = 1 WHERE id = ?"
                )->execute([time(), $emailId]);
                static::log($userId, $toEmail, $subject, $template, 'sent');
            } catch (Exception $e) {
                // stays pending for retry via admin panel
            }
        }

        return $inserted;
    }

    /**
     * Process the email queue — sends pending emails in batch
     */
    public static function processQueue(int $batchSize = 0): array {
        if ($batchSize <= 0) {
            $batchSize = (int)(Config::get('email.queue_batch_size') ?: 20);
        }

        $now = time();
        $retryDelay = (int)(Config::get('email.queue_retry_delay') ?: 300);
        $results = ['sent' => 0, 'failed' => 0, 'remaining' => 0];

        // Fetch pending emails ready to send
        $batchSize = (int)$batchSize;
        $stmt = DB::$connection->prepare(
            "SELECT * FROM email_queue
             WHERE status IN ('pending', 'failed')
               AND ts_scheduled <= ?
               AND attempts < max_attempts
             ORDER BY priority ASC, id ASC
             LIMIT {$batchSize}"
        );
        $stmt->execute([$now]);
        $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($emails)) return $results;

        // Create mailer with keep-alive for batch
        try {
            $mail = static::createMailer();
            $mail->SMTPKeepAlive = true;
        } catch (Exception $e) {
            return $results;
        }

        foreach ($emails as $email) {
            // Mark as sending
            DB::$connection->prepare("UPDATE email_queue SET status = 'sending' WHERE id = ?")->execute([$email['id']]);

            try {
                $mail->clearAddresses();
                $mail->addAddress($email['to_email'], $email['to_name']);
                $mail->Subject = $email['subject'];
                $mail->Body    = $email['body_html'];
                $mail->AltBody = $email['body_text'];
                $mail->send();

                // Mark as sent
                DB::$connection->prepare(
                    "UPDATE email_queue SET status = 'sent', ts_sent = ?, attempts = attempts + 1 WHERE id = ?"
                )->execute([time(), $email['id']]);

                // Log success
                static::log($email['user_id'], $email['to_email'], $email['subject'], $email['template'], 'sent');
                $results['sent']++;

            } catch (Exception $e) {
                $attempts = $email['attempts'] + 1;
                $maxAttempts = $email['max_attempts'];
                $newStatus = ($attempts >= $maxAttempts) ? 'failed' : 'pending';
                $nextScheduled = $now + ($retryDelay * $attempts);

                DB::$connection->prepare(
                    "UPDATE email_queue SET status = ?, attempts = ?, last_error = ?, ts_scheduled = ? WHERE id = ?"
                )->execute([$newStatus, $attempts, $e->getMessage(), $nextScheduled, $email['id']]);

                if ($newStatus === 'failed') {
                    static::log($email['user_id'], $email['to_email'], $email['subject'], $email['template'], 'failed', $e->getMessage());
                }
                $results['failed']++;
            }
        }

        // Close SMTP connection
        try { $mail->smtpClose(); } catch (\Exception $e) {}

        // Count remaining
        $stmt = DB::$connection->prepare(
            "SELECT COUNT(*) FROM email_queue WHERE status IN ('pending', 'failed') AND attempts < max_attempts"
        );
        $stmt->execute();
        $results['remaining'] = (int)$stmt->fetchColumn();

        return $results;
    }

    /**
     * Render an email template
     */
    public static function renderTemplate(string $template, array $data = []): ?array {
        $templateDir = SERVER_DIR . '/email-templates';
        $file = $templateDir . '/' . $template . '.php';

        if (!file_exists($file)) return null;

        // Add common data
        $data['year'] = date('Y');
        $data['game_url'] = Config::get('site.public_url') ?: 'http://localhost/';
        $data['server_name'] = Config::get('site.server_name') ?: 'HeroZero';

        // Generate unsubscribe URL if user_id is present
        if (!empty($data['user_id'])) {
            $secret = Config::get('email.unsubscribe_secret') ?: 'default_secret';
            $sig = hash_hmac('sha256', (string)$data['user_id'], $secret);
            $data['unsubscribe_url'] = ($data['game_url']) . 'unsubscribe.php?uid=' . $data['user_id'] . '&sig=' . $sig;
        } else {
            $data['unsubscribe_url'] = '';
        }

        // Render template
        ob_start();
        extract($data, EXTR_SKIP);
        include $file;
        $html = ob_get_clean();

        // Generate plain text version
        $text = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>', '</tr>'],
            ["\n", "\n", "\n", "\n\n", "\n", "\n\n", "\n\n", "\n\n", "\n"],
            $html
        ));
        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

        return ['html' => $html, 'text' => $text];
    }

    /**
     * Log a sent/failed email
     */
    private static function log(int $userId, string $toEmail, string $subject, string $template, string $status, string $error = ''): void {
        $stmt = DB::$connection->prepare(
            "INSERT INTO email_log (user_id, to_email, subject, template, status, error, ts_sent)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $toEmail, $subject, $template, $status, $error ?: null, time()]);
    }

    /**
     * Get queue statistics (for admin dashboard)
     */
    public static function getQueueStats(): array {
        $stats = [];
        $rows = DB::$connection->query(
            "SELECT status, COUNT(*) as cnt FROM email_queue GROUP BY status"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $stats[$row['status']] = (int)$row['cnt'];
        }

        $stats['pending']  = $stats['pending'] ?? 0;
        $stats['sending']  = $stats['sending'] ?? 0;
        $stats['sent']     = $stats['sent'] ?? 0;
        $stats['failed']   = $stats['failed'] ?? 0;
        $stats['total']    = array_sum($stats);

        // Today's sent count from log
        $todayStart = strtotime('today');
        $stmt = DB::$connection->prepare("SELECT COUNT(*) FROM email_log WHERE status = 'sent' AND ts_sent >= ?");
        $stmt->execute([$todayStart]);
        $stats['sent_today'] = (int)$stmt->fetchColumn();

        return $stats;
    }

}
