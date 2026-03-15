<?php if (!defined('IN_ENGINE')) exit; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-gem me-2"></i>Item Catalog <span class="badge bg-secondary"><?= $total ?> / <?= $totalAll ?></span></h2>
</div>

<form method="GET" class="mb-4">
    <input type="hidden" name="page" value="items">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Search Identifier</label>
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="e.g. mask_ninja">
        </div>
        <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="0">All Types</option>
                <?php foreach ($typeMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterType == $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Quality</label>
            <select name="quality" class="form-select">
                <option value="0">All Qualities</option>
                <?php foreach ($qualityMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filterQuality == $k ? 'selected' : '' ?>><?= $v['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Pattern</label>
            <select name="pattern" class="form-select">
                <option value="">All Patterns</option>
                <?php foreach ($patterns as $p): ?>
                    <option value="<?= e($p) ?>" <?= $filterPattern === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
            <a href="index.php?page=items" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="table-dark-custom">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Identifier</th>
                    <th>Type</th>
                    <th>Quality</th>
                    <th>Req. Level</th>
                    <th>Pattern</th>
                    <th>Set</th>
                    <th>Sew Price</th>
                    <th>Flags</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No items match your filters.</td></tr>
                <?php else: ?>
                    <?php
                    $rowNum = ($page - 1) * 50;
                    foreach ($templates as $t):
                        $rowNum++;
                        $q = $qualityMap[$t['quality']] ?? ['name' => '?', 'color' => 'secondary'];
                        $badgeStyle = $q['color'] === 'purple' ? 'style="background-color:#6f42c1"' : '';
                        $badgeClass = $q['color'] !== 'purple' ? "bg-{$q['color']}" : '';
                    ?>
                        <tr>
                            <td class="text-muted"><?= $rowNum ?></td>
                            <td><code><?= e($t['identifier']) ?></code></td>
                            <td><?= $typeMap[$t['type']] ?? 'Unknown' ?></td>
                            <td><span class="badge <?= $badgeClass ?>" <?= $badgeStyle ?>><?= $q['name'] ?></span></td>
                            <td><?= $t['required_level'] ?></td>
                            <td>
                                <?php if ($t['item_pattern']): ?>
                                    <a href="index.php?page=items&pattern=<?= urlencode($t['item_pattern']) ?>" class="text-decoration-none">
                                        <?= e($t['item_pattern']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['item_set_identifier']): ?>
                                    <span class="badge bg-info"><?= e($t['item_set_identifier']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['sew_price'] > 0): ?>
                                    <span class="text-warning"><?= number_format($t['sew_price']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['is_event']): ?>
                                    <span class="badge bg-warning text-dark">Event</span>
                                <?php endif; ?>
                                <?php if ($t['is_license']): ?>
                                    <span class="badge bg-info">License</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=items&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&type=<?= $filterType ?>&quality=<?= $filterQuality ?>&pattern=<?= urlencode($filterPattern) ?>">Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="index.php?page=items&p=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= $filterType ?>&quality=<?= $filterQuality ?>&pattern=<?= urlencode($filterPattern) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="index.php?page=items&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&type=<?= $filterType ?>&quality=<?= $filterQuality ?>&pattern=<?= urlencode($filterPattern) ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="mt-3">
    <div class="alert alert-info small mb-0">
        <i class="fas fa-info-circle me-1"></i>
        This catalog shows all <?= $totalAll ?> item templates from <code>GameSettings</code>.
        Stats (Stamina, Strength, etc.) and prices are generated server-side when items are created, based on character level and randomization.
        To give an item to a character, go to <strong>Characters &gt; Edit &gt; Give Item</strong>.
    </div>
</div>
