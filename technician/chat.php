<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('technician');
$pdo = getDB(); $uid = $_SESSION['user_id'];
$techStmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id=?"); $techStmt->execute([$uid]); $tech = $techStmt->fetch();
if (!$tech) redirect('/technician/dashboard.php');
$tid = $tech['id'];

$cid = (int)get('id');

// Handle pesan atau close
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');
    if ($action === 'send') {
        $msg = trim(post('message'));
        if ($msg && $cid) {
            $pdo->prepare("INSERT INTO chat_messages (consultation_id,sender_id,message) VALUES (?,?,?)")->execute([$cid,$uid,$msg]);
            $pdo->prepare("UPDATE consultations SET status='answered',updated_at=NOW() WHERE id=? AND technician_id=?")->execute([$cid,$tid]);
            redirect('/technician/chat.php?id='.$cid);
        }
    } elseif ($action === 'close' && $cid) {
        $pdo->prepare("UPDATE consultations SET status='closed',updated_at=NOW() WHERE id=? AND technician_id=?")->execute([$cid,$tid]);
        redirect('/technician/chat.php','Konsultasi ditutup.','success');
    }
}

// Jika ada id, tampilkan chat spesifik
if ($cid) {
    $cons = $pdo->prepare("SELECT c.*,u.name AS user_name FROM consultations c JOIN users u ON c.user_id=u.id WHERE c.id=? AND c.technician_id=?");
    $cons->execute([$cid,$tid]); $cons = $cons->fetch();
    if (!$cons) { $cid = 0; }
    else {
        $pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE consultation_id=? AND sender_id!=?")->execute([$cid,$uid]);
        $messages = $pdo->prepare("SELECT m.*,u.name AS sender_name FROM chat_messages m JOIN users u ON m.sender_id=u.id WHERE consultation_id=? ORDER BY m.created_at ASC");
        $messages->execute([$cid]); $messages = $messages->fetchAll();
    }
}

// Daftar semua konsultasi
$allCons = $pdo->prepare("SELECT c.*,u.name AS user_name,(SELECT COUNT(*) FROM chat_messages WHERE consultation_id=c.id AND is_read=0 AND sender_id!=?) AS unread FROM consultations c JOIN users u ON c.user_id=u.id WHERE c.technician_id=? ORDER BY c.updated_at DESC");
$allCons->execute([$uid,$tid]); $allCons = $allCons->fetchAll();

pageHeader('Konsultasi','Konsultasi Masuk');
?>
<div class="row g-3" style="height:calc(100vh - 140px);">

<!-- List konsultasi -->
<div class="col-md-4">
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--sage-lt);">
        <span style="color:var(--sage);"><i class="bi bi-chat-dots me-2"></i>Konsultasi</span>
        <span class="badge bg-secondary"><?=count($allCons)?></span>
    </div>
    <div class="card-body p-0" style="overflow-y:auto;">
        <?php if(empty($allCons)): ?>
        <div class="text-center py-4 text-muted" style="font-size:.82rem;"><i class="bi bi-inbox d-block display-5 mb-2 opacity-25"></i>Belum ada konsultasi</div>
        <?php else: foreach($allCons as $c): $isActive=$c['id']==$cid; ?>
        <a href="chat.php?id=<?=$c['id']?>" style="text-decoration:none;color:inherit;">
        <div style="padding:.75rem 1rem;border-bottom:1px solid var(--border);background:<?=$isActive?'var(--sage-lt)':'#fff'?>;transition:.15s;" onmouseover="this.style.background='#FAFDF8'" onmouseout="this.style.background='<?=$isActive?'var(--sage-lt)':'#fff'?>'">
            <div class="d-flex justify-content-between align-items-start">
                <div style="font-weight:700;font-size:.83rem;"><?=e($c['user_name'])?></div>
                <?php if($c['unread']): ?><span class="badge" style="background:var(--sage);color:#fff;font-size:.65rem;"><?=$c['unread']?></span><?php endif; ?>
            </div>
            <div style="font-size:.75rem;color:var(--muted);"><?=e(limitStr($c['subject'],45))?></div>
            <div style="font-size:.68rem;color:var(--muted);margin-top:.1rem;"><?=date('d M H:i',strtotime($c['updated_at']))?></div>
        </div>
        </a>
        <?php endforeach; endif; ?>
    </div>
</div>
</div>

<!-- Chat Window -->
<div class="col-md-8">
<?php if (!$cid || !isset($cons)): ?>
<div class="card h-100 d-flex align-items-center justify-content-center text-center" style="color:var(--muted);">
    <div class="card-body">
        <i class="bi bi-chat-square display-2 d-block mb-3 opacity-20"></i>
        <h5 style="color:var(--muted);font-weight:600;">Pilih konsultasi di kiri</h5>
        <p style="font-size:.85rem;">Klik salah satu konsultasi untuk membaca dan membalas pesan.</p>
    </div>
</div>
<?php else: ?>
<div class="card h-100 d-flex flex-column">
    <!-- Header -->
    <div class="card-header d-flex align-items-center justify-content-between" style="background:var(--mint-lt);">
        <div>
            <span style="font-weight:700;"><?=e($cons['user_name'])?></span>
            <div style="font-size:.73rem;color:var(--muted);"><?=e($cons['subject'])?></div>
        </div>
        <?php if ($cons['status']!=='closed'): ?>
        <form method="POST" onsubmit="return confirm('Tutup konsultasi ini?')">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="close">
            <button class="btn btn-sm" style="background:#FEE2E2;color:#BE123C;border:none;border-radius:8px;font-size:.75rem;" type="submit">
                <i class="bi bi-x-circle me-1"></i>Tutup
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <div class="card-body p-3 flex-grow-1 overflow-auto" id="chatBox" style="max-height:340px;display:flex;flex-direction:column;gap:.55rem;">
        <?php foreach ($messages as $m): $isMe=$m['sender_id']==$uid; ?>
        <div style="display:flex;flex-direction:column;align-items:<?=$isMe?'flex-end':'flex-start'?>;">
            <?php if(!$isMe): ?><div style="font-size:.68rem;color:var(--muted);margin-bottom:.15rem;font-weight:600;"><?=e($m['sender_name'])?></div><?php endif; ?>
            <div style="max-width:75%;background:<?=$isMe?'#4D7C5F':'#E8F2EC'?>;color:<?=$isMe?'#fff':'#1A3A28'?>;border:<?=$isMe?'none':'1px solid #C5E0CF'?>;padding:.6rem .85rem;border-radius:<?=$isMe?'14px 14px 4px 14px':'14px 14px 14px 4px'?>;font-size:.83rem;line-height:1.55;word-break:break-word;">
                <?=nl2br(e($m['message']))?>
            </div>
            <div style="font-size:.65rem;color:var(--muted);margin-top:.15rem;"><?=date('H:i',strtotime($m['created_at']))?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Input -->
    <?php if($cons['status']!=='closed'): ?>
    <div class="p-3 border-top" style="border-color:var(--border)!important;">
        <form method="POST" class="d-flex gap-2">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="send">
            <input type="text" name="message" class="form-control" placeholder="Tulis balasan..." required autocomplete="off" style="border-radius:50px;font-size:.85rem;">
            <button type="submit" class="btn btn-primary" style="border-radius:50px;padding:.5rem 1rem;flex-shrink:0;">
                <i class="bi bi-send"></i>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

</div>
<script>
const cb = document.getElementById('chatBox');
if (cb) { cb.scrollTop = cb.scrollHeight; }
if (<?=$cid?>) setTimeout(() => location.reload(), 8000);
</script>
<?php pageFooter(); ?>
