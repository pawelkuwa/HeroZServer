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
    <h2><i class="fas fa-pen me-2"></i>Compose Message</h2>
    <a href="index.php?page=messages" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Messages</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="index.php?page=messages&action=compose">
                    <?= csrf_field() ?>
                    <input type="hidden" name="send_message" value="1">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From Character ID</label>
                            <input type="number" name="from_id" class="form-control" value="0" min="0">
                            <small class="text-muted">0 = System message</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Character ID</label>
                            <input type="number" name="to_id" class="form-control" value="" min="1" id="toIdField">
                            <small class="text-muted">Required unless broadcasting</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="broadcast" value="1" id="broadcastCheck">
                            <label class="form-check-label" for="broadcastCheck">
                                <strong class="text-warning">Broadcast to ALL characters</strong>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" required maxlength="80" placeholder="Message subject">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="6" required placeholder="Message content..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirmSend(this)">
                        <i class="fas fa-paper-plane me-1"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Help</div>
            <div class="card-body">
                <p><strong>Single Message:</strong> Enter a specific character ID in the "To" field.</p>
                <p><strong>Broadcast:</strong> Check the broadcast box to send the message to every character in the game. The "To" field will be ignored.</p>
                <p class="text-warning mb-0"><strong>Warning:</strong> Broadcasts create one message per character and cannot be easily undone.</p>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSend(btn) {
    var bc = document.getElementById('broadcastCheck');
    if (bc && bc.checked) {
        if (!confirm('Send this message to ALL characters? This cannot be undone!')) {
            return false;
        }
    }
    setTimeout(function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';
    }, 50);
    return true;
}
</script>
