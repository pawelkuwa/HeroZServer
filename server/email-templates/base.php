<?php
$_bl = $lang ?? $locale ?? 'en_GB';
$_bhtml = match($_bl) { 'pl_PL' => 'pl', 'pt_BR' => 'pt', default => 'en' };
$_bsub = ['pl_PL'=>'Serwer Prywatny','pt_BR'=>'Servidor Privado','en_GB'=>'Private Server'];
$_bunsub = ['pl_PL'=>'Wypisz się z powiadomień','pt_BR'=>'Cancelar inscrição de notificações','en_GB'=>'Unsubscribe from notifications'];
$_bs = $_bsub[$_bl] ?? $_bsub['en_GB'];
$_bu = $_bunsub[$_bl] ?? $_bunsub['en_GB'];
?>
<!DOCTYPE html>
<html lang="<?= $_bhtml ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($subject ?? 'HeroZero') ?></title>
</head>
<body style="margin:0; padding:0; background-color:#1a1d23; font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#1a1d23; padding:20px 0;">
<tr><td align="center">

<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%;">

<tr>
<td style="background:linear-gradient(135deg,#6c5bb7,#8b7bd4); padding:30px 40px; text-align:center; border-radius:12px 12px 0 0;">
    <h1 style="margin:0; color:#ffffff; font-size:28px; font-weight:bold; letter-spacing:2px;">
        <?= htmlspecialchars($server_name ?? 'HeroZero') ?>
    </h1>
    <p style="margin:5px 0 0; color:rgba(255,255,255,0.8); font-size:13px;"><?= $_bs ?></p>
</td>
</tr>

<tr>
<td style="background-color:#2d3139; padding:35px 40px; color:#e0e0e0; font-size:15px; line-height:1.6;">
    <?= $content ?? '' ?>
</td>
</tr>

<tr>
<td style="background-color:#242830; padding:25px 40px; text-align:center; border-radius:0 0 12px 12px; border-top:1px solid #3a3f4a;">
    <p style="margin:0 0 8px; color:#888; font-size:12px;">
        &copy; <?= $year ?? date('Y') ?> <?= htmlspecialchars($server_name ?? 'HeroZero') ?> Server
    </p>
    <?php if (!empty($unsubscribe_url)): ?>
    <p style="margin:0; font-size:11px;">
        <a href="<?= htmlspecialchars($unsubscribe_url) ?>" style="color:#6c5bb7; text-decoration:none;"><?= $_bu ?></a>
    </p>
    <?php endif; ?>
</td>
</tr>

</table>

</td></tr>
</table>
</body>
</html>
