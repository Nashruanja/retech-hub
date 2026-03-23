<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB(); $uid = $_SESSION['user_id'];
$consultations = $pdo->prepare("SELECT c.*,u.name AS tech_name,t.keahlian,
    (SELECT COUNT(*) FROM chat_messages WHERE consultation_id=c.id AND is_read=0 AND sender_id!=?) AS unread
    FROM consultations c JOIN technicians t ON c.technician_id=t.id JOIN users u ON t.user_id=u.id
    WHERE c.user_id=? ORDER BY c.updated_at DESC");
$consultations->execute([$uid,$uid]); $consultations = $consultations->fetchAll();

pageHeader('Konsultasi','Chat Konsultasi');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?=count($consultations)?> konsultasi</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Konsultasi Baru</a>
</div>

<?php if (empty($consultations)): ?>
<div class="card text-center py-5"><div class="card-body">
    <div style="font-size:3.5rem;margin-bottom:1rem;">💬</div>
    <h5>Belum ada konsultasi</h5>
    <p class="text-muted" style="font-size:.87rem;">Tanya langsung ke teknisi sebelum booking servis.</p>
    <a href="create.php" class="btn btn-primary mt-1"><i class="bi bi-chat-dots me-1"></i>Mulai Konsultasi</a>
</div></div>
<?php else: ?>
<div class="card"><div class="card-body p-0">
<?php foreach ($consultations as $c): ?>
<a href="chat.php?id=<?=$c['id']?>" style="text-decoration:none;color:inherit;">
<div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom <?=$c['unread']?'':'?'?>" style="<?=$c['unread']?'background:#FAFDF8;':''?> transition:.15s;" onmouseover="this.style.background='#F4F8F5'" onmouseout="this.style.background='<?=$c['unread']?'#FAFDF8':'#fff'?>'">
    <div style="width:44px;height:44px;background:linear-gradient(135deg,#A7D3B5,#4D7C5F);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0;">
        <?=strtoupper(substr($c['tech_name'],0,1))?>
    </div>
    <div class="flex-grow-1">
        <div style="font-weight:700;font-size:.88rem;"><?=e($c['tech_name'])?></div>
        <div style="font-size:.76rem;color:var(--muted);"><?=e($c['keahlian'])?></div>
        <div style="font-size:.8rem;color:var(--muted);margin-top:.15rem;"><?=e(limitStr($c['subject'],55))?></div>
    </div>
    <div class="text-end flex-shrink-0">
        <?php if ($c['unread']): ?>
        <span class="badge" style="background:var(--sage);color:#fff;font-size:.7rem;"><?=$c['unread']?> baru</span>
        <?php endif; ?>
        <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;"><?=date('d M',strtotime($c['updated_at']))?></div>
        <?php
        $statusBg=['open'=>'#E8F5EE','answered'=>'#ECFDF5','closed'=>'#F4F8F5'];
        $statusClr=['open'=>'#2D5016','answered'=>'#065F46','closed'=>'var(--muted)'];
        $statusLbl=['open'=>'Menunggu','answered'=>'Dijawab','closed'=>'Selesai'];
        ?>
        <span class="badge mt-1" style="background:<?=$statusBg[$c['status']]??'#F4F8F5'?>;color:<?=$statusClr[$c['status']]??'var(--muted)'?>;font-size:.68rem;"><?=$statusLbl[$c['status']]??$c['status']?></span>
    </div>
</div>
</a>
<?php endforeach; ?>
</div></div>
<?php endif; ?>
<?php pageFooter(); ?>
