<?php
/** @var int $totalUsers */
/** @var array|null $flash */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-bullhorn text-warning me-2"></i> Broadcast Email</h4>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <form method="POST" action="index.php?page=email&action=sendBroadcast">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Server Announcement - HeroZero" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="8" placeholder="Write your announcement here..." required></textarea>
                        <div class="form-text">Plain text. Will be wrapped in the HeroZero email template.</div>
                    </div>

                    <div class="alert alert-info small">
                        <i class="fas fa-users me-1"></i>
                        This will queue emails for <strong><?= number_format($totalUsers) ?></strong> active users
                        (with email notifications enabled).
                        <br>
                        <i class="fas fa-info-circle me-1"></i>
                        Emails are queued, not sent immediately. Use "Process Queue" to send them.
                    </div>

                    <button type="submit" class="btn btn-warning" onclick="return confirm('Queue broadcast to <?= $totalUsers ?> users?')">
                        <i class="fas fa-bullhorn"></i> Queue Broadcast
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
