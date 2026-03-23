<?php
// technician/dashboard.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('technician');

$pdo = getDB();
$uid = $_SESSION['user_id'];

// Cek profil teknisi
$techStmt = $pdo->prepare("SELECT t.*, u.name, u.phone FROM technicians t JOIN users u ON t.user_id=u.id WHERE t.user_id=?");
$techStmt->execute([$uid]); $tech = $techStmt->fetch();

// Cek verifikasi
if (!$tech) {
    pageHeader('Dashboard Teknisi','Dashboard Teknisi');
    echo '<div class="alert alert-danger">Profil teknisi tidak ditemukan. Hubungi admin.</div>';
    pageFooter(); exit;
}

if ($tech['is_verified'] == 0) {
    pageHeader('Menunggu Verifikasi','Akun Dalam Proses Verifikasi');
    ?>
    <div class="row justify-content-center mt-4">
    <div class="col-lg-6">
    <div class="card text-center p-5">
        <div style="font-size:4rem;margin-bottom:1rem;">⏳</div>
        <h4 style="font-weight:800;color:var(--txt,#1A3A28);">Akunmu Sedang Diverifikasi</h4>
        <p style="color:var(--muted,#6B8F78);font-size:.9rem;line-height:1.7;max-width:380px;margin:.75rem auto 0;">
            Admin sedang memeriksa data pendaftaran teknisimu.
            Proses verifikasi biasanya <strong>1–2 hari kerja</strong>.
            Kamu akan dihubungi via WhatsApp setelah disetujui.
        </p>
        <?php if ($tech['phone']): ?>
        <div class="mt-3" style="font-size:.83rem;color:var(--muted,#6B8F78);">
            WA terdaftar: <strong><?= e($tech['phone']??'') ?></strong>
        </div>
        <?php endif; ?>
        <div class="mt-4 p-3 rounded" style="background:var(--sage-lt,#E8F5EE);border:1px solid var(--sage-md,#C5E0CF);font-size:.82rem;color:var(--sage-dk,#2D5016);">
            <i class="bi bi-info-circle me-1"></i>
            Sambil menunggu, kamu bisa melengkapi <a href="<?= BASE_URL ?>/technician/profile.php" style="color:var(--sage,#4D7C5F);font-weight:700;">profil & lokasi bengkel</a> kamu.
        </div>
    </div>
    </div>
    </div>
    <?php
    pageFooter(); exit;
}

if ($tech['is_verified'] == 2) {
    pageHeader('Akun Ditolak','Pendaftaran Teknisi Ditolak');
    ?>
    <div class="row justify-content-center mt-4"><div class="col-lg-6">
    <div class="card text-center p-5" style="border:2px solid #FECDD3;">
        <div style="font-size:4rem;margin-bottom:1rem;">❌</div>
        <h4 style="font-weight:800;color:#9F1239;">Pendaftaran Ditolak</h4>
        <?php if ($tech['reject_reason']): ?>
        <p style="color:#6B7280;font-size:.88rem;">
            Alasan: <strong><?= e($tech['reject_reason']??'') ?></strong>
        </p>
        <?php endif; ?>
        <p style="color:#6B7280;font-size:.85rem;">Silakan hubungi admin untuk informasi lebih lanjut.</p>
    </div>
    </div></div>
    <?php
    pageFooter(); exit;
}

$tid = $tech['id'];

// Statistik pekerjaan
$totalJobs  = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE technician_id=?");
$totalJobs->execute([$tid]); $totalJobs = $totalJobs->fetchColumn();

$pendingJobs = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE technician_id=? AND status='menunggu'");
$pendingJobs->execute([$tid]); $pendingJobs = $pendingJobs->fetchColumn();

$activeJobs  = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE technician_id=? AND status='diproses'");
$activeJobs->execute([$tid]); $activeJobs = $activeJobs->fetchColumn();

$doneJobs    = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE technician_id=? AND status='selesai'");
$doneJobs->execute([$tid]); $doneJobs = $doneJobs->fetchColumn();

$totalEarning = $pdo->prepare("SELECT COALESCE(SUM(service_cost + transport_fee),0) FROM service_requests WHERE technician_id=? AND status='selesai'");
$totalEarning->execute([$tid]); $totalEarning = $totalEarning->fetchColumn();

// Konsultasi belum dijawab
$unreadConsult = $pdo->prepare("SELECT COUNT(DISTINCT c.id) FROM consultations c JOIN chat_messages cm ON c.id=cm.consultation_id WHERE c.technician_id=? AND cm.sender_id != ? AND cm.is_read=0");
$unreadConsult->execute([$tid, $uid]); $unreadConsult = $unreadConsult->fetchColumn();

// Pekerjaan terbaru
$recentJobs = $pdo->prepare("
    SELECT sr.*, d.device_name, d.brand, d.device_type, u.name AS customer_name, u.phone AS customer_phone
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE sr.technician_id = ?
    ORDER BY sr.updated_at DESC LIMIT 5
");
$recentJobs->execute([$tid]); $recentJobs = $recentJobs->fetchAll();

pageHeader('Dashboard Teknisi', 'Dashboard Teknisi');
?>

<div class="mb-4 d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:var(--txt,#1A3A28);margin-bottom:.15rem;">
            Halo, <?= e($tech['name']??'') ?>! 🔧
        </h4>
        <p style="color:var(--muted,#6B8F78);font-size:.87rem;margin:0;">
            <i class="bi bi-tools me-1"></i><?= e($tech['keahlian']??'') ?>
            &nbsp;·&nbsp;
            <i class="bi bi-geo-alt me-1"></i><?= e($tech['lokasi']??'') ?>
        </p>
    </div>
    <!-- Badge verifikasi -->
    <span class="badge" style="background:var(--sage-lt,#E8F5EE);color:var(--sage-dk,#2D5016);border:1px solid var(--sage-md,#C5E0CF);font-size:.78rem;padding:.4rem .8rem;border-radius:8px;">
        ✅ Terverifikasi
    </span>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#4D7C5F,#2D5016)">
            <div class="num"><?= $totalJobs ?></div>
            <div class="lbl">Total Pekerjaan</div>
            <i class="bi bi-briefcase ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#F59E0B,#D97706)">
            <div class="num"><?= $pendingJobs + $activeJobs ?></div>
            <div class="lbl">Sedang Berjalan</div>
            <i class="bi bi-hourglass ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8)">
            <div class="num"><?= $doneJobs ?></div>
            <div class="lbl">Selesai</div>
            <i class="bi bi-check-circle ico"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#10B981,#065F46)">
            <div class="num" style="font-size:1.3rem;"><?= number_format($totalEarning/1000,0) ?>K</div>
            <div class="lbl">Penghasilan (Rp)</div>
            <i class="bi bi-cash ico"></i>
        </div>
    </div>
</div>

<!-- ALERT konsultasi belum dijawab -->
<?php if ($unreadConsult > 0): ?>
<div class="alert mb-4" style="background:#EFF6FF;border:1.5px solid #BFDBFE;font-size:.86rem;color:#1E40AF;">
    <i class="bi bi-chat-dots me-2"></i>
    Ada <strong><?= $unreadConsult ?> pesan konsultasi</strong> yang belum dijawab.
    <a href="<?= BASE_URL ?>/technician/chat.php" style="color:var(--sage-dk,#2D5016);font-weight:700;margin-left:.5rem;">Lihat sekarang →</a>
</div>
<?php endif; ?>

<!-- ALERT kalau belum isi alamat bengkel -->
<?php if (!$tech['workshop_address'] || !$tech['latitude']): ?>
<div class="alert mb-4" style="background:#FFFBEB;border:1.5px solid #FDE68A;font-size:.85rem;color:#92400E;">
    <i class="bi bi-geo-alt me-2"></i>
    <strong>Lengkapi Profil:</strong> Alamat bengkel dan koordinat belum diisi.
    Pelanggan tidak bisa melihat lokasi bengkelmu!
    <a href="<?= BASE_URL ?>/technician/profile.php" style="color:var(--sage-dk,#2D5016);font-weight:700;margin-left:.5rem;">Isi sekarang →</a>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Pekerjaan Terbaru -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--sage-lt,#E8F5EE);">
                <span><i class="bi bi-list-task me-2" style="color:var(--sage,#4D7C5F)"></i>Pekerjaan Terbaru</span>
                <a href="<?= BASE_URL ?>/technician/services.php" class="btn btn-sm btn-outline-success" style="font-size:.75rem;">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Pelanggan</th><th>Perangkat</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($recentJobs)): ?>
                        <tr><td colspan="5" class="text-center py-4" style="color:var(--muted,#6B8F78);">
                            <i class="bi bi-inbox d-block display-5 mb-2 opacity-25"></i>
                            Belum ada pekerjaan masuk.
                        </td></tr>
                    <?php else: foreach ($recentJobs as $j): ?>
                        <tr>
                            <td>
                                <div style="font-size:.85rem;font-weight:600;"><?= e($j['customer_name']??'') ?></div>
                                <?php if ($j['customer_phone']): ?>
                                <a href="https://wa.me/62<?= ltrim($j['customer_phone']??'','0') ?>" target="_blank" style="font-size:.72rem;color:#25D366;text-decoration:none;">
                                    <i class="bi bi-whatsapp me-1"></i><?= e($j['customer_phone']??'') ?>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:.85rem;"><?= deviceEmoji($j['device_type']??'') ?> <?= e($j['device_name']??'') ?></div>
                                <div style="font-size:.74rem;color:var(--muted,#6B8F78);"><?= e($j['brand']??'') ?></div>
                            </td>
                            <td style="font-size:.81rem;"><?= tglIndo($j['service_date']) ?></td>
                            <td><span class="badge bg-<?= statusBadge($j['status']) ?>"><?= statusLabel($j['status']) ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/technician/update.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-primary" style="font-size:.72rem;padding:.25rem .55rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions + Profil -->
    <div class="col-lg-4">
        <!-- Quick Links -->
        <div class="card mb-3">
            <div class="card-header" style="background:var(--sage-lt,#E8F5EE);">
                <i class="bi bi-lightning me-2" style="color:var(--sage,#4D7C5F)"></i>Aksi Cepat
            </div>
            <div class="card-body p-3 d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>/technician/services.php?status=menunggu" class="btn btn-outline-warning btn-sm text-start">
                    <i class="bi bi-hourglass me-2"></i>
                    Servis Menunggu
                    <?php if ($pendingJobs > 0): ?>
                    <span class="badge bg-warning ms-1" style="font-size:.68rem;"><?= $pendingJobs ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>/technician/chat.php" class="btn btn-outline-primary btn-sm text-start">
                    <i class="bi bi-chat-dots me-2"></i>
                    Konsultasi
                    <?php if ($unreadConsult > 0): ?>
                    <span class="badge bg-primary ms-1" style="font-size:.68rem;"><?= $unreadConsult ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>/technician/profile.php" class="btn btn-outline-success btn-sm text-start">
                    <i class="bi bi-person-gear me-2"></i>Profil &amp; Lokasi
                </a>
            </div>
        </div>

        <!-- Info profil -->
        <div class="card">
            <div class="card-header" style="background:var(--sage-lt,#E8F5EE);">
                <i class="bi bi-star me-2" style="color:var(--sage,#4D7C5F)"></i>Performa Kamu
            </div>
            <div class="card-body p-3 text-center">
                <div style="font-size:2.5rem;font-weight:800;color:var(--sage-dk,#2D5016);"><?= number_format($tech['rating']??0,1) ?></div>
                <div style="font-size:1.2rem;margin:.2rem 0;">
                    <?php for ($i=1;$i<=5;$i++) echo $i<=$tech['rating']?'⭐':'☆'; ?>
                </div>
                <div style="font-size:.8rem;color:var(--muted,#6B8F78);">dari <?= $tech['total_jobs']??0 ?> ulasan</div>
                <hr style="border-color:var(--border,#D0E8D8);">
                <div style="font-size:.82rem;color:var(--muted,#6B8F78);">
                    <i class="bi bi-lightning me-1"></i>Respon ~<?= $tech['response_time']??60 ?> menit<br>
                    <i class="bi bi-tag me-1"></i>Mulai <?= rupiah($tech['price_start']??50000) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php pageFooter(); ?>
