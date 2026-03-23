<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$pdo = getDB();

// Statistik platform
$stats = [
    'users'       => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'technicians' => $pdo->query("SELECT COUNT(*) FROM technicians WHERE is_verified=1")->fetchColumn(),
    'pending_tech'=> $pdo->query("SELECT COUNT(*) FROM technicians WHERE is_verified=0")->fetchColumn(),
    'services'    => $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn(),
    'pending_srv' => $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status='menunggu'")->fetchColumn(),
    'devices'     => $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn(),
    'ewaste'      => $pdo->query("SELECT COUNT(*) FROM e_waste_locations")->fetchColumn(),
    'revenue'     => $pdo->query("SELECT COALESCE(SUM(app_fee),0) FROM service_requests WHERE status='selesai'")->fetchColumn(),
];

// Servis terbaru
$recentServices = $pdo->query("
    SELECT sr.*, d.device_name, d.brand, u.name AS customer_name, u2.name AS tech_name
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN technicians t ON sr.technician_id = t.id
    LEFT JOIN users u2 ON t.user_id = u2.id
    ORDER BY sr.created_at DESC LIMIT 6
")->fetchAll();

// Teknisi pending verifikasi
$pendingTechs = $pdo->query("
    SELECT t.*, u.name, u.phone, u.created_at AS registered_at
    FROM technicians t JOIN users u ON t.user_id=u.id
    WHERE t.is_verified=0 ORDER BY u.created_at DESC LIMIT 3
")->fetchAll();

pageHeader('Admin Dashboard', 'Panel Administrasi');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:800;color:var(--txt);margin-bottom:.15rem;">Selamat Datang, <?= e($pdo->query("SELECT name FROM users WHERE id={$_SESSION['user_id']}")->fetchColumn()) ?>! 👋</h4>
        <p style="color:var(--muted);font-size:.87rem;margin:0;">Ringkasan statistik ReTech Hub hari ini.</p>
    </div>
    <div style="font-size:.8rem;color:var(--muted);"><?= date('l, d F Y') ?></div>
</div>

<!-- ALERT: Teknisi pending verifikasi -->
<?php if ($stats['pending_tech'] > 0): ?>
<div class="alert mb-4" style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:var(--r);font-size:.87rem;color:#92400E;">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong><?= $stats['pending_tech'] ?> teknisi</strong> menunggu verifikasi.
    <a href="<?= BASE_URL ?>/admin/technician_verify.php" style="color:var(--sage-dk,#2D5016);font-weight:700;margin-left:.5rem;">
        Verifikasi sekarang →
    </a>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#4D7C5F,#2D5016)">
            <div class="num"><?= $stats['users'] ?></div>
            <div class="lbl">Total Pelanggan</div>
            <i class="bi bi-people ico"></i>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8)">
            <div class="num"><?= $stats['technicians'] ?></div>
            <div class="lbl">Teknisi Aktif</div>
            <i class="bi bi-person-gear ico"></i>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#F59E0B,#D97706)">
            <div class="num"><?= $stats['services'] ?></div>
            <div class="lbl">Total Servis</div>
            <i class="bi bi-tools ico"></i>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10B981,#065F46)">
            <div class="num">Rp <?= number_format($stats['revenue']/1000,0,',','.') ?>K</div>
            <div class="lbl">Fee Platform</div>
            <i class="bi bi-cash-coin ico"></i>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="row g-3 mb-4">
    <?php
    $actions = [
        ['admin/users/create.php',        'Tambah User',          'person-plus',   'success'],
        ['admin/technician_verify.php',   'Verifikasi Teknisi',   'shield-check',  'warning'],
        ['admin/ewaste/create.php',        'Tambah Lokasi E-Waste','recycle',       'info'],
        ['admin/articles/create.php',      'Tulis Artikel',        'journal-plus',  'primary'],
    ];
    foreach ($actions as [$url,$lbl,$ico,$col]):
    ?>
    <div class="col-6 col-md-3">
        <a href="<?= BASE_URL ?>/<?= $url ?>" class="btn btn-outline-<?= $col ?> w-100 py-3 d-flex flex-column align-items-center gap-1" style="border-radius:12px;text-decoration:none;">
            <i class="bi bi-<?= $ico ?>" style="font-size:1.4rem;"></i>
            <span style="font-size:.78rem;"><?= $lbl ?></span>
            <?php if ($lbl === 'Verifikasi Teknisi' && $stats['pending_tech'] > 0): ?>
            <span class="badge bg-warning" style="font-size:.68rem;"><?= $stats['pending_tech'] ?> pending</span>
            <?php endif; ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Servis Terbaru -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--sage-lt,#E8F5EE);">
                <span><i class="bi bi-clock-history me-2" style="color:var(--sage,#4D7C5F)"></i>Servis Terbaru</span>
                <a href="<?= BASE_URL ?>/admin/services.php" class="btn btn-sm btn-outline-success" style="font-size:.75rem;">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Pelanggan</th><th>Perangkat</th><th>Teknisi</th><th>Status</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentServices)): ?>
                        <tr><td colspan="5" class="text-center py-3" style="color:var(--muted,#6B8F78);">Belum ada data servis.</td></tr>
                    <?php else: foreach ($recentServices as $s): ?>
                        <tr>
                            <td style="font-size:.84rem;"><?= e($s['customer_name'] ?? '-') ?></td>
                            <td>
                                <div style="font-size:.84rem;font-weight:600;"><?= e($s['device_name'] ?? '-') ?></div>
                                <div style="font-size:.74rem;color:var(--muted,#6B8F78);"><?= e($s['brand'] ?? '') ?></div>
                            </td>
                            <td style="font-size:.84rem;"><?= e($s['tech_name'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= statusBadge($s['status']) ?>"><?= statusLabel($s['status']) ?></span></td>
                            <td style="font-size:.83rem;font-weight:600;">
                                <?= $s['total_cost'] ? rupiah($s['total_cost']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar stats + pending -->
    <div class="col-lg-4">
        <!-- Mini stats -->
        <div class="card mb-3">
            <div class="card-header" style="background:var(--sage-lt,#E8F5EE);">
                <i class="bi bi-bar-chart me-2" style="color:var(--sage,#4D7C5F)"></i>Ringkasan
            </div>
            <div class="card-body p-3">
                <?php
                $miniStats = [
                    ['bi-hourglass','Menunggu',           $stats['pending_srv'], '#FEF3C7','#B45309'],
                    ['bi-phone',   'Total Perangkat',      $stats['devices'],     '#DBEAFE','#1D4ED8'],
                    ['bi-recycle', 'Lokasi E-Waste',       $stats['ewaste'],      '#E8F5EE','#2D5016'],
                    ['bi-shield-x','Teknisi Pending',      $stats['pending_tech'],'#FFF1F2','#BE123C'],
                ];
                foreach ($miniStats as [$ico,$lbl,$val,$bg,$clr]):
                ?>
                <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--border,#D0E8D8);">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;background:<?= $bg ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi <?= $ico ?>" style="color:<?= $clr ?>;font-size:.88rem;"></i>
                        </div>
                        <span style="font-size:.83rem;color:var(--txt,#1A3A28);"><?= $lbl ?></span>
                    </div>
                    <span style="font-weight:800;color:<?= $clr ?>;font-size:.97rem;"><?= $val ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending teknisi -->
        <?php if (!empty($pendingTechs)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#FFFBEB;">
                <span style="font-size:.85rem;"><i class="bi bi-shield-exclamation me-2" style="color:#D97706"></i>Perlu Diverifikasi</span>
                <a href="<?= BASE_URL ?>/admin/technician_verify.php" style="font-size:.73rem;color:var(--sage,#4D7C5F);font-weight:700;text-decoration:none;">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <?php foreach ($pendingTechs as $pt): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#A7D3B5,#4D7C5F);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0;">
                        <?= strtoupper(substr($pt['name']??'T',0,1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:700;font-size:.83rem;"><?= e($pt['name']??'') ?></div>
                        <div style="font-size:.72rem;color:var(--muted,#6B8F78);"><?= e($pt['keahlian']??'') ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/technician_verify.php" class="btn btn-sm" style="font-size:.7rem;padding:.2rem .5rem;background:#FFFBEB;color:#B45309;border:1px solid #FDE68A;border-radius:6px;">
                        Review
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php pageFooter(); ?>
