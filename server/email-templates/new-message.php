<?php
$lang = $locale ?? 'en_GB';
$sn = htmlspecialchars($sender_name ?? '');
$ms = htmlspecialchars($msg_subject ?? '');
$t = [
    'pl_PL' => ['title'=>'Nowa Wiadomość', 'from'=>"Masz nową wiadomość od <strong>{$sn}</strong>.", 'subj'=>'Temat:', 'btn'=>'Czytaj Wiadomość', 'hint'=>'Zaloguj się, aby przeczytać pełną wiadomość.'],
    'pt_BR' => ['title'=>'Nova Mensagem', 'from'=>"Você tem uma nova mensagem de <strong>{$sn}</strong>.", 'subj'=>'Assunto:', 'btn'=>'Ler Mensagem', 'hint'=>'Faça login para ler a mensagem completa.'],
    'en_GB' => ['title'=>'New Message', 'from'=>"You have a new message from <strong>{$sn}</strong>.", 'subj'=>'Subject:', 'btn'=>'Read Message', 'hint'=>'Log in to read the full message.'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'] . ' — HeroZero';

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['from'] . '</p>
<p style="background:#242830; padding:12px 16px; border-radius:6px; border-left:3px solid #6c5bb7;">
    <strong style="color:#ccc;">' . $s['subj'] . '</strong> ' . $ms . '
</p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['hint'] . '</p>
';

include __DIR__ . '/base.php';
