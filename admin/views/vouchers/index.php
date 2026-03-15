<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-ticket-alt me-2"></i>Vouchers</h2>
        <a href="index.php?page=vouchers&action=create" class="btn btn-success"><i class="fas fa-plus me-1"></i>Create Voucher</a>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show">
            <?= e($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-dark">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?= (int)$stats['total'] ?></h3>
                    <small class="text-muted">Total Vouchers</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark">
                <div class="card-body text-center">
                    <h3 class="text-success"><?= (int)$stats['active'] ?></h3>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark">
                <div class="card-body text-center">
                    <h3 class="text-info"><?= (int)$stats['total_redemptions'] ?></h3>
                    <small class="text-muted">Total Redemptions</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <input type="hidden" name="page" value="vouchers">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by code..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-dark">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Rewards</th>
                        <th>Uses</th>
                        <th>Level</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vouchers)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No vouchers found</td></tr>
                    <?php else: ?>
                        <?php foreach ($vouchers as $v): ?>
                            <?php $rewards = json_decode($v['rewards'], true) ?: []; ?>
                            <tr>
                                <td><?= (int)$v['id'] ?></td>
                                <td><code><?= e($v['code']) ?></code></td>
                                <td>
                                    <?php foreach ($rewards as $k => $val): ?>
                                        <span class="badge bg-secondary"><?= e($k) ?>: <?= (int)$val ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><?= (int)$v['redemption_count'] ?> / <?= $v['uses_max'] > 0 ? (int)$v['uses_max'] : '&infin;' ?></td>
                                <td><?= $v['min_level'] > 0 ? (int)$v['min_level'] : '-' ?></td>
                                <td><?= $v['ts_end'] > 0 ? date('Y-m-d H:i', $v['ts_end']) : 'Never' ?></td>
                                <td>
                                    <?php if ($v['status'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?page=vouchers&action=view&id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                                    <form method="POST" action="index.php?page=vouchers&action=toggle" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle"><i class="fas fa-power-off"></i></button>
                                    </form>
                                    <form method="POST" action="index.php?page=vouchers&action=delete" class="d-inline" onsubmit="return confirm('Delete this voucher?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
