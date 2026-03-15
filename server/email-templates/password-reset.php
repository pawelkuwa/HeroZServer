<?php
$lang = $locale ?? 'en_GB';
$rl = htmlspecialchars($reset_link ?? '#');
$ttl = htmlspecialchars($ttl_minutes ?? '60');
$t = [
    'pl_PL' => ['title'=>'Reset Hasła', 'msg'=>'Poprosiłeś o reset hasła do konta HeroZero.', 'click'=>'Kliknij poniższy przycisk, aby ustawić nowe hasło:', 'btn'=>'Resetuj Hasło', 'expires'=>"Ten link wygasa za <strong>{$ttl} minut</strong>.", 'ignore'=>'Jeśli nie prosiłeś o to, zignoruj tę wiadomość. Twoje hasło pozostanie bez zmian.', 'alt'=>'Jeśli przycisk nie działa, skopiuj i wklej ten link w przeglądarce:'],
    'pt_BR' => ['title'=>'Redefinição de Senha', 'msg'=>'Você solicitou a redefinição de senha da sua conta HeroZero.', 'click'=>'Clique no botão abaixo para definir uma nova senha:', 'btn'=>'Redefinir Senha', 'expires'=>"Este link expira em <strong>{$ttl} minutos</strong>.", 'ignore'=>'Se você não solicitou isso, pode ignorar este email. Sua senha não será alterada.', 'alt'=>'Se o botão não funcionar, copie e cole este link no seu navegador:'],
    'en_GB' => ['title'=>'Password Reset', 'msg'=>'You requested a password reset for your HeroZero account.', 'click'=>'Click the button below to set a new password:', 'btn'=>'Reset My Password', 'expires'=>"This link expires in <strong>{$ttl} minutes</strong>.", 'ignore'=>'If you didn\'t request this, you can safely ignore this email. Your password will remain unchanged.', 'alt'=>'If the button doesn\'t work, copy and paste this link into your browser:'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'];

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['msg'] . '</p>
<p>' . $s['click'] . '</p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . $rl . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['expires'] . '</p>
<p style="color:#999; font-size:13px;">' . $s['ignore'] . '</p>
<p style="margin-top:20px; color:#666; font-size:12px; border-top:1px solid #3a3f4a; padding-top:15px;">
    ' . $s['alt'] . '<br>
    <a href="' . $rl . '" style="color:#6c5bb7; word-break:break-all;">' . $rl . '</a>
</p>
';

include __DIR__ . '/base.php';
