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
    <h2>
        <i class="fas fa-user-edit me-2"></i>Edit User #<?= intval($user['id']) ?>
        <?php if (intval($user['ts_banned']) > time()): ?>
            <span class="badge bg-danger ms-2"><i class="fas fa-ban"></i> Banned</span>
        <?php endif; ?>
    </h2>
    <a href="index.php?page=users" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>User Details</div>
            <div class="card-body">
                <form method="POST" action="index.php?page=users&action=edit&id=<?= $user['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="save_user" value="1">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Locale</label>
                            <input type="text" name="locale" class="form-control" value="<?= e($user['locale']) ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Premium Currency</label>
                            <input type="number" name="premium_currency" class="form-control" value="<?= intval($user['premium_currency']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trusted</label>
                            <select name="trusted" class="form-select">
                                <option value="0" <?= $user['trusted'] == 0 ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= $user['trusted'] == 1 ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Login Count</label>
                            <input type="text" class="form-control" value="<?= number_format(intval($user['login_count'])) ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Registration IP</label>
                            <input type="text" class="form-control" value="<?= e($user['registration_ip'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Login IP</label>
                            <input type="text" class="form-control" value="<?= e($user['last_login_ip']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Login</label>
                            <input type="text" class="form-control" value="<?= $user['ts_last_login'] > 0 ? date('Y-m-d H:i', $user['ts_last_login']) : 'Never' ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Created</label>
                            <input type="text" class="form-control" value="<?= date('Y-m-d H:i', $user['ts_creation']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" value="<?= e($user['geo_country_name']) ?> (<?= e($user['geo_country_code']) ?>)" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Network</label>
                            <input type="text" class="form-control" value="<?= e($user['network']) ?>" readonly>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-ninja me-2"></i>Characters (<?= count($characters) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($characters)): ?>
                    <p class="text-muted p-3 mb-0">No characters found.</p>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Currency</th>
                                <th>Honor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($characters as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><?= e($c['name']) ?></td>
                                    <td><span class="badge bg-info"><?= $c['level'] ?></span></td>
                                    <td><?= number_format(intval($c['game_currency'])) ?></td>
                                    <td><?= number_format(intval($c['honor'])) ?></td>
                                    <td>
                                        <a href="index.php?page=characters&action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-gavel me-2"></i>Ban Control</div>
            <div class="card-body">
                <?php if (intval($user['ts_banned']) > time()): ?>
                    <p class="text-danger mb-2"><strong>Banned until:</strong> <?= date('Y-m-d H:i', $user['ts_banned']) ?></p>
                    <a href="index.php?page=users&action=unban&id=<?= $user['id'] ?>&<?= $GLOBALS['admin_config']['csrf']['token_name'] ?>=<?= csrf_token() ?>"
                       class="btn btn-success w-100" onclick="return confirm('Unban this user?')">
                        <i class="fas fa-unlock me-1"></i> Unban User
                    </a>
                <?php else: ?>
                    <a href="index.php?page=users&action=ban&id=<?= $user['id'] ?>&<?= $GLOBALS['admin_config']['csrf']['token_name'] ?>=<?= csrf_token() ?>"
                       class="btn btn-warning w-100" onclick="return confirm('Ban this user?')">
                        <i class="fas fa-ban me-1"></i> Ban User
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-key me-2"></i>Reset Password</div>
            <div class="card-body">
                <form method="POST" action="index.php?page=users&action=resetPassword&id=<?= $user['id'] ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="text" name="new_password" class="form-control" placeholder="New password" required minlength="4">
                    </div>
                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Reset password?')">
                        <i class="fas fa-key me-1"></i> Reset Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-coins me-2"></i>Adjust Premium Currency</div>
            <div class="card-body">
                <p class="mb-2">Current: <strong class="text-warning"><?= number_format(intval($user['premium_currency'])) ?></strong></p>
                <form method="POST" action="index.php?page=users&action=adjustCurrency&id=<?= $user['id'] ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="number" name="amount" class="form-control" placeholder="Amount" required>
                    </div>
                    <div class="mb-2">
                        <select name="mode" class="form-select">
                            <option value="add">Add / Subtract</option>
                            <option value="set">Set exact value</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-coins me-1"></i> Apply</button>
                </form>
            </div>
        </div>

        <div class="card mb-3 border-danger">
            <div class="card-header bg-danger text-white"><i class="fas fa-skull-crossbones me-2"></i>Danger Zone</div>
            <div class="card-body">
                <a href="index.php?page=users&action=delete&id=<?= $user['id'] ?>&<?= $GLOBALS['admin_config']['csrf']['token_name'] ?>=<?= csrf_token() ?>"
                   class="btn btn-danger w-100"
                   onclick="return confirm('DELETE this user and ALL their characters/items? This CANNOT be undone!')">
                    <i class="fas fa-trash me-1"></i> Delete User Permanently
                </a>
            </div>
        </div>
    </div>
</div>
