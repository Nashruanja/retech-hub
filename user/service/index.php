<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');
$pdo = getDB(); $uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT sr.*,d.device_name,d.brand,u2.name AS tech_name FROM service_requests sr JOIN devices d ON sr.device_id=d.id LEFT JOIN technicians t ON sr.technician_id=t.id LEFT JOIN users u2 ON t.user_id=u2.id WHERE d.user_id=? ORDER BY sr.created_at DESC");
$stmt->execute([$uid]); $services = $stmt->fetchAll();
pageHeader('Riwayat Servis','Riwayat Servis');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Semua permintaan servis Anda</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Booking Baru</a>
</div>
<div class="card">
<div class="card-body p-0">
<?php if(empty($services)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x display-3 d-block mb-3 opacity-25"></i>
    <h5>Belum ada riwayat servis</h5>
    <a href="create.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-calendar-plus me-1"></i>Booking Servis</a>
</div>
<?php else: foreach($services as $s): ?>
<div class="d-flex align-items-center gap-3 p-3 border-bottom">
    <div style="font-size:1.5rem;">🔧</div>
    <div class="flex-grow-1">
        <div style="font-weight:600;font-size:.88rem;"><?= e($s['device_name']) ?> <small class="text-muted fw-normal"><?= e($s['brand']) ?></small></div>
        <div class="text-muted" style="font-size:.78rem;"><?= e(limitStr($s['complaint'],60)) ?></div>
        <span class="badge bg-<?= statusBadge($s['status']) ?> mt-1"><?= statusLabel($s['status']) ?></span>
    </div>
    <div class="text-end">
        <div class="text-muted" style="font-size:.78rem;"><?= tglIndo($s['service_date']) ?></div>
        <?php if($s['cost']): ?><div style="font-weight:700;color:#10B981;font-size:.83rem;"><?= rupiah($s['cost']) ?></div><?php endif; ?>
        <a href="show.php?id=<?= $s['id'] ?>" class="btn btn-xs btn-outline-primary mt-1" style="font-size:.7rem;padding:.15rem .45rem;">Detail</a>
    </div>
</div>
<?php endforeach; endif; ?>
</div>
</div>
<?php pageFooter(); ?>
