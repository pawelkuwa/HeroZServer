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
        <i class="fas fa-user-ninja me-2"></i>Edit Character: <?= e($character['name']) ?>
        <span class="badge bg-info ms-2">Lv. <?= intval($character['level']) ?></span>
    </h2>
    <div>
        <a href="index.php?page=users&action=edit&id=<?= $character['user_id'] ?>" class="btn btn-outline-info me-1">
            <i class="fas fa-user"></i> Owner (<?= e($character['user_email'] ?? '') ?>)
        </a>
        <a href="index.php?page=characters" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="index.php?page=characters&action=edit&id=<?= $character['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="save_character" value="1">

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-basic">Basic Info</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-currency">Currencies</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-stats">Stats</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-energy">Energy</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-appearance">Appearance</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-guild">Guild</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-basic">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($character['name']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="m" <?= $character['gender'] === 'm' ? 'selected' : '' ?>>Male</option>
                                <option value="f" <?= $character['gender'] === 'f' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Level</label>
                            <input type="number" name="level" class="form-control" value="<?= intval($character['level']) ?>" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">XP</label>
                            <input type="number" name="xp" class="form-control" value="<?= intval($character['xp']) ?>" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= e($character['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-currency">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Game Currency</label>
                            <input type="number" name="game_currency" class="form-control" value="<?= intval($character['game_currency']) ?>" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Honor</label>
                            <input type="number" name="honor" class="form-control" value="<?= intval($character['honor']) ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-stats">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Stat Points Available</label>
                        <input type="number" name="stat_points_available" class="form-control" style="max-width:200px" value="<?= intval($character['stat_points_available']) ?>" min="0">
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Stat</th>
                                    <th>Base</th>
                                    <th>Bought</th>
                                    <th>Trained</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (['stamina', 'strength', 'critical_rating', 'dodge_rating'] as $stat): ?>
                                    <tr>
                                        <td><strong><?= ucwords(str_replace('_', ' ', $stat)) ?></strong></td>
                                        <td><input type="number" name="stat_base_<?= $stat ?>" class="form-control form-control-sm" value="<?= intval($character["stat_base_{$stat}"]) ?>" min="0" style="width:100px"></td>
                                        <td><input type="number" name="stat_bought_<?= $stat ?>" class="form-control form-control-sm" value="<?= intval($character["stat_bought_{$stat}"]) ?>" min="0" style="width:100px"></td>
                                        <td><input type="number" name="stat_trained_<?= $stat ?>" class="form-control form-control-sm" value="<?= intval($character["stat_trained_{$stat}"]) ?>" min="0" style="width:100px"></td>
                                        <td><strong><?= intval($character["stat_base_{$stat}"]) + intval($character["stat_bought_{$stat}"]) + intval($character["stat_trained_{$stat}"]) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-energy">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Quest Energy</label>
                            <input type="number" name="quest_energy" class="form-control" value="<?= intval($character['quest_energy']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Quest Energy</label>
                            <input type="number" name="max_quest_energy" class="form-control" value="<?= intval($character['max_quest_energy']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duel Stamina</label>
                            <input type="number" name="duel_stamina" class="form-control" value="<?= intval($character['duel_stamina']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Duel Stamina</label>
                            <input type="number" name="max_duel_stamina" class="form-control" value="<?= intval($character['max_duel_stamina']) ?>" min="0">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Training Count</label>
                            <input type="number" name="training_count" class="form-control" value="<?= intval($character['training_count']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Training Count</label>
                            <input type="number" name="max_training_count" class="form-control" value="<?= intval($character['max_training_count']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">League Stamina</label>
                            <input type="number" name="league_stamina" class="form-control" value="<?= intval($character['league_stamina']) ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max League Stamina</label>
                            <input type="number" name="max_league_stamina" class="form-control" value="<?= intval($character['max_league_stamina']) ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-appearance">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <?php
                        $appearanceFields = [
                            'appearance_skin_color', 'appearance_hair_color', 'appearance_hair_type',
                            'appearance_head_type', 'appearance_eyes_type', 'appearance_eyebrows_type',
                            'appearance_nose_type', 'appearance_mouth_type', 'appearance_facial_hair_type',
                            'appearance_decoration_type',
                        ];
                        foreach ($appearanceFields as $field):
                            $label = ucwords(str_replace(['appearance_', '_'], ['', ' '], $field));
                        ?>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><?= $label ?></label>
                                <input type="number" name="<?= $field ?>" class="form-control" value="<?= intval($character[$field]) ?>" min="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-guild">
            <div class="card mb-4">
                <div class="card-body">
                    <?php if ($guild): ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Guild</label>
                                <p>
                                    <a href="index.php?page=guilds&action=edit&id=<?= $guild['id'] ?>"><?= e($guild['name']) ?></a>
                                    <span class="text-muted">(#<?= $guild['id'] ?>)</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Guild Rank</label>
                                <p><?= intval($character['guild_rank']) ?></p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Joined</label>
                                <p><?= $character['ts_guild_joined'] ? date('Y-m-d H:i', $character['ts_guild_joined']) : 'N/A' ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">This character is not in a guild.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i> Save All Changes</button>
</form>

<!-- Quick Actions -->
<hr class="my-4">
<h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
<div class="row mb-4">
    <div class="col-md-4 mb-2">
        <form method="POST" action="index.php?page=characters&action=setLevel&id=<?= $character['id'] ?>">
            <?= csrf_field() ?>
            <div class="input-group">
                <input type="number" name="level" class="form-control" placeholder="Level" min="1" max="999" value="<?= intval($character['level']) ?>">
                <button type="submit" class="btn btn-info" onclick="return confirm('Set level?')">Set Level</button>
            </div>
        </form>
    </div>
    <div class="col-md-4 mb-2">
        <form method="POST" action="index.php?page=characters&action=adjustCurrency&id=<?= $character['id'] ?>">
            <?= csrf_field() ?>
            <div class="input-group">
                <input type="number" name="amount" class="form-control" placeholder="Amount">
                <select name="mode" class="form-select" style="max-width:100px">
                    <option value="add">Add</option>
                    <option value="set">Set</option>
                </select>
                <button type="submit" class="btn btn-warning">Currency</button>
            </div>
        </form>
    </div>
    <div class="col-md-4 mb-2">
        <div class="d-flex gap-1 w-100">
            <form method="POST" action="index.php?page=characters&action=maxStats&id=<?= $character['id'] ?>" class="flex-fill">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success w-100" onclick="return confirm('Max all base stats to 9999?')">
                    <i class="fas fa-arrow-up"></i> Max Stats
                </button>
            </form>
            <form method="POST" action="index.php?page=characters&action=maxCurrency&id=<?= $character['id'] ?>" class="flex-fill">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Max game currency?')">
                    <i class="fas fa-coins"></i> Max $
                </button>
            </form>
            <form method="POST" action="index.php?page=characters&action=maxLevel&id=<?= $character['id'] ?>" class="flex-fill">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-info w-100" onclick="return confirm('Max level to 999?')">
                    <i class="fas fa-star"></i> Max Lv
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$typeMap = [1=>'Mask',2=>'Cape',3=>'Suit',4=>'Belt',5=>'Boots',6=>'Weapon',7=>'Gadget',8=>'Missiles',9=>'Sidekick',10=>'Surprise',11=>'Reskill'];
$qualityMap = [1=>['name'=>'Common','color'=>'secondary'],2=>['name'=>'Rare','color'=>'primary'],3=>['name'=>'Epic','color'=>'purple']];
$totalItems = count($equipped) + count($bagItems) + count($shopItems) + count($bankItems);

function renderItemRow($item, $typeMap, $qualityMap, $extra = '') {
    $q = $qualityMap[$item['quality']] ?? ['name'=>'?','color'=>'secondary'];
    $badgeColor = $q['color'] === 'purple' ? 'style="background-color:#6f42c1"' : '';
    $badgeClass = $q['color'] !== 'purple' ? "bg-{$q['color']}" : '';
    echo '<tr>';
    echo '<td>#' . $item['id'] . '</td>';
    if ($extra) echo '<td><span class="badge bg-info">' . e($extra) . '</span></td>';
    echo '<td><small>' . e($item['identifier'] ?? '') . '</small></td>';
    echo '<td>' . ($typeMap[$item['type']] ?? 'Unknown') . '</td>';
    echo '<td><span class="badge ' . $badgeClass . '" ' . $badgeColor . '>' . $q['name'] . '</span></td>';
    echo '<td>' . intval($item['item_level']) . '</td>';
    echo '<td><small>';
    echo 'STA:' . intval($item['stat_stamina']);
    echo ' STR:' . intval($item['stat_strength']);
    echo ' CRI:' . intval($item['stat_critical_rating']);
    echo ' DOD:' . intval($item['stat_dodge_rating']);
    echo ' DMG:' . intval($item['stat_weapon_damage']);
    echo '</small></td>';
    echo '</tr>';
}

function renderItemTable($items, $typeMap, $qualityMap, $hasSlot = false) {
    if (empty($items)) {
        echo '<p class="text-muted p-3 mb-0">None.</p>';
        return;
    }
    echo '<table class="table table-hover table-sm mb-0"><thead><tr>';
    echo '<th>ID</th>';
    if ($hasSlot) echo '<th>Slot</th>';
    echo '<th>Identifier</th><th>Type</th><th>Quality</th><th>Level</th><th>Stats</th>';
    echo '</tr></thead><tbody>';
    foreach ($items as $item) {
        renderItemRow($item, $typeMap, $qualityMap, $hasSlot ? ($item['_slot'] ?? '') : '');
    }
    echo '</tbody></table>';
}
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-gem me-2"></i>Items (<?= $totalItems ?>)</span>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#giveItemModal">
            <i class="fas fa-gift"></i> Give Item
        </button>
    </div>
    <div class="card-body p-0">
        <div class="px-3 pt-3 pb-1"><h6><i class="fas fa-shield-alt me-1"></i> Equipped (<?= count($equipped) ?>)</h6></div>
        <?php renderItemTable($equipped, $typeMap, $qualityMap, true); ?>

        <div class="px-3 pt-3 pb-1 border-top"><h6><i class="fas fa-suitcase me-1"></i> Bag (<?= count($bagItems) ?>)</h6></div>
        <?php renderItemTable($bagItems, $typeMap, $qualityMap); ?>

        <div class="px-3 pt-3 pb-1 border-top"><h6><i class="fas fa-store me-1"></i> Shop (<?= count($shopItems) ?>)</h6></div>
        <?php renderItemTable($shopItems, $typeMap, $qualityMap); ?>

        <div class="px-3 pt-3 pb-1 border-top"><h6><i class="fas fa-vault me-1"></i> Bank (<?= count($bankItems) ?>)</h6></div>
        <?php renderItemTable($bankItems, $typeMap, $qualityMap); ?>
    </div>
</div>

<div class="modal fade" id="giveItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fas fa-gift me-2"></i>Give Item to <?= e($character['name']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=characters&action=giveItem&id=<?= $character['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="give_item" value="1">
                <input type="hidden" name="identifier" id="giveItemIdentifier" value="">
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input type="text" id="giveItemSearch" class="form-control" placeholder="Search identifier...">
                        </div>
                        <div class="col-md-2">
                            <select name="destination" class="form-select">
                                <option value="bag">Bag</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="giveItemType" class="form-select">
                                <option value="">All Types</option>
                                <option value="1">Mask</option>
                                <option value="2">Cape</option>
                                <option value="3">Suit</option>
                                <option value="4">Belt</option>
                                <option value="5">Boots</option>
                                <option value="6">Weapon</option>
                                <option value="7">Gadget</option>
                                <option value="8">Missiles</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="giveItemQuality" class="form-select">
                                <option value="">All Qualities</option>
                                <option value="1">Common</option>
                                <option value="2">Rare</option>
                                <option value="3">Epic</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Selected:</strong> <span id="giveItemSelected" class="text-muted">None</span>
                    </div>
                    <div id="giveItemList" style="max-height:350px; overflow-y:auto;" class="table-dark-custom">
                        <table class="table table-hover table-sm mb-0">
                            <thead><tr><th>Identifier</th><th>Type</th><th>Quality</th><th>Req. Lv</th></tr></thead>
                            <tbody id="giveItemBody"></tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Stats and prices are generated automatically based on character level (Lv. <?= intval($character['level']) ?>).
                    </p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="giveItemSubmit" disabled>
                        <i class="fas fa-gift me-1"></i> Give Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    var items = <?= json_encode($itemTemplates ?? []) ?>;
    var types = {1:'Mask',2:'Cape',3:'Suit',4:'Belt',5:'Boots',6:'Weapon',7:'Gadget',8:'Missiles',9:'Sidekick',10:'Surprise',11:'Reskill'};
    var quals = {1:['Common','secondary'],2:['Rare','primary'],3:['Epic','purple']};
    var body = document.getElementById('giveItemBody');
    var selected = null;

    function render(){
        var search = document.getElementById('giveItemSearch').value.toLowerCase();
        var typeF = document.getElementById('giveItemType').value;
        var qualF = document.getElementById('giveItemQuality').value;
        var html = '', count = 0;
        for(var i=0; i<items.length && count < 100; i++){
            var it = items[i];
            if(search && it.identifier.toLowerCase().indexOf(search) === -1) continue;
            if(typeF && it.type != typeF) continue;
            if(qualF && it.quality != qualF) continue;
            var qInfo = quals[it.quality] || ['?','secondary'];
            var cls = (selected === it.identifier) ? ' class="table-active"' : '';
            var badgeStyle = qInfo[1]==='purple' ? ' style="background-color:#6f42c1"' : '';
            var badgeClass = qInfo[1]!=='purple' ? 'bg-'+qInfo[1] : '';
            html += '<tr'+cls+' data-id="'+it.identifier+'" style="cursor:pointer">'
                + '<td><code>'+it.identifier+'</code></td>'
                + '<td>'+(types[it.type]||'?')+'</td>'
                + '<td><span class="badge '+badgeClass+'"'+badgeStyle+'>'+qInfo[0]+'</span></td>'
                + '<td>'+it.required_level+'</td></tr>';
            count++;
        }
        if(!html) html = '<tr><td colspan="4" class="text-center text-muted py-3">No items match.</td></tr>';
        body.innerHTML = html;
        body.querySelectorAll('tr[data-id]').forEach(function(row){
            row.addEventListener('click', function(){
                selected = this.getAttribute('data-id');
                document.getElementById('giveItemIdentifier').value = selected;
                document.getElementById('giveItemSelected').innerHTML = '<code>'+selected+'</code>';
                document.getElementById('giveItemSubmit').disabled = false;
                render();
            });
        });
    }

    document.getElementById('giveItemSearch').addEventListener('input', render);
    document.getElementById('giveItemType').addEventListener('change', render);
    document.getElementById('giveItemQuality').addEventListener('change', render);
    document.getElementById('giveItemModal').addEventListener('shown.bs.modal', render);
})();
</script>
