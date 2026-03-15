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
    <h2><i class="fas fa-user-ninja me-2"></i>Characters <span class="badge bg-secondary"><?= $total ?></span></h2>
</div>

<form method="GET" class="mb-4">
    <input type="hidden" name="page" value="characters">
    <div class="input-group" style="max-width: 500px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="<?= e($search) ?>">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="index.php?page=characters" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Level</th>
                <th>Game Currency</th>
                <th>Honor</th>
                <th>Guild</th>
                <th>User Email</th>
                <th>Last Action</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($characters)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No characters found.</td></tr>
            <?php else: ?>
                <?php foreach ($characters as $c): ?>
                    <tr>
                        <td><strong>#<?= intval($c['id']) ?></strong></td>
                        <td>
                            <?= e($c['name']) ?>
                            <small class="text-muted">(<?= $c['gender'] === 'f' ? 'F' : 'M' ?>)</small>
                        </td>
                        <td><span class="badge bg-info"><?= intval($c['level']) ?></span></td>
                        <td><?= number_format(intval($c['game_currency'])) ?></td>
                        <td><?= number_format(intval($c['honor'])) ?></td>
                        <td>
                            <?php if (!empty($c['guild_id'])): ?>
                                <a href="index.php?page=guilds&action=edit&id=<?= $c['guild_id'] ?>" class="text-decoration-none">
                                    Guild #<?= $c['guild_id'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?page=users&action=edit&id=<?= $c['user_id'] ?>" class="text-decoration-none">
                                <?= e($c['user_email'] ?? '') ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($c['ts_last_action']) && $c['ts_last_action'] > 0): ?>
                                <?= date('Y-m-d H:i', $c['ts_last_action']) ?>
                                <?php if ((time() - $c['ts_last_action']) < 900): ?>
                                    <span class="badge bg-success ms-1"><i class="fas fa-circle" style="font-size: 0.5em;"></i></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?page=characters&action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
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
            <a class="page-link" href="index.php?page=characters&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=characters&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=characters&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
