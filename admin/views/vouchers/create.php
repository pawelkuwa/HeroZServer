<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus me-2"></i>Create Voucher</h2>
        <a href="index.php?page=vouchers" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show">
            <?= e($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card bg-dark">
        <div class="card-body">
            <form method="POST" action="index.php?page=vouchers&action=create">
                <?= csrf_field() ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. WELCOME2026" style="text-transform: uppercase">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Uses</label>
                        <input type="number" name="uses_max" class="form-control" value="100" min="0">
                        <small class="text-muted">0 = unlimited</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Min Level</label>
                        <input type="number" name="min_level" class="form-control" value="0" min="0">
                        <small class="text-muted">0 = no requirement</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" name="ts_start" class="form-control">
                        <small class="text-muted">Leave empty for immediate</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="datetime-local" name="ts_end" class="form-control">
                        <small class="text-muted">Leave empty for no expiry</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Locale Restriction</label>
                        <select name="locale" class="form-select">
                            <option value="">All locales</option>
                            <option value="pt_BR">Portuguese (BR)</option>
                            <option value="en_GB">English</option>
                            <option value="pl_PL">Polish</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Specific User ID</label>
                        <input type="number" name="user_id" class="form-control" value="0" min="0">
                        <small class="text-muted">0 = any user</small>
                    </div>
                </div>

                <hr class="border-secondary">
                <h5 class="mb-3">Rewards</h5>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-coins text-warning me-1"></i>Gold</label>
                        <input type="number" name="game_currency" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-gem text-info me-1"></i>Donuts</label>
                        <input type="number" name="premium_currency" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-chart-bar text-success me-1"></i>Stat Points</label>
                        <input type="number" name="stat_points" class="form-control" value="0" min="0">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-bolt text-warning me-1"></i>Quest Energy</label>
                        <input type="number" name="quest_energy" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-dumbbell text-danger me-1"></i>Training Sessions</label>
                        <input type="number" name="training_sessions" class="form-control" value="0" min="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Create Voucher</button>
            </form>
        </div>
    </div>
</div>
