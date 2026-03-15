<?php
/** @var array $emails */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var string $status */
/** @var array|null $flash */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-list-ol text-warning me-2"></i> Email Queue</h4>
    <div>
        <a href="index.php?page=email" class="btn btn-sm btn-outline-secondary me-1">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <?php if ($total > 0): ?>
        <form method="POST" action="index.php?page=email&action=clearQueue" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear all pending/failed emails?')">
                <i class="fas fa-trash"></i> Clear Failed/Pending
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Status Filter -->
<div class="mb-3">
    <a href="index.php?page=email&action=queue" class="btn btn-sm <?= !$status ? 'btn-light' : 'btn-outline-secondary' ?>">All</a>
    <a href="index.php?page=email&action=queue&status=pending" class="btn btn-sm <?= $status === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
    <a href="index.php?page=email&action=queue&status=sent" class="btn btn-sm <?= $status === 'sent' ? 'btn-success' : 'btn-outline-success' ?>">Sent</a>
    <a href="index.php?page=email&action=queue&status=failed" class="btn btn-sm <?= $status === 'failed' ? 'btn-danger' : 'btn-outline-danger' ?>">Failed</a>
    <span class="text-muted ms-2"><?= number_format($total) ?> total</span>
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
                    <th>Attempts</th>
                    <th>Created</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($emails)): ?>
                    <?php foreach ($emails as $email): ?>
                        <tr>
                            <td><?= (int)$email['id'] ?></td>
                            <td><?= e($email['to_email']) ?></td>
                            <td class="text-truncate" style="max-width:200px"><?= e($email['subject']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($email['template']) ?></span></td>
                            <td>
                                <?php
                                $statusColors = ['pending' => 'warning', 'sending' => 'info', 'sent' => 'success', 'failed' => 'danger'];
                                $color = $statusColors[$email['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= e($email['status']) ?></span>
                            </td>
                            <td><?= (int)$email['attempts'] ?>/<?= (int)$email['max_attempts'] ?></td>
                            <td class="text-muted small"><?= date('M j, H:i', (int)$email['ts_created']) ?></td>
                            <td class="text-muted small text-truncate" style="max-width:150px"><?= e($email['last_error'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Queue is empty</td></tr>
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
                <a class="page-link" href="index.php?page=email&action=queue&status=<?= e($status) ?>&p=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
