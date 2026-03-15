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

<h2 class="mb-4"><i class="fas fa-cogs me-2"></i>Server Configuration</h2>

<div class="row">
    <!-- Server Info -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-server me-2"></i>Server Info</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                        <tr><td class="fw-bold">PHP Version</td><td><?= e($info['php_version']) ?></td></tr>
                        <tr><td class="fw-bold">PHP SAPI</td><td><?= e($info['php_sapi']) ?></td></tr>
                        <tr><td class="fw-bold">Server Software</td><td><?= e($info['server_software']) ?></td></tr>
                        <tr><td class="fw-bold">MySQL Version</td><td><?= e($info['mysql_version']) ?></td></tr>
                        <tr><td class="fw-bold">Operating System</td><td><?= e($info['os']) ?></td></tr>
                        <tr><td class="fw-bold">Memory Limit</td><td><?= e($info['memory_limit']) ?></td></tr>
                        <tr><td class="fw-bold">Max Execution Time</td><td><?= e($info['max_execution']) ?>s</td></tr>
                        <tr><td class="fw-bold">Upload Max Filesize</td><td><?= e($info['upload_max']) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Database Info -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-database me-2"></i>Database Info</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                        <tr><td class="fw-bold">Database Name</td><td><?= e($info['db_name']) ?></td></tr>
                        <tr><td class="fw-bold">Host</td><td><?= e($info['db_host']) ?></td></tr>
                        <tr><td class="fw-bold">Table Prefix</td><td><?= e($info['db_prefix']) ?: '<span class="text-muted">None</span>' ?></td></tr>
                    </tbody>
                </table>
                <hr class="my-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th colspan="2" class="text-center bg-dark">Record Counts</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="fw-bold">Users</td><td><span class="badge bg-primary"><?= number_format($dbStats['users']) ?></span></td></tr>
                        <tr><td class="fw-bold">Characters</td><td><span class="badge bg-info"><?= number_format($dbStats['characters']) ?></span></td></tr>
                        <tr><td class="fw-bold">Guilds</td><td><span class="badge bg-success"><?= number_format($dbStats['guilds']) ?></span></td></tr>
                        <tr><td class="fw-bold">Items</td><td><span class="badge bg-warning text-dark"><?= number_format($dbStats['items']) ?></span></td></tr>
                        <tr><td class="fw-bold">Messages</td><td><span class="badge bg-secondary"><?= number_format($dbStats['messages']) ?></span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- CLI Tools -->
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-terminal me-2"></i>CLI Tools</div>
            <div class="card-body">
                <?php if (!empty($_SESSION['script_output'])): ?>
                    <pre class="bg-black text-success p-3 rounded mb-3" style="max-height:300px; overflow-y:auto; font-size:0.85rem;"><?= e($_SESSION['script_output']) ?></pre>
                    <?php unset($_SESSION['script_output']); ?>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h6><i class="fas fa-dragon me-1"></i> World Boss</h6>
                                <form method="POST" action="index.php?page=config&action=runScript" class="d-flex gap-2 mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="script" value="worldboss">
                                    <select name="arg" class="form-select form-select-sm" style="width:auto">
                                        <option value="check">Check</option>
                                        <option value="spawn">Spawn</option>
                                        <option value="process">Process</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Run</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h6><i class="fas fa-trophy me-1"></i> Tournament</h6>
                                <form method="POST" action="index.php?page=config&action=runScript" class="d-flex gap-2 mt-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="script" value="tournament">
                                    <select name="arg" class="form-select form-select-sm" style="width:auto">
                                        <option value="status">Status</option>
                                        <option value="start">Start</option>
                                        <option value="end">End</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-info">Run</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Cache Management -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-broom me-2"></i>Cache Management</div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Cache files:</strong> <?= $cacheCount ?> file(s)
                    <br>
                    <strong>Total size:</strong> <?= number_format($cacheSize / 1024, 1) ?> KB
                </p>
                <form method="POST" action="index.php?page=config&action=edit">
                    <?= csrf_field() ?>
                    <input type="hidden" name="clear_cache" value="1">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Clear all cache files?')">
                        <i class="fas fa-broom me-1"></i> Clear Cache
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Server Config (read-only) -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-file-code me-2"></i>Server Config (Read-Only)</div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($configValues) && is_array($configValues)): ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            function renderConfigRows(array $data, string $prefix = ''): void {
                                foreach ($data as $key => $value) {
                                    $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
                                    if (is_array($value)) {
                                        renderConfigRows($value, $fullKey);
                                    } elseif (is_object($value)) {
                                        echo '<tr>';
                                        echo '<td><code>' . e($fullKey) . '</code></td>';
                                        echo '<td><em class="text-muted">{object}</em></td>';
                                        echo '</tr>';
                                    } else {
                                        $display = $value;
                                        if (stripos($fullKey, 'password') !== false || stripos($fullKey, 'secret') !== false || stripos($fullKey, 'key') !== false) {
                                            $display = '********';
                                        }
                                        echo '<tr>';
                                        echo '<td><code>' . e($fullKey) . '</code></td>';
                                        echo '<td>' . e((string)$display) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            }
                            renderConfigRows($configValues);
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">Server config not available or not in expected format.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
