<?php
/** @var int $totalUsers */
/** @var int $totalCharacters */
/** @var int $totalGuilds */
/** @var int $onlinePlayers */
/** @var int $bannedUsers */
/** @var array $recentUsers */
/** @var array $topCharacters */
/** @var int|string $totalGameCurrency */
/** @var int|string $totalPremiumCurrency */
/** @var string $serverUptime */
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-tachometer-alt text-warning me-2"></i> Dashboard</h4>
    <span class="text-muted small">
        <i class="fas fa-server me-1"></i> Uptime: <?= e($serverUptime ?? '0d 0h 0m') ?>
    </span>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($totalUsers ?? 0) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($totalCharacters ?? 0) ?></div>
                    <div class="stat-label">Characters</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-user-ninja"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($totalGuilds ?? 0) ?></div>
                    <div class="stat-label">Guilds</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-shield-halved"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($onlinePlayers ?? 0) ?></div>
                    <div class="stat-label">Online Now</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-circle-dot"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($bannedUsers ?? 0) ?></div>
                    <div class="stat-label">Banned</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-warning"><?= number_format($totalGameCurrency ?? 0) ?></div>
                    <div class="stat-label">Game Currency in Circulation</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-info"><?= number_format($totalPremiumCurrency ?? 0) ?></div>
                    <div class="stat-label">Premium Currency in Circulation</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-gem"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="section-header">
            <h5><i class="fas fa-user-plus text-success me-2"></i> Recent Registrations</h5>
        </div>
        <div class="table-dark-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>IP</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?= (int)$user['id'] ?></td>
                                    <td>
                                        <a href="index.php?page=users&action=view&id=<?= (int)$user['id'] ?>"
                                           class="text-decoration-none">
                                            <?= e($user['email']) ?>
                                        </a>
                                    </td>
                                    <td class="text-muted small">
                                        <?= $user['ts_creation'] > 0
                                            ? date('M j, Y H:i', (int)$user['ts_creation'])
                                            : 'N/A' ?>
                                    </td>
                                    <td class="text-muted small"><?= e($user['registration_ip']) ?></td>
                                    <td>
                                        <?php if ((int)$user['ts_banned'] > 0): ?>
                                            <span class="badge badge-banned">Banned</span>
                                        <?php else: ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="section-header">
            <h5><i class="fas fa-trophy text-warning me-2"></i> Top Characters by Level</h5>
        </div>
        <div class="table-dark-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Level</th>
                            <th>Honor</th>
                            <th>Currency</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topCharacters)): ?>
                            <?php foreach ($topCharacters as $i => $char): ?>
                                <tr>
                                    <td>
                                        <?php if ($i < 3): ?>
                                            <i class="fas fa-medal" style="color: <?= ['#ffd700','#c0c0c0','#cd7f32'][$i] ?>"></i>
                                        <?php else: ?>
                                            <?= $i + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?page=characters&action=view&id=<?= (int)$char['id'] ?>"
                                           class="text-decoration-none fw-semibold">
                                            <?= e($char['name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-level">Lv. <?= (int)$char['level'] ?></span></td>
                                    <td><?= number_format((int)$char['honor']) ?></td>
                                    <td class="text-warning"><?= number_format((int)$char['game_currency']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No characters found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
