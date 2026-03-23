<?php
// user/dashboard.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('user');

$pdo = getDB();
$uid = $_SESSION['user_id'];

// Statistik user
$totalDevices  = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE user_id=?");
$totalDevices->execute([$uid]); $totalDevices = $totalDevices->fetchColumn();

$totalServices = $pdo->prepare("SELECT COUNT(*) FROM service_requests sr JOIN devices d ON sr.device_id=d.id WHERE d.user_id=?");
$totalServices->execute([$uid]); $totalServices = $totalServices->fetchColumn();

$activeServices = $pdo->prepare("SELECT COUNT(*) FROM service_requests sr JOIN devices d ON sr.device_id=d.id WHERE d.user_id=? AND sr.status IN('menunggu','diproses')");
$activeServices->execute([$uid]); $activeServices = $activeServices->fetchColumn();

$totalSpent = $pdo->prepare("SELECT COALESCE(SUM(sr.total_cost),0) FROM service_requests sr JOIN devices d ON sr.device_id=d.id WHERE d.user_id=? AND sr.status='selesai'");
$totalSpent->execute([$uid]); $totalSpent = $totalSpent->fetchColumn();

// Konsultasi belum dijawab
$unreadMsg = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM consultations c JOIN chat_messages cm ON c.id=cm.consultation_id WHERE c.user_id=? AND cm.sender_id!=? AND cm.is_read=0");
$unreadMsg->execute([$uid, $uid]); $unreadMsg = $unreadMsg->fetchColumn();

// Servis terbaru
$recentServices = $pdo->prepare("
    SELECT sr.*, d.device_name, d.brand, d.device_type, u2.name AS tech_name, t.lokasi AS tech_lokasi
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    LEFT JOIN technicians t ON sr.technician_id = t.id
    LEFT JOIN users u2 ON t.user_id = u2.id
    WHERE d.user_id = ?
    ORDER BY sr.updated_at DESC LIMIT 5
");
$recentServices->execute([$uid]); $recentServices = $recentServices->fetchAll();

// Perangkat terbaru
$recentDevices = $pdo->prepare("SELECT * FROM devices WHERE user_id=? ORDER BY created_at DESC LIMIT 4");
$recentDevices->execute([$uid]); $recentDevices = $recentDevices->fetchAll();

// Servis yang garansinya masih aktif
$activeWarranties = $pdo->prepare("
    SELECT sr.*, d.device_name, d.brand, DATEDIFF(sr.warranty_until, CURDATE()) AS days_left
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    WHERE d.user_id = ? AND sr.warranty_until >= CURDATE() AND sr.warranty_days > 0
    ORDER BY sr.warranty_until ASC LIMIT 3
");
$activeWarranties->execute([$uid]); $activeWarranties = $activeWarranties->fetchAll();

pageHeader('Dashboard', 'Dashboard Saya');
?>

<div class="mb-4">
    <h4 style="font-weight:800;color:var(--txt,#1A3A28);margin-bottom:.15rem;">
        Halo, <?= e($_SESSION['user_name'] ?? '') ?>! 👋
    </h4>
    <p style="color:var(--muted,#6B8F78);font-size:.87rem;margin:0;">
        Berikut ringkasan aktivitas servis perangkat kamu.
    </p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#4D7C5F,#2D5016)">
            <div class="num"><?= $totalDevices ?></div>
            <div class="lbl">Total Perangkat</div>
            <i class="bi bi-phone ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8)">
            <div class="num"><?= $totalServices ?></div>
            <div class="lbl">Total Servis</div>
            <i class="bi bi-tools ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#F59E0B,#D97706)">
            <div class="num"><?= $activeServices ?></div>
            <div class="lbl">Servis Aktif</div>
            <i class="bi bi-hourglass ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10B981,#065F46)">
            <div class="num" style="font-size:1.3rem;"><?= number_format($totalSpent/1000,0) ?>K</div>
            <div class="lbl">Total Spend (Rp)</div>
            <i class="bi bi-cash ico"></i>
        </div>
    </div>
</div>

<!-- ALERT konsultasi -->
<?php if ($unreadMsg > 0): ?>
<div class="alert mb-4" style="background:#EFF6FF;border:1.5px solid #BFDBFE;font-size:.86rem;color:#1E40AF;">
    <i class="bi bi-chat-dots me-2"></i>
    Ada <strong><?= $unreadMsg ?> balasan</strong> dari teknisi di konsultasi kamu.
    <a href="<?= BASE_URL ?>/user/chat/index.php" style="color:var(--sage-dk,#2D5016);font-weight:700;margin-left:.5rem;">Baca sekarang →</a>
</div>
<?php endif; ?>

<!-- ALERT garansi aktif -->
<?php if (!empty($activeWarranties)): ?>
<div class="alert mb-4" style="background:#E8F5EE;border:1.5px solid #C5E0CF;font-size:.85rem;color:#2D5016;">
    <i class="bi bi-shield-check me-2"></i>
    <strong><?= count($activeWarranties) ?> garansi aktif</strong> —
    <?php foreach ($activeWarranties as $wi => $w): ?>
    <?= e($w['device_name']??'') ?> (<strong><?= $w['days_left'] ?> hari</strong>)<?= $wi < count($activeWarranties)-1 ? ', ' : '' ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- QUICK ACTIONS -->
<div class="card mb-4">
    <div class="card-body p-3">
        <p style="color:var(--muted,#6B8F78);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:.65rem;">Aksi Cepat</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/diagnosis/index.php" class="btn btn-primary btn-sm">
                <i class="bi bi-cpu me-1"></i>Diagnosa AI
            </a>
            <a href="<?= BASE_URL ?>/user/service/create.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-calendar-plus me-1"></i>Booking Servis
            </a>
            <a href="<?= BASE_URL ?>/user/devices/create.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Tambah Perangkat
            </a>
            <a href="<?= BASE_URL ?>/user/chat/create.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-chat-dots me-1"></i>Konsultasi Teknisi
            </a>
            <a href="<?= BASE_URL ?>/ewaste/index.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-recycle me-1"></i>Lokasi E-Waste
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Servis Terbaru -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--sage-lt,#E8F5EE);">
                <span><i class="bi bi-tools me-2" style="color:var(--sage,#4D7C5F)"></i>Servis Terbaru</span>
                <a href="<?= BASE_URL ?>/user/service/index.php" class="btn btn-sm btn-outline-success" style="font-size:.75rem;">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentServices)): ?>
                <div class="text-center py-5" style="color:var(--muted,#6B8F78);">
                    <i class="bi bi-inbox display-4 d-block mb-3 opacity-25"></i>
                    <p style="font-size:.87rem;margin-bottom:.75rem;">Belum ada riwayat servis.</p>
                    <a href="<?= BASE_URL ?>/user/service/create.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-calendar-plus me-1"></i>Booking Servis Pertama
                    </a>
                </div>
                <?php else: foreach ($recentServices as $s): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom" style="border-color:var(--border,#D0E8D8)!important;">
                    <div style="font-size:1.4rem;flex-shrink:0;"><?= deviceEmoji($s['device_type']??'') ?></div>
                    <div class="flex-grow-1 min-w-0">
                        <div style="font-weight:700;font-size:.87rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= e($s['device_name']??'') ?>
                        </div>
                        <div style="font-size:.76rem;color:var(--muted,#6B8F78);">
                            <?= e(limitStr($s['complaint']??'',45)) ?>
                        </div>
                        <?php if ($s['tech_name']): ?>
                        <div style="font-size:.73rem;color:var(--sage,#4D7C5F);">
                            <i class="bi bi-person me-1"></i><?= e($s['tech_name']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="badge bg-<?= statusBadge($s['status']) ?>"><?= statusLabel($s['status']) ?></span>
                        <div style="font-size:.7rem;color:var(--muted,#6B8F78);margin-top:.2rem;"><?= tglIndo($s['service_date']) ?></div>
                        <?php if ($s['total_cost']): ?>
                        <div style="font-size:.8rem;font-weight:700;color:var(--sage,#4D7C5F);"><?= rupiah($s['total_cost']) ?></div>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/user/service/show.php?id=<?= $s['id'] ?>" style="font-size:.7rem;color:var(--sage,#4D7C5F);text-decoration:none;font-weight:600;">
                            Detail →
                        </a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Perangkat & Garansi -->
    <div class="col-lg-5">
        <!-- Perangkat -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--sage-lt,#E8F5EE);">
                <span><i class="bi bi-phone me-2" style="color:var(--sage,#4D7C5F)"></i>Perangkat Saya</span>
                <a href="<?= BASE_URL ?>/user/devices/index.php" class="btn btn-sm btn-outline-success" style="font-size:.75rem;">Lihat Semua</a>
            </div>
            <div class="card-body p-2">
                <?php if (empty($recentDevices)): ?>
                <div class="text-center py-3" style="color:var(--muted,#6B8F78);">
                    <i class="bi bi-phone display-5 d-block mb-2 opacity-25"></i>
                    <p style="font-size:.83rem;margin-bottom:.5rem;">Belum ada perangkat.</p>
                    <a href="<?= BASE_URL ?>/user/devices/create.php" class="btn btn-sm btn-primary">Tambah Perangkat</a>
                </div>
                <?php else: foreach ($recentDevices as $d): ?>
                <a href="<?= BASE_URL ?>/user/devices/show.php?id=<?= $d['id'] ?>" style="text-decoration:none;">
                    <div class="d-flex align-items-center gap-2 p-2 rounded mb-1"
                         style="background:var(--sand,#F4F8F5);transition:.15s;"
                         onmouseover="this.style.background='var(--sage-lt,#E8F5EE)'"
                         onmouseout="this.style.background='var(--sand,#F4F8F5)'">
                        <div style="font-size:1.3rem;flex-shrink:0;"><?= deviceEmoji($d['device_type']??'') ?></div>
                        <div class="flex-grow-1">
                            <div style="font-size:.84rem;font-weight:700;color:var(--txt,#1A3A28);"><?= e($d['device_name']??'') ?></div>
                            <div style="font-size:.74rem;color:var(--muted,#6B8F78);"><?= e($d['brand']??'') ?> · <?= e($d['device_type']??'') ?></div>
                        </div>
                        <i class="bi bi-chevron-right" style="color:var(--muted,#6B8F78);font-size:.75rem;"></i>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Garansi Aktif -->
        <?php if (!empty($activeWarranties)): ?>
        <div class="card">
            <div class="card-header" style="background:#E8F5EE;">
                <i class="bi bi-shield-check me-2" style="color:#2D5016"></i>Garansi Aktif
            </div>
            <div class="card-body p-0">
                <?php foreach ($activeWarranties as $w): ?>
                <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:.83rem;font-weight:700;"><?= e($w['device_name']??'') ?></div>
                        <div style="font-size:.73rem;color:var(--muted,#6B8F78);"><?= e($w['warranty_notes']??'') ?></div>
                    </div>
                    <div class="text-end">
                        <span class="badge" style="background:#E8F5EE;color:#2D5016;font-size:.7rem;">
                            <?= $w['days_left'] ?> hari
                        </span>
                        <div style="font-size:.68rem;color:var(--muted,#6B8F78);">s/d <?= tglIndo($w['warranty_until']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php pageFooter(); ?>
