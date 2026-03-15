<?php
/**
 * Forgot Password Page
 * Generates a password reset token and queues a reset email.
 * Always shows the same message regardless of whether the email exists (prevents enumeration).
 * Multi-language support: PL, EN, BR (via web-lang cookie, same as game.php)
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('IN_ENGINE', true);
define('BASE_DIR', dirname(__DIR__));
define('SERVER_DIR', dirname(__DIR__) . '/server');

require_once SERVER_DIR . '/src/Utils/functions.php';
require_once SERVER_DIR . '/src/Utils/autoloader.php';
require_once BASE_DIR . '/vendor/autoload.php';

\Srv\Config::__init();
\Srv\DB::__init();

$lang = $_COOKIE['web-lang'] ?? 'pl_PL';
$translations = [
    'pl_PL' => [
        'title'           => 'Zapomniałeś hasła',
        'desc'            => 'Wpisz adres email powiązany z kontem, a wyślemy Ci link do zresetowania hasła.',
        'email_label'     => 'Adres Email',
        'email_placeholder' => 'twoj@email.com',
        'submit'          => 'Wyślij link resetujący',
        'back'            => 'Powrót do gry',
        'note'            => 'Pamiętaj: przetworz kolejkę emaili w panelu admina, aby wysłać wiadomość.',
        'invalid_email'   => 'Proszę podać prawidłowy adres email.',
        'sent_msg'        => 'Jeśli konto z tym emailem istnieje, link do resetowania hasła został wysłany.',
        'cooldown_msg'    => 'Proszę poczekać 60 sekund przed kolejną próbą.',
    ],
    'en_GB' => [
        'title'           => 'Forgot Password',
        'desc'            => 'Enter your account email and we\'ll send you a reset link.',
        'email_label'     => 'Email Address',
        'email_placeholder' => 'your@email.com',
        'submit'          => 'Reset Password',
        'back'            => 'Back to Game',
        'note'            => 'Remember: process the email queue in the admin panel to send the email.',
        'invalid_email'   => 'Please enter a valid email address.',
        'sent_msg'        => 'If an account with that email exists, a password reset link has been sent.',
        'cooldown_msg'    => 'Please wait 60 seconds before trying again.',
    ],
    'pt_BR' => [
        'title'           => 'Esqueceu a Senha',
        'desc'            => 'Insira o email da sua conta e enviaremos um link para redefinir a sua senha.',
        'email_label'     => 'Endereço de Email',
        'email_placeholder' => 'seu@email.com',
        'submit'          => 'Enviar Link de Redefinição',
        'back'            => 'Voltar ao Jogo',
        'note'            => 'Lembre-se: processe a fila de emails no painel de administração para enviar o email.',
        'invalid_email'   => 'Por favor, insira um endereço de email válido.',
        'sent_msg'        => 'Se existir uma conta com esse email, um link de redefinição de senha foi enviado.',
        'cooldown_msg'    => 'Por favor, aguarde 60 segundos antes de tentar novamente.',
    ],
];
$t = $translations[$lang] ?? $translations['en_GB'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = $t['invalid_email'];
        $messageType = 'error';
    } else {
        $recentRequests = 0;

        // Cooldown: 60s between requests for the same email
        $cooldown = 60;
        $stmt = \Srv\DB::$connection->prepare(
            "SELECT MAX(t.ts_created) FROM password_reset_tokens t
             JOIN user u ON u.id = t.user_id
             WHERE u.email = ?"
        );
        $stmt->execute([$email]);
        $lastRequest = (int) $stmt->fetchColumn();

        if ($lastRequest > 0 && (time() - $lastRequest) < $cooldown) {
            $message = $t['cooldown_msg'];
            $messageType = 'error';
        // Rate limit: max 20 requests per email per hour
        } else {
            $oneHourAgo = time() - 3600;
            $stmt = \Srv\DB::$connection->prepare(
                "SELECT COUNT(*) FROM password_reset_tokens t
                 JOIN user u ON u.id = t.user_id
                 WHERE u.email = ? AND t.ts_created > ?"
            );
            $stmt->execute([$email, $oneHourAgo]);
            $recentRequests = (int) $stmt->fetchColumn();
        }

        if (!$message && $recentRequests >= 20) {
            $message = $t['sent_msg'];
            $messageType = 'success';
        } elseif (!$message) {
            $stmt = \Srv\DB::$connection->prepare("SELECT id, email, locale FROM user WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // Clean up old tokens (used or expired)
                $stmt = \Srv\DB::$connection->prepare(
                    "DELETE FROM password_reset_tokens WHERE user_id = ? AND (used = 1 OR ts_expires < ?)"
                );
                $stmt->execute([$user['id'], time()]);

                $token = bin2hex(random_bytes(32));
                $now = time();
                $ttl = (int) (\Srv\Config::get('email.reset_token_ttl') ?: 3600);
                $expires = $now + $ttl;

                $stmt = \Srv\DB::$connection->prepare(
                    "INSERT INTO password_reset_tokens (user_id, token, ts_created, ts_expires, used) VALUES (?, ?, ?, ?, 0)"
                );
                $stmt->execute([$user['id'], $token, $now, $expires]);

                $resetUrl = \Srv\Config::get('email.reset_url') ?: 'http://localhost/pages/reset-password.php';
                $resetLink = $resetUrl . '?resetpassword=' . $user['id'] . '&code=' . $token;

                $ttlMinutes = (int) ($ttl / 60);
                \Srv\Mail::queue(
                    (int) $user['id'],
                    $user['email'],
                    'Password Reset — HeroZero',
                    'password-reset',
                    [
                        'reset_link' => $resetLink,
                        'ttl_minutes' => $ttlMinutes,
                        'user_id' => $user['id'],
                        'locale' => $lang,
                    ],
                    1
                );
            }

            // Always show same message (prevents user enumeration)
            $message = $t['sent_msg'];
            $messageType = 'success';
        }
    }
}

$serverName = \Srv\Config::get('site.server_name') ?: 'HeroZero';
$htmlLang = match($lang) { 'pl_PL' => 'pl', 'pt_BR' => 'pt', default => 'en' };
?>
<!DOCTYPE html>
<html lang="<?= $htmlLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?> — <?= htmlspecialchars($serverName) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #1a1d23;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { width: 100%; max-width: 440px; padding: 20px; }
        .card { background: #2d3139; border-radius: 12px; overflow: hidden; }
        .card-header {
            background: linear-gradient(135deg, #6c5bb7, #8b7bd4);
            padding: 30px 40px; text-align: center;
        }
        .card-header h1 { color: #fff; font-size: 28px; font-weight: bold; letter-spacing: 2px; margin: 0; }
        .card-header p { color: rgba(255,255,255,0.8); font-size: 13px; margin-top: 5px; }
        .card-body { padding: 35px 40px; }
        .card-body h2 { font-size: 20px; color: #fff; margin-bottom: 10px; }
        .card-body .desc { color: #aaa; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #ccc; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 16px; background: #1a1d23;
            border: 1px solid #3a3f4a; border-radius: 6px; color: #e0e0e0;
            font-size: 15px; outline: none; transition: border-color 0.2s;
        }
        .form-group input:focus { border-color: #6c5bb7; }
        .btn {
            display: inline-block; width: 100%; padding: 13px 30px; background: #6c5bb7;
            color: #fff; border: none; border-radius: 6px; font-size: 15px;
            font-weight: bold; cursor: pointer; text-align: center; transition: background 0.2s;
        }
        .btn:hover { background: #5a4a9e; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; line-height: 1.4; }
        .alert-success { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.3); color: #5cb85c; }
        .alert-error { background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.3); color: #e57373; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #6c5bb7; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
        .note { text-align: center; color: #666; font-size: 12px; margin-top: 15px; }
        .lang-switch { text-align: center; margin-top: 15px; }
        .lang-switch a { color: #888; text-decoration: none; font-size: 12px; margin: 0 6px; }
        .lang-switch a:hover { color: #6c5bb7; }
        .lang-switch a.active { color: #6c5bb7; font-weight: bold; }
    </style>
    <script src="../js/js.cookie.js"></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><?= htmlspecialchars($serverName) ?></h1>
                <p>Private Server</p>
            </div>
            <div class="card-body">
                <h2><?= htmlspecialchars($t['title']) ?></h2>
                <p class="desc"><?= htmlspecialchars($t['desc']) ?></p>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($messageType !== 'success'): ?>
                <form method="POST" action="/pages/forgot-password.php">
                    <div class="form-group">
                        <label for="email"><?= htmlspecialchars($t['email_label']) ?></label>
                        <input type="email" id="email" name="email" placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn"><?= htmlspecialchars($t['submit']) ?></button>
                </form>
                <?php endif; ?>

                <a href="/" class="back-link">&larr; <?= htmlspecialchars($t['back']) ?></a>
            </div>
        </div>
        <p class="note"><?= htmlspecialchars($t['note']) ?></p>
        <div class="lang-switch">
            <a href="#" class="<?= $lang === 'pl_PL' ? 'active' : '' ?>" onclick="switchLang('pl_PL')">PL</a>
            <a href="#" class="<?= $lang === 'en_GB' ? 'active' : '' ?>" onclick="switchLang('en_GB')">EN</a>
            <a href="#" class="<?= $lang === 'pt_BR' ? 'active' : '' ?>" onclick="switchLang('pt_BR')">BR</a>
        </div>
    </div>
    <script>
        function switchLang(lang) {
            Cookies.set('web-lang', lang, { expires: 365, path: '/' });
            location.reload();
        }
    </script>
</body>
</html>
