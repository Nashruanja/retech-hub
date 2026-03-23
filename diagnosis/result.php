<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/diagnosis/index.php');
verifyCsrf();

$deviceName = post('device_name');
$complaint  = post('complaint');

if (!$deviceName || !$complaint) redirect('/diagnosis/index.php', 'Nama perangkat dan keluhan wajib diisi.');

// Panggil Gemini API
$diagnosis  = geminiDiagnose($deviceName, $complaint);
$color      = damageColor($diagnosis['tingkat_kerusakan']);

// Simpan diagnosis ke session agar bisa digunakan saat booking
$_SESSION['last_diagnosis'] = [
    'device_name' => $deviceName,
    'complaint'   => $complaint,
    'diagnosis'   => implode(' | ', array_values($diagnosis)),
];

pageHeader('Hasil Diagnosa', 'Hasil Diagnosa AI');
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<!-- Info Perangkat -->
<div class="card mb-4">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <div style="font-size:2rem;">📱</div>
        <div>
            <div style="font-weight:600;"><?= e($deviceName) ?></div>
            <div class="text-muted" style="font-size:.82rem;">Keluhan: <?= e(limitStr($complaint, 80)) ?></div>
        </div>
    </div>
</div>

<!-- Hasil Diagnosa -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>🤖 Hasil Diagnosa AI</span>
        <span class="badge bg-success" style="font-size:.7rem;">Gemini AI</span>
    </div>
    <div class="card-body p-4">

        <!-- Tingkat Kerusakan -->
        <div class="text-center mb-4 p-3" style="background:#F8FAFB;border-radius:12px;">
            <div class="text-muted mb-1" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Tingkat Kerusakan</div>
            <span class="badge bg-<?= $color ?>" style="font-size:1rem;padding:.5rem 1.5rem;border-radius:8px;">
                <?= e($diagnosis['tingkat_kerusakan']) ?>
            </span>
        </div>

        <!-- Kemungkinan Kerusakan -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size:1.2rem;">🔍</span>
                <span style="font-weight:600;font-size:.9rem;">Kemungkinan Penyebab</span>
            </div>
            <div style="color:#495057;font-size:.9rem;line-height:1.7;padding-left:2rem;">
                <?= e($diagnosis['kemungkinan_kerusakan']) ?>
            </div>
        </div>

        <!-- Saran -->
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size:1.2rem;">💡</span>
                <span style="font-weight:600;font-size:.9rem;">Saran Tindakan</span>
            </div>
            <div style="color:#495057;font-size:.9rem;line-height:1.7;padding-left:2rem;">
                <?= e($diagnosis['saran']) ?>
            </div>
        </div>

        <hr>
        <p class="text-muted mb-0" style="font-size:.76rem;">
            <i class="bi bi-info-circle me-1"></i>
            Diagnosa ini adalah perkiraan awal AI. Pemeriksaan langsung oleh teknisi tetap diperlukan.
        </p>
    </div>
</div>

<!-- Langkah Selanjutnya -->
<div class="card">
    <div class="card-body p-4">
        <h6 style="font-weight:700;margin-bottom:1rem;">Langkah Selanjutnya</h6>
        <div class="row g-3">
            <div class="col-sm-6">
                <a href="<?= BASE_URL ?>/user/service/create.php" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-calendar-plus me-2"></i>Booking Teknisi
                </a>
                <p class="text-muted text-center mt-1 mb-0" style="font-size:.73rem;">Bayar COD setelah selesai</p>
            </div>
            <div class="col-sm-6">
                <a href="<?= BASE_URL ?>/diagnosis/index.php" class="btn btn-outline-secondary w-100 py-2">
                    <i class="bi bi-arrow-clockwise me-2"></i>Diagnosa Lagi
                </a>
                <p class="text-muted text-center mt-1 mb-0" style="font-size:.73rem;">Coba perangkat berbeda</p>
            </div>
        </div>

        <?php if ($diagnosis['tingkat_kerusakan'] === 'Berat'): ?>
        <div class="alert mt-3 mb-0 py-2 px-3" style="background:#FFF5F5;border:1px solid #F5C6CB;border-radius:10px;font-size:.83rem;">
            <strong class="text-danger">⚠️ Kerusakan Berat</strong><br>
            <span class="text-muted">Jika tidak bisa diperbaiki, kelola sebagai e-waste secara bertanggung jawab.</span><br>
            <a href="<?= BASE_URL ?>/ewaste/index.php" class="btn btn-sm btn-danger mt-2">
                <i class="bi bi-recycle me-1"></i>Lihat Lokasi E-Waste
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
</div>

<?php pageFooter(); ?>
