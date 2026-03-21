<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Event Quests</h2>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show">
            <?= e($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Only 1 event can be active at a time.</strong> Activating a new event will automatically deactivate the current one.
        Changes modify <code>GameSettings.php</code> directly and clear the server cache.
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-dark">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?= (int)$totalCount ?></h3>
                    <small class="text-muted">Total Events</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-dark">
                <div class="card-body text-center">
                    <h3 class="<?= $activeCount > 0 ? 'text-success' : 'text-muted' ?>"><?= (int)$activeCount ?></h3>
                    <small class="text-muted">Currently Active</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Identifier</th>
                        <th>Objectives</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No event quests found in GameSettings</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $evt): ?>
                            <tr>
                                <td><strong><?= e($evt['name']) ?></strong></td>
                                <td><code><?= e($evt['identifier']) ?></code></td>
                                <td><?= (int)$evt['objectives'] ?></td>
                                <td><?= e($evt['start_date']) ?></td>
                                <td><?= e($evt['end_date']) ?></td>
                                <td>
                                    <?php if ($evt['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($evt['is_active']): ?>
                                        <form method="POST" action="index.php?page=events&action=deactivate" class="d-inline" onsubmit="return confirm('Deactivate this event?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="identifier" value="<?= e($evt['identifier']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="fas fa-power-off me-1"></i>Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" title="Activate" data-bs-toggle="modal" data-bs-target="#activateModal" data-identifier="<?= e($evt['identifier']) ?>" data-name="<?= e($evt['name']) ?>">
                                            <i class="fas fa-play me-1"></i>Activate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Activate Modal -->
<div class="modal fade" id="activateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <form method="POST" action="index.php?page=events&action=activate">
                <?= csrf_field() ?>
                <input type="hidden" name="identifier" id="modalIdentifier">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Activate Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Activating: <strong id="modalEventName"></strong></p>
                    <?php if ($activeCount > 0): ?>
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            There is already an active event. It will be deactivated automatically.
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-play me-1"></i>Activate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('activateModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('modalIdentifier').value = btn.getAttribute('data-identifier');
    document.getElementById('modalEventName').textContent = btn.getAttribute('data-name');
});
</script>
