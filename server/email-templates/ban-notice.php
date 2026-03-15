<?php
$lang = $locale ?? 'en_GB';
$t = [
    'pl_PL' => ['title'=>'Konto Zawieszone', 'msg'=>'Twoje konto HeroZero zostało zawieszone przez administratora.', 'contact'=>'Jeśli uważasz, że to pomyłka, skontaktuj się z administracją serwera.', 'auto'=>'To jest automatyczne powiadomienie. Nie odpowiadaj na tego e-maila.'],
    'pt_BR' => ['title'=>'Conta Suspensa', 'msg'=>'Sua conta HeroZero foi suspensa por um administrador.', 'contact'=>'Se você acredita que isso foi um erro, entre em contato com a administração do servidor.', 'auto'=>'Esta é uma notificação automática. Não responda a este email.'],
    'en_GB' => ['title'=>'Account Suspended', 'msg'=>'Your HeroZero account has been suspended by an administrator.', 'contact'=>'If you believe this was a mistake, please contact the server administration.', 'auto'=>'This is an automated notification. Do not reply to this email.'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'] . ' — HeroZero';

$content = '
<h2 style="margin:0 0 15px; color:#ff6b6b; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['msg'] . '</p>
<p>' . $s['contact'] . '</p>
<p style="color:#999; font-size:13px; margin-top:20px; border-top:1px solid #3a3f4a; padding-top:15px;">' . $s['auto'] . '</p>
';

include __DIR__ . '/base.php';
