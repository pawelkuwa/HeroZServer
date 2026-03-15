<?php if (!defined('IN_ENGINE')) exit; ?>

<?php
if (!empty($_SESSION['flash'])):
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
?>
<div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-envelope me-2"></i>Messages <span class="badge bg-secondary"><?= $total ?></span></h2>
    <a href="index.php?page=messages&action=compose" class="btn btn-success">
        <i class="fas fa-pen me-1"></i> Compose
    </a>
</div>

<!-- Table -->
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>From</th>
                <th>To</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Read</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($messages)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No messages found.</td></tr>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td><strong>#<?= intval($msg['id']) ?></strong></td>
                        <td>
                            <?php if ($msg['character_from_id']): ?>
                                <a href="index.php?page=characters&action=edit&id=<?= $msg['character_from_id'] ?>">
                                    <?= e($msg['from_name'] ?? 'Char #' . $msg['character_from_id']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted"><?= e(substr($msg['character_to_ids'] ?? '', 0, 50)) ?></small>
                        </td>
                        <td><?= e($msg['subject'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($msg['ts_creation']) && $msg['ts_creation'] > 0): ?>
                                <?= date('Y-m-d H:i', $msg['ts_creation']) ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (intval($msg['readed'] ?? 0)): ?>
                                <span class="badge bg-success">Read</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="index.php?page=messages&action=delete&id=<?= $msg['id'] ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete message #<?= $msg['id'] ?>?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=messages&p=<?= $page - 1 ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=messages&p=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=messages&p=<?= $page + 1 ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
