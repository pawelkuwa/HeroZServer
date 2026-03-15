<?php
/** @var array $emailConfig */
/** @var array|null $flash */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-cog text-warning me-2"></i> Email Settings</h4>
    <a href="index.php?page=email" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- SMTP Configuration -->
    <div class="col-lg-6">
        <div class="section-header">
            <h5><i class="fas fa-server text-info me-2"></i> SMTP Configuration</h5>
        </div>
        <div class="table-dark-custom">
            <table class="table mb-0">
                <tbody>
                    <?php foreach ($emailConfig as $key => $value): ?>
                        <tr>
                            <td class="text-muted" style="width:40%"><?= e($key) ?></td>
                            <td>
                                <?php if ($key === 'smtp_auth'): ?>
                                    <span class="badge <?= $value ? 'bg-success' : 'bg-secondary' ?>"><?= $value ? 'Yes' : 'No' ?></span>
                                <?php elseif ($key === 'smtp_username' && empty($value)): ?>
                                    <span class="text-muted">(none)</span>
                                <?php elseif ($key === 'smtp_encryption' && empty($value)): ?>
                                    <span class="text-muted">(none)</span>
                                <?php else: ?>
                                    <code><?= e((string)$value) ?></code>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mt-2">
            <i class="fas fa-info-circle"></i>
            Edit <code>server/config.php</code> to change SMTP settings.
        </p>
    </div>

    <!-- Send Test Email -->
    <div class="col-lg-6">
        <div class="section-header">
            <h5><i class="fas fa-flask text-success me-2"></i> Send Test Email</h5>
        </div>
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <form method="POST" action="index.php?page=email&action=sendTest">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Recipient Email</label>
                        <input type="email" name="test_email" class="form-control" placeholder="test@example.com" required>
                    </div>
                    <p class="text-muted small">
                        Sends a test email directly (not via queue). Make sure MailPit is running on port <?= e($emailConfig['smtp_port'] ?? '1025') ?>.
                    </p>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send Test
                    </button>
                </form>
            </div>
        </div>

        <!-- MailPit Info -->
        <div class="card bg-dark border-secondary mt-3">
            <div class="card-body">
                <h6><i class="fas fa-inbox text-info"></i> MailPit</h6>
                <p class="text-muted small mb-2">Local mail catcher for development. View captured emails:</p>
                <a href="http://localhost:8025" target="_blank" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-external-link-alt"></i> Open MailPit (localhost:8025)
                </a>
                <p class="text-muted small mt-2 mb-0">
                    Run: <code>C:\laragon\bin\mailpit\mailpit.exe</code>
                </p>
            </div>
        </div>
    </div>
</div>
