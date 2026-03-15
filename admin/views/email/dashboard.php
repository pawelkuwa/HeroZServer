<?php
/** @var array $stats */
/** @var array $recentSent */
/** @var array $recentQueue */
/** @var array|null $flash */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-paper-plane text-warning me-2"></i> Email System</h4>
    <div>
        <a href="index.php?page=email&action=broadcast" class="btn btn-sm btn-outline-info me-1">
            <i class="fas fa-bullhorn"></i> Broadcast
        </a>
        <form method="POST" action="index.php?page=email&action=processQueue" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-success">
                <i class="fas fa-play"></i> Process Queue
            </button>
        </form>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($stats['pending'] ?? 0) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($stats['sent_today'] ?? 0) ?></div>
                    <div class="stat-label">Sent Today</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($stats['failed'] ?? 0) ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
                    <div class="stat-label">Total in Queue</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="index.php?page=email&action=queue" class="btn btn-outline-light w-100 py-3">
            <i class="fas fa-list-ol d-block mb-1"></i> View Queue
        </a>
    </div>
    <div class="col-md-3">
        <a href="index.php?page=email&action=log" class="btn btn-outline-light w-100 py-3">
            <i class="fas fa-history d-block mb-1"></i> Sent Log
        </a>
    </div>
    <div class="col-md-3">
        <a href="index.php?page=email&action=settings" class="btn btn-outline-light w-100 py-3">
            <i class="fas fa-cog d-block mb-1"></i> Settings
        </a>
    </div>
    <div class="col-md-3">
        <a href="index.php?page=email&action=broadcast" class="btn btn-outline-light w-100 py-3">
            <i class="fas fa-bullhorn d-block mb-1"></i> Broadcast
        </a>
    </div>
</div>

<div class="section-header">
    <h5><i class="fas fa-check-circle text-success me-2"></i> Recent Sent Emails</h5>
</div>
<div class="table-dark-custom">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentSent)): ?>
                    <?php foreach ($recentSent as $log): ?>
                        <tr>
                            <td><?= (int)$log['id'] ?></td>
                            <td><?= e($log['to_email']) ?></td>
                            <td><?= e($log['subject']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($log['template']) ?></span></td>
                            <td>
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge bg-success">Sent</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('M j, H:i', (int)$log['ts_sent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No emails sent yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
