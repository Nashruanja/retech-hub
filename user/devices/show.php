<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$id  = (int)get('id');
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);
$device = $stmt->fetch();
if (!$device) redirect('/user/devices/index.php', 'Perangkat tidak ditemukan.', 'error');

// Riwayat servis perangkat ini
$hist = $pdo->prepare("SELECT sr.*, u.name AS tech_name, t.lokasi AS tech_lokasi
    FROM service_requests sr
    LEFT JOIN technicians t ON sr.technician_id=t.id
    LEFT JOIN users u ON t.user_id=u.id
    WHERE sr.device_id=? ORDER BY sr.created_at DESC");
$hist->execute([$id]);
$history = $hist->fetchAll();

pageHeader($device['device_name'], 'Detail Perangkat');
?>

<!-- Header Perangkat -->
<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-4">
            <div style="font-size:3rem;"><?= deviceEmoji($device['device_type']) ?></div>
            <div class="flex-grow-1">
                <h4 style="font-weight:700;margin-bottom:.25rem;"><?= e($device['device_name']) ?></h4>
                <div class="text-muted" style="font-size:.88rem;">
                    <?= e($device['brand']) ?> · <?= e($device['device_type']) ?>
                    <?php if ($device['purchase_year']): ?> · <?= $device['purchase_year'] ?><?php endif; ?>
                </div>
                <?php if ($device['description']): ?>
                <p class="text-muted mt-1 mb-0" style="font-size:.83rem;"><?= e($device['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/user/service/create.php?device_id=<?= $device['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-calendar-plus me-1"></i>Booking Servis
                </a>
                <a href="edit.php?id=<?= $device['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Servis -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-success"></i>Riwayat Servis Perangkat Ini</span>
        <span class="badge bg-light text-muted"><?= count($history) ?> servis</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard-x display-4 d-block mb-3 opacity-25"></i>
            <p class="mb-2">Belum ada riwayat servis untuk perangkat ini.</p>
            <a href="<?= BASE_URL ?>/user/service/create.php?device_id=<?= $device['id'] ?>" class="btn btn-primary btn-sm">Booking Servis Sekarang</a>
        </div>
        <?php else: foreach ($history as $s): ?>
        <div class="p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="badge bg-<?= statusBadge($s['status']) ?> me-2"><?= statusLabel($s['status']) ?></span>
                    <small class="text-muted"><?= tglIndo($s['service_date']) ?></small>
                </div>
                <a href="<?= BASE_URL ?>/user/service/show.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:.73rem;padding:.2rem .55rem;">
                    Detail <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted" style="font-size:.75rem;font-weight:700;text-transform:uppercase;">Keluhan</div>
                    <p class="mb-0" style="font-size:.87rem;"><?= e($s['complaint']) ?></p>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.75rem;font-weight:700;text-transform:uppercase;">Teknisi</div>
                            <p class="mb-0" style="font-size:.85rem;"><?= e($s['tech_name'] ?? '-') ?></p>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.75rem;font-weight:700;text-transform:uppercase;">Biaya</div>
                            <p class="mb-0 fw-bold" style="font-size:.85rem;"><?= $s['cost'] ? rupiah($s['cost']) : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($s['notes']): ?>
            <div class="mt-2 p-2 rounded" style="background:#F8FAFB;font-size:.8rem;color:#6C757D;">
                <i class="bi bi-chat-left-text me-1"></i><em><?= e($s['notes']) ?></em>
            </div>
            <?php endif; ?>
            <?php if ($s['status'] === 'tidak_bisa_diperbaiki'): ?>
            <div class="alert mt-2 mb-0 py-2 px-3" style="background:#FFF5F5;border:1px solid #F5C6CB;border-radius:8px;font-size:.82rem;">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i>
                Perangkat tidak bisa diperbaiki.
                <a href="<?= BASE_URL ?>/ewaste/" class="fw-bold text-danger ms-1">Kelola sebagai E-Waste <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php pageFooter(); ?>
