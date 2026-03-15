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
        <i class="fas fa-shield-halved me-2"></i>Edit Guild: <?= e($guild['name']) ?>
        <?php if (intval($guild['status']) === 1): ?>
            <span class="badge bg-success ms-2">Active</span>
        <?php else: ?>
            <span class="badge bg-secondary ms-2">Inactive</span>
        <?php endif; ?>
    </h2>
    <a href="index.php?page=guilds" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Guilds</a>
</div>

<div class="row">
    <!-- Main Edit Form -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Guild Details</div>
            <div class="card-body">
                <form method="POST" action="index.php?page=guilds&action=edit&id=<?= $guild['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="save_guild" value="1">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($guild['name']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" <?= $guild['status'] == 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $guild['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Accept Members</label>
                            <select name="accept_members" class="form-select">
                                <option value="1" <?= $guild['accept_members'] == 1 ? 'selected' : '' ?>>Yes</option>
                                <option value="0" <?= $guild['accept_members'] == 0 ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Honor</label>
                            <input type="number" name="honor" class="form-control" value="<?= intval($guild['honor']) ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Game Currency</label>
                            <input type="number" name="game_currency" class="form-control" value="<?= intval($guild['game_currency']) ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Premium Currency</label>
                            <input type="number" name="premium_currency" class="form-control" value="<?= intval($guild['premium_currency']) ?>" min="0">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Missiles</label>
                            <input type="number" name="missiles" class="form-control" value="<?= intval($guild['missiles']) ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Guild Capacity</label>
                            <input type="number" name="stat_guild_capacity" class="form-control" value="<?= intval($guild['stat_guild_capacity']) ?>" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Leader</label>
                            <input type="text" class="form-control" value="<?= e($guild['leader_name'] ?? 'N/A') ?> (#<?= intval($guild['leader_character_id']) ?>)" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($guild['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"><?= e($guild['note'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Members List -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-users me-2"></i>Members (<?= count($members) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($members)): ?>
                    <p class="text-muted p-3 mb-0">No members.</p>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Rank</th>
                                <th>Honor</th>
                                <th>User Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td>#<?= $m['id'] ?></td>
                                    <td>
                                        <?= e($m['name']) ?>
                                        <?php if (intval($m['id']) === intval($guild['leader_character_id'])): ?>
                                            <span class="badge bg-warning text-dark ms-1">Leader</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= intval($m['level']) ?></span></td>
                                    <td><?= intval($m['guild_rank']) ?></td>
                                    <td><?= number_format(intval($m['honor'])) ?></td>
                                    <td>
                                        <a href="index.php?page=users&action=edit&id=<?= $m['user_id'] ?>"><?= e($m['user_email'] ?? '') ?></a>
                                    </td>
                                    <td>
                                        <a href="index.php?page=characters&action=edit&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">
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

    <!-- Quick Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Adjust Currency -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-coins me-2"></i>Adjust Currency</div>
            <div class="card-body">
                <form method="POST" action="index.php?page=guilds&action=adjustCurrency&id=<?= $guild['id'] ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <select name="currency_type" class="form-select">
                            <option value="game">Game Currency (current: <?= number_format(intval($guild['game_currency'])) ?>)</option>
                            <option value="premium">Premium Currency (current: <?= number_format(intval($guild['premium_currency'])) ?>)</option>
                        </select>
                    </div>
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

        <!-- Guild Stats -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Battle Stats</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Battles Attacked</td><td class="text-end"><?= intval($guild['battles_attacked']) ?></td></tr>
                    <tr><td>Battles Defended</td><td class="text-end"><?= intval($guild['battles_defended']) ?></td></tr>
                    <tr><td>Battles Won</td><td class="text-end text-success"><?= intval($guild['battles_won']) ?></td></tr>
                    <tr><td>Battles Lost</td><td class="text-end text-danger"><?= intval($guild['battles_lost']) ?></td></tr>
                    <tr><td>Artifacts Won</td><td class="text-end"><?= intval($guild['artifacts_won']) ?></td></tr>
                    <tr><td>Artifacts Owned</td><td class="text-end"><?= intval($guild['artifacts_owned_current']) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Delete -->
        <div class="card mb-3 border-danger">
            <div class="card-header bg-danger text-white"><i class="fas fa-skull-crossbones me-2"></i>Danger Zone</div>
            <div class="card-body">
                <a href="index.php?page=guilds&action=delete&id=<?= $guild['id'] ?>&<?= $GLOBALS['admin_config']['csrf']['token_name'] ?>=<?= csrf_token() ?>"
                   class="btn btn-danger w-100"
                   onclick="return confirm('DELETE this guild? All members will be removed. This CANNOT be undone!')">
                    <i class="fas fa-trash me-1"></i> Delete Guild
                </a>
            </div>
        </div>
    </div>
</div>
