<?php
$lang = $locale ?? 'en_GB';
$gn = htmlspecialchars($guild_name ?? '');
$inv = htmlspecialchars($inviter_name ?? '');
$t = [
    'pl_PL' => ['title'=>'Zaproszenie do Drużyny', 'invited'=>"Zostałeś zaproszony do drużyny <strong>{$gn}</strong>!", 'by'=>'Zaproszony przez:', 'btn'=>'Zaloguj się', 'hint'=>'Zaloguj się, aby zaakceptować lub odrzucić zaproszenie.'],
    'pt_BR' => ['title'=>'Convite de Guilda', 'invited'=>"Você foi convidado para a guilda <strong>{$gn}</strong>!", 'by'=>'Convidado por:', 'btn'=>'Entrar no Jogo', 'hint'=>'Faça login para aceitar ou recusar o convite.'],
    'en_GB' => ['title'=>'Guild Invitation', 'invited'=>"You've been invited to join <strong>{$gn}</strong>!", 'by'=>'Invited by:', 'btn'=>'Log In to Respond', 'hint'=>'Log in to accept or decline the invitation.'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'] . ' — HeroZero';

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['invited'] . '</p>
<p>' . $s['by'] . ' <strong>' . $inv . '</strong></p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['hint'] . '</p>
';

include __DIR__ . '/base.php';
