<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');
$pdo = getDB(); $uid = $_SESSION['user_id']; $cid = (int)get('id');

$cons = $pdo->prepare("SELECT c.*,u.name AS tech_name,u.phone AS tech_phone,t.keahlian FROM consultations c JOIN technicians t ON c.technician_id=t.id JOIN users u ON t.user_id=u.id WHERE c.id=? AND c.user_id=?");
$cons->execute([$cid,$uid]); $cons = $cons->fetch();
if (!$cons) redirect('/user/chat/index.php','Konsultasi tidak ditemukan.');

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $msg = trim(post('message'));
    if ($msg && $cons['status'] !== 'closed') {
        $pdo->prepare("INSERT INTO chat_messages (consultation_id,sender_id,message) VALUES (?,?,?)")->execute([$cid,$uid,$msg]);
        $pdo->prepare("UPDATE consultations SET updated_at=NOW() WHERE id=?")->execute([$cid]);
        redirect('/user/chat/chat.php?id='.$cid);
    }
}

// Mark messages as read
$pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE consultation_id=? AND sender_id!=?")->execute([$cid,$uid]);

$messages = $pdo->prepare("SELECT m.*,u.name AS sender_name FROM chat_messages m JOIN users u ON m.sender_id=u.id WHERE consultation_id=? ORDER BY m.created_at ASC");
$messages->execute([$cid]); $messages = $messages->fetchAll();

pageHeader('Chat Konsultasi', e($cons['subject']));
?>
<div class="row justify-content-center"><div class="col-lg-7">

<!-- Chat Header -->
<div class="card mb-3">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#A7D3B5,#4D7C5F);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0;">
            <?=strtoupper(substr($cons['tech_name'],0,1))?>
        </div>
        <div class="flex-grow-1">
            <div style="font-weight:700;"><?=e($cons['tech_name'])?></div>
            <div style="font-size:.75rem;color:var(--muted);"><?=e($cons['keahlian'])?></div>
        </div>
        <div class="d-flex gap-2">
            <?php if ($cons['tech_phone']): ?>
            <a href="https://wa.me/62<?=ltrim($cons['tech_phone'],'0')?>" target="_blank"
               class="btn btn-sm" style="background:#25D366;color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:600;">
                <i class="bi bi-whatsapp me-1"></i>WhatsApp
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;border-radius:8px;">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>
</div>

<!-- Topik -->
<div class="alert py-2 px-3 mb-3" style="background:var(--sage-lt);border:1px solid var(--border);font-size:.82rem;color:var(--sage);border-radius:12px;">
    <i class="bi bi-chat-square-text me-1"></i><strong>Topik:</strong> <?=e($cons['subject'])?>
</div>

<!-- Chat Messages -->
<div class="card mb-3">
    <div class="card-body p-3" id="chatBox" style="max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:.6rem;">
        <?php if (empty($messages)): ?>
        <div class="text-center text-muted py-4" style="font-size:.85rem;">
            <i class="bi bi-chat d-block display-5 mb-2 opacity-25"></i>
            Belum ada pesan. Kirim pesan pertamamu!
        </div>
        <?php else: foreach ($messages as $m): $isMe = $m['sender_id'] == $uid; ?>
        <div style="display:flex;flex-direction:column;align-items:<?=$isMe?'flex-end':'flex-start'?>;">
            <?php if(!$isMe): ?>
            <div style="font-size:.7rem;color:var(--muted);margin-bottom:.2rem;font-weight:600;"><?=e($m['sender_name'])?></div>
            <?php endif; ?>
            <div style="max-width:78%;background:<?=$isMe?'#4D7C5F':'#E8F2EC'?>;color:<?=$isMe?'#fff':'#1A3A28'?>;border:<?=$isMe?'none':'1px solid #C5E0CF'?>;padding:.65rem .9rem;border-radius:<?=$isMe?'14px 14px 4px 14px':'14px 14px 14px 4px'?>;font-size:.85rem;line-height:1.6;word-break:break-word;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <?=nl2br(e($m['message']))?>
            </div>
            <div style="font-size:.67rem;color:var(--muted);margin-top:.2rem;"><?=date('H:i, d M',strtotime($m['created_at']))?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Input Chat -->
<?php if ($cons['status'] !== 'closed'): ?>
<div class="card">
    <div class="card-body p-3">
        <form method="POST" class="d-flex gap-2">
            <?php csrfField(); ?>
            <input type="text" name="message" class="form-control" placeholder="Ketik pesan..." required autocomplete="off" style="border-radius:50px;">
            <button type="submit" class="btn btn-primary" style="border-radius:50px;padding:.5rem 1.1rem;flex-shrink:0;">
                <i class="bi bi-send"></i>
            </button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert text-center" style="background:var(--sage-lt);color:var(--sage);border-radius:12px;font-size:.85rem;">
    <i class="bi bi-lock me-1"></i>Konsultasi ini sudah ditutup.
    <a href="create.php" style="color:var(--sage);font-weight:700;" class="ms-2">Buka konsultasi baru?</a>
</div>
<?php endif; ?>

</div></div>

<script>
// Auto-scroll ke bawah
const chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

// Auto-refresh setiap 8 detik untuk cek pesan baru
setTimeout(() => location.reload(), 8000);
</script>
<?php pageFooter(); ?>
