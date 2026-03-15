<?php $rewards = json_decode($voucher['rewards'], true) ?: []; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-ticket-alt me-2"></i>Voucher: <code><?= e($voucher['code']) ?></code></h2>
        <a href="index.php?page=vouchers" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-dark">
                <div class="card-header"><h5 class="mb-0">Details</h5></div>
                <div class="card-body">
                    <table class="table table-dark table-sm mb-0">
                        <tr><td class="text-muted">ID</td><td><?= (int)$voucher['id'] ?></td></tr>
                        <tr><td class="text-muted">Code</td><td><code><?= e($voucher['code']) ?></code></td></tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <?php if ($voucher['status'] == 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr><td class="text-muted">Uses</td><td><?= (int)$voucher['uses_current'] ?> / <?= $voucher['uses_max'] > 0 ? (int)$voucher['uses_max'] : '&infin;' ?></td></tr>
                        <tr><td class="text-muted">Min Level</td><td><?= $voucher['min_level'] > 0 ? (int)$voucher['min_level'] : 'None' ?></td></tr>
                        <tr><td class="text-muted">Locale</td><td><?= !empty($voucher['locale']) ? e($voucher['locale']) : 'All' ?></td></tr>
                        <tr><td class="text-muted">User ID</td><td><?= $voucher['user_id'] > 0 ? (int)$voucher['user_id'] : 'Any' ?></td></tr>
                        <tr><td class="text-muted">Start</td><td><?= $voucher['ts_start'] > 0 ? date('Y-m-d H:i', $voucher['ts_start']) : 'Immediate' ?></td></tr>
                        <tr><td class="text-muted">End</td><td><?= $voucher['ts_end'] > 0 ? date('Y-m-d H:i', $voucher['ts_end']) : 'Never' ?></td></tr>
                        <tr><td class="text-muted">Created</td><td><?= date('Y-m-d H:i', $voucher['ts_creation']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-dark">
                <div class="card-header"><h5 class="mb-0">Rewards</h5></div>
                <div class="card-body">
                    <?php if (empty($rewards)): ?>
                        <p class="text-muted mb-0">No rewards configured</p>
                    <?php else: ?>
                        <table class="table table-dark table-sm mb-0">
                            <?php foreach ($rewards as $key => $value): ?>
                                <tr>
                                    <td class="text-muted"><?= e(ucwords(str_replace('_', ' ', $key))) ?></td>
                                    <td class="text-end"><strong><?= is_array($value) ? count($value) . ' items' : number_format((int)$value) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark">
        <div class="card-header"><h5 class="mb-0">Redemption Log (<?= count($redemptions) ?>)</h5></div>
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Character</th>
                        <th>Redeemed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($redemptions)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No redemptions yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($redemptions as $r): ?>
                            <tr>
                                <td><?= (int)$r['user_id'] ?></td>
                                <td><?= e($r['email'] ?? 'N/A') ?></td>
                                <td><?= e($r['character_name'] ?? 'N/A') ?></td>
                                <td><?= date('Y-m-d H:i:s', $r['ts_redeemed']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
