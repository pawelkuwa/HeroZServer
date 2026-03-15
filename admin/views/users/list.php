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
    <h2><i class="fas fa-users me-2"></i>Users <span class="badge bg-secondary"><?= $total ?></span></h2>
</div>

<form method="GET" class="mb-4">
    <input type="hidden" name="page" value="users">
    <div class="input-group" style="max-width: 500px;">
        <input type="text" name="search" class="form-control" placeholder="Search by email, ID, or IP..." value="<?= e($search) ?>">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="index.php?page=users" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Premium Currency</th>
                <th>Login Count</th>
                <th>Last Login</th>
                <th>Locale</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong>#<?= intval($u['id']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-coins"></i> <?= number_format(intval($u['premium_currency'])) ?>
                            </span>
                        </td>
                        <td><?= number_format(intval($u['login_count'])) ?></td>
                        <td>
                            <?php if ($u['ts_last_login'] > 0): ?>
                                <?= date('Y-m-d H:i', $u['ts_last_login']) ?>
                                <?php if ((time() - $u['ts_last_login']) < 900): ?>
                                    <span class="badge bg-success ms-1" title="Online recently"><i class="fas fa-circle" style="font-size: 0.5em;"></i></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-info"><?= e($u['locale']) ?></span></td>
                        <td>
                            <?php if (intval($u['ts_banned']) > time()): ?>
                                <span class="badge bg-danger"><i class="fas fa-ban"></i> Banned</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (intval($u['ts_banned']) > time()): ?>
                                    <form method="POST" action="index.php?page=users&action=unban&id=<?= $u['id'] ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Unban" onclick="return confirm('Unban user #<?= $u['id'] ?>?')">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="index.php?page=users&action=ban&id=<?= $u['id'] ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Ban" onclick="return confirm('Ban user #<?= $u['id'] ?>?')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="index.php?page=users&action=delete&id=<?= $u['id'] ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('DELETE user #<?= $u['id'] ?> and ALL their characters/items? This cannot be undone!')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=users&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=users&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=users&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
