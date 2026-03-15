<?php
$lang = $locale ?? 'en_GB';
$t = [
    'pl_PL' => ['title'=>'Konto Przywrócone', 'msg'=>'Twoje konto HeroZero zostało przywrócone. Możesz się ponownie zalogować.', 'btn'=>'Graj Teraz', 'wb'=>'Witaj z powrotem, Bohaterze!'],
    'pt_BR' => ['title'=>'Conta Restaurada', 'msg'=>'Sua conta HeroZero foi restaurada. Você pode fazer login novamente.', 'btn'=>'Jogar Agora', 'wb'=>'Bem-vindo de volta, Herói!'],
    'en_GB' => ['title'=>'Account Restored', 'msg'=>'Your HeroZero account has been restored. You can log in again.', 'btn'=>'Play Now', 'wb'=>'Welcome back, Hero!'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'] . ' — HeroZero';

$content = '
<h2 style="margin:0 0 15px; color:#51cf66; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['msg'] . '</p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['wb'] . '</p>
';

include __DIR__ . '/base.php';
