<?php
$lang = $locale ?? 'en_GB';
$subject = $subject ?? 'Server Announcement';
$btn = ['pl_PL'=>'Graj Teraz', 'pt_BR'=>'Jogar Agora', 'en_GB'=>'Play Now'];
$b = $btn[$lang] ?? $btn['en_GB'];

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . htmlspecialchars($subject) . '</h2>
<div style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($message ?? '')) . '</div>
<p style="margin-top:25px;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:12px 30px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold;">
        ' . $b . '
    </a>
</p>
';

include __DIR__ . '/base.php';
