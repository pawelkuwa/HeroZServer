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
    <h2><i class="fas fa-shield-halved me-2"></i>Guilds <span class="badge bg-secondary"><?= $total ?></span></h2>
</div>

<form method="GET" class="mb-4">
    <input type="hidden" name="page" value="guilds">
    <div class="input-group" style="max-width: 500px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="<?= e($search) ?>">
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="index.php?page=guilds" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Leader</th>
                <th>Members</th>
                <th>Honor</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($guilds)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No guilds found.</td></tr>
            <?php else: ?>
                <?php foreach ($guilds as $g): ?>
                    <tr>
                        <td><strong>#<?= intval($g['id']) ?></strong></td>
                        <td><?= e($g['name']) ?></td>
                        <td><?= e($g['leader_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge bg-info"><?= intval($g['member_count'] ?? 0) ?> / <?= intval($g['stat_guild_capacity']) ?></span>
                        </td>
                        <td><?= number_format(intval($g['honor'])) ?></td>
                        <td>
                            <?php if (intval($g['status']) === 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=guilds&action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="index.php?page=guilds&action=delete&id=<?= $g['id'] ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                       onclick="return confirm('DELETE guild \'<?= e($g['name']) ?>\'? All members will be removed from the guild.')">
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
            <a class="page-link" href="index.php?page=guilds&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=guilds&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=guilds&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
