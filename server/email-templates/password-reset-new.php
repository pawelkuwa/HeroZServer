<?php
$lang = $locale ?? 'en_GB';
$pw = htmlspecialchars($new_password ?? '');
$t = [
    'pl_PL' => ['title'=>'Reset Hasła', 'msg'=>'Twoje hasło do konta HeroZero zostało zresetowane.', 'newpass'=>'Twoje nowe hasło:', 'login'=>'Zaloguj się nowym hasłem i zmień je w ustawieniach konta.', 'btn'=>'Graj Teraz', 'ignore'=>'Jeśli nie prosiłeś o reset hasła, skontaktuj się z administracją.'],
    'pt_BR' => ['title'=>'Redefinição de Senha', 'msg'=>'Sua senha para o HeroZero foi redefinida.', 'newpass'=>'Sua nova senha:', 'login'=>'Faça login com a nova senha e altere-a nas configurações da conta.', 'btn'=>'Jogar Agora', 'ignore'=>'Se você não solicitou isso, entre em contato com a administração.'],
    'en_GB' => ['title'=>'Password Reset', 'msg'=>'Your HeroZero account password has been reset.', 'newpass'=>'Your new password:', 'login'=>'Log in with your new password and change it in your account settings.', 'btn'=>'Play Now', 'ignore'=>'If you didn\'t request this, please contact the administration.'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'];

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['msg'] . '</p>
<p style="margin:20px 0; font-size:14px; color:#ccc;">' . $s['newpass'] . '</p>
<p style="margin:0 0 20px; text-align:center;">
    <span style="display:inline-block; padding:12px 30px; background:#1a1d23; border:2px solid #6c5bb7; border-radius:6px; font-size:22px; font-weight:bold; color:#ffffff; letter-spacing:3px; font-family:monospace;">
        ' . $pw . '
    </span>
</p>
<p style="color:#999; font-size:13px;">' . $s['login'] . '</p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['ignore'] . '</p>
';

include __DIR__ . '/base.php';
