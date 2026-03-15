<?php
/**
 * Reset Password Page (Step 2)
 * User clicks the link from email → generates random password → sends second email with password.
 * If token already used or invalid → shows error.
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
        'title'     => 'Reset Hasła',
        'success'   => 'Twoje nowe hasło zostało wygenerowane i wysłane na Twój email.',
        'invalid'   => 'Ten link jest nieprawidłowy lub już został użyty.',
        'back'      => 'Powrót do gry',
        'check'     => 'Sprawdź swoją skrzynkę email, aby zobaczyć nowe hasło.',
    ],
    'en_GB' => [
        'title'     => 'Password Reset',
        'success'   => 'Your new password has been generated and sent to your email.',
        'invalid'   => 'This link is invalid or has already been used.',
        'back'      => 'Back to Game',
        'check'     => 'Check your email to see your new password.',
    ],
    'pt_BR' => [
        'title'     => 'Redefinição de Senha',
        'success'   => 'Sua nova senha foi criada e enviada para o seu email.',
        'invalid'   => 'Este link é inválido ou já foi utilizado.',
        'back'      => 'Voltar ao Jogo',
        'check'     => 'Verifique seu email para ver a nova senha.',
    ],
];
$t = $translations[$lang] ?? $translations['en_GB'];

$message = '';
$messageType = '';
$token = $_GET['code'] ?? '';
$userId = (int) ($_GET['resetpassword'] ?? 0);

if (!empty($token) && $userId > 0) {
    $stmt = \Srv\DB::$connection->prepare(
        "SELECT t.*, u.email, u.locale FROM password_reset_tokens t
         JOIN user u ON u.id = t.user_id
         WHERE t.token = ? AND t.user_id = ? AND t.used = 0 AND t.ts_expires > ?
         LIMIT 1"
    );
    $stmt->execute([$token, $userId, time()]);
    $tokenData = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($tokenData) {
        $newPassword = substr(str_shuffle('abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);

        $hashed = \Srv\Core::passwordHash($newPassword);
        $stmt = \Srv\DB::$connection->prepare("UPDATE user SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hashed, $tokenData['user_id']]);

        $stmt = \Srv\DB::$connection->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
        $stmt->execute([$tokenData['id']]);

        // Invalidate all other tokens for this user
        $stmt = \Srv\DB::$connection->prepare(
            "UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0"
        );
        $stmt->execute([$tokenData['user_id']]);

        \Srv\Mail::queue(
            (int) $tokenData['user_id'],
            $tokenData['email'],
            'Your New Password — HeroZero',
            'password-reset-new',
            [
                'new_password' => $newPassword,
                'user_id' => $tokenData['user_id'],
                'locale' => $lang,
            ],
            1
        );

        $message = $t['success'];
        $messageType = 'success';
    } else {
        $message = $t['invalid'];
        $messageType = 'error';
    }
} else {
    $message = $t['invalid'];
    $messageType = 'error';
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
        .card-body { padding: 35px 40px; text-align: center; }
        .card-body h2 { font-size: 20px; color: #fff; margin-bottom: 20px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; }
        .alert-success { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.3); color: #5cb85c; }
        .alert-error { background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.3); color: #e57373; }
        .hint { color: #999; font-size: 13px; margin-bottom: 20px; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #6c5bb7; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
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

                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>

                <?php if ($messageType === 'success'): ?>
                    <p class="hint"><?= htmlspecialchars($t['check']) ?></p>
                <?php endif; ?>

                <a href="/" class="back-link">&larr; <?= htmlspecialchars($t['back']) ?></a>
            </div>
        </div>
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
