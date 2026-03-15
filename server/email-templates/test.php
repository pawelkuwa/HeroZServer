<?php
$subject = $subject ?? 'Test Email - HeroZero Server';
$content = '
<h2 style="margin:0 0 15px; color:#ffffff; font-size:22px;">Test Email</h2>
<p>This is a test email from your <strong>HeroZero</strong> private server.</p>
<p>If you received this email, your email system is working correctly!</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:25px 0;">
<tr><td>
    <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding:6px 12px; color:#aaa; font-size:13px; border-bottom:1px solid #3a3f4a;">SMTP Host:</td>
        <td style="padding:6px 12px; color:#e0e0e0; font-size:13px; border-bottom:1px solid #3a3f4a;">' . htmlspecialchars($smtp_host ?? 'N/A') . '</td>
    </tr>
    <tr>
        <td style="padding:6px 12px; color:#aaa; font-size:13px; border-bottom:1px solid #3a3f4a;">SMTP Port:</td>
        <td style="padding:6px 12px; color:#e0e0e0; font-size:13px; border-bottom:1px solid #3a3f4a;">' . htmlspecialchars($smtp_port ?? 'N/A') . '</td>
    </tr>
    <tr>
        <td style="padding:6px 12px; color:#aaa; font-size:13px; border-bottom:1px solid #3a3f4a;">From:</td>
        <td style="padding:6px 12px; color:#e0e0e0; font-size:13px; border-bottom:1px solid #3a3f4a;">' . htmlspecialchars($from_email ?? 'N/A') . '</td>
    </tr>
    <tr>
        <td style="padding:6px 12px; color:#aaa; font-size:13px;">Sent at:</td>
        <td style="padding:6px 12px; color:#e0e0e0; font-size:13px;">' . date('Y-m-d H:i:s') . '</td>
    </tr>
    </table>
</td></tr>
</table>

<p style="color:#888; font-size:13px;">This email was sent as a test from the admin panel.</p>
';

include __DIR__ . '/base.php';
