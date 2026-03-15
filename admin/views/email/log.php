<?php
/** @var array $logs */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var string $search */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-history text-warning me-2"></i> Email Log</h4>
    <div>
        <a href="index.php?page=email" class="btn btn-sm btn-outline-secondary me-1">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <form method="POST" action="index.php?page=email&action=deleteOldLogs" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete logs older than 30 days?')">
                <i class="fas fa-trash"></i> Purge Old
            </button>
        </form>
    </div>
</div>

<!-- Search -->
<form method="GET" class="mb-3">
    <input type="hidden" name="page" value="email">
    <input type="hidden" name="action" value="log">
    <div class="input-group input-group-sm" style="max-width:400px">
        <input type="text" name="q" class="form-control" placeholder="Search by email or subject..." value="<?= e($search) ?>">
        <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
        <?php if ($search): ?>
            <a href="index.php?page=email&action=log" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </div>
    <span class="text-muted small mt-1 d-block"><?= number_format($total) ?> entries</span>
</form>

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
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= (int)$log['id'] ?></td>
                            <td><?= e($log['to_email']) ?></td>
                            <td class="text-truncate" style="max-width:200px"><?= e($log['subject']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($log['template']) ?></span></td>
                            <td>
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge bg-success">Sent</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('M j, H:i', (int)$log['ts_sent']) ?></td>
                            <td class="text-muted small text-truncate" style="max-width:150px"><?= e($log['error'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No log entries</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=email&action=log&q=<?= urlencode($search) ?>&p=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
