<?php
$lang = $locale ?? 'en_GB';
$t = [
    'pl_PL' => ['title'=>'Witaj, Bohaterze!', 'created'=>'Twoje konto zostało utworzone.', 'start'=>'Stwórz postać i rozpocznij przygodę!', 'btn'=>'Graj Teraz', 'ignore'=>'Jeśli nie tworzyłeś tego konta, zignoruj tę wiadomość.'],
    'pt_BR' => ['title'=>'Bem-vindo, Herói!', 'created'=>'Sua conta foi criada com sucesso.', 'start'=>'Crie seu personagem e comece sua aventura!', 'btn'=>'Jogar Agora', 'ignore'=>'Se você não criou esta conta, pode ignorar este email.'],
    'en_GB' => ['title'=>'Welcome, Hero!', 'created'=>'Your account has been created successfully.', 'start'=>'Create your character and start your adventure!', 'btn'=>'Play Now', 'ignore'=>'If you didn\'t create this account, you can safely ignore this email.'],
];
$s = $t[$lang] ?? $t['en_GB'];
$subject = $subject ?? $s['title'];

$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">' . $s['title'] . '</h2>
<p>' . $s['created'] . '</p>
<p>' . $s['start'] . '</p>
<p style="margin:25px 0; text-align:center;">
    <a href="' . htmlspecialchars($game_url ?? 'http://localhost/') . '"
       style="display:inline-block; padding:14px 40px; background:#6c5bb7; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold; font-size:16px;">
        ' . $s['btn'] . '
    </a>
</p>
<p style="color:#999; font-size:13px;">' . $s['ignore'] . '</p>
';

include __DIR__ . '/base.php';
