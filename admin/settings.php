<?php
// admin/settings.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $settings = [
        'transport_fee_per_km'   => (int)post('transport_fee_per_km'),
        'app_fee_percent'        => (int)post('app_fee_percent'),
        'min_transport_fee'      => (int)post('min_transport_fee'),
        'max_transport_distance' => (int)post('max_transport_distance'),
    ];

    // Handle Gemini API key khusus
    if (isset($_POST['gemini_api_key_val'])) {
        $gKey = trim(post('gemini_api_key_val'));
        $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES ('gemini_api_key',?) ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
            ->execute([$gKey, $gKey]);
    }
    foreach ($settings as $key => $val) {
        $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES (?,?)
                       ON DUPLICATE KEY UPDATE setting_value=?, updated_at=NOW()")
            ->execute([$key, (string)$val, (string)$val]);
    }

    redirect('/admin/settings.php', 'Pengaturan berhasil disimpan!', 'success');
}

// Baca setting saat ini
$perKm    = (int)getSetting('transport_fee_per_km', '3000');
$feeP     = (int)getSetting('app_fee_percent', '5');
$minFee   = (int)getSetting('min_transport_fee', '10000');
$maxDist  = (int)getSetting('max_transport_distance', '30');

pageHeader('Pengaturan Platform', 'Pengaturan Platform');
?>

<div class="row g-4">

<!-- ONGKIR SETTING -->
<div class="col-lg-6">
<div class="card">
    <div class="card-header" style="background:var(--sage-lt);">
        <i class="bi bi-car-front me-2" style="color:var(--sage)"></i>
        Pengaturan Ongkir (Home Visit)
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <!-- sembunyikan field fee agar tidak ikut terkirim kosong -->
            <input type="hidden" name="app_fee_percent" value="<?= $feeP ?>">
            <input type="hidden" name="min_transport_fee" value="<?= $minFee ?>">
            <input type="hidden" name="max_transport_distance" value="<?= $maxDist ?>">

            <div class="mb-4">
                <label class="form-label">💰 Harga per Kilometer (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="transport_fee_per_km" class="form-control"
                           value="<?= $perKm ?>" min="500" step="500" required>
                    <span class="input-group-text">/km</span>
                </div>
                <div class="form-text">Contoh: Rp 3.000/km → jarak 5 km = Rp 15.000 ongkir</div>
            </div>

            <div class="p-3 rounded mb-4" style="background:var(--sand);border:1px solid var(--border);font-size:.83rem;">
                <div style="font-weight:700;color:var(--sage-dk);margin-bottom:.5rem;">📊 Simulasi Harga</div>
                <table style="width:100%;font-size:.8rem;">
                    <tr style="color:var(--muted);">
                        <th style="text-align:left;padding:.2rem 0;">Jarak</th>
                        <th style="text-align:right;padding:.2rem 0;">Ongkir</th>
                    </tr>
                    <?php foreach ([3,5,10,15,20] as $km): ?>
                    <tr>
                        <td><?= $km ?> km</td>
                        <td style="text-align:right;font-weight:600;color:var(--sage-dk);"><?= rupiah(max($km * $perKm, $minFee)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <div style="margin-top:.5rem;font-size:.74rem;color:var(--muted);">
                    Minimum ongkir: <strong><?= rupiah($minFee) ?></strong> |
                    Maks. jarak layanan: <strong><?= $maxDist ?> km</strong>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-circle me-1"></i>Simpan Harga/km
            </button>
        </form>
    </div>
</div>
</div>

<!-- FEE & LIMIT SETTING -->
<div class="col-lg-6">
<div class="card">
    <div class="card-header" style="background:var(--sage-lt);">
        <i class="bi bi-percent me-2" style="color:var(--sage)"></i>
        Fee Platform & Batas Layanan
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="transport_fee_per_km" value="<?= $perKm ?>">

            <div class="mb-3">
                <label class="form-label">📱 Fee Platform (%)</label>
                <div class="input-group">
                    <input type="number" name="app_fee_percent" class="form-control"
                           value="<?= $feeP ?>" min="1" max="20" required>
                    <span class="input-group-text">%</span>
                </div>
                <div class="form-text">
                    Fee ini masuk ke kas ReTech Hub (developer).
                    Teknisi <strong>hanya menerima biaya servis + ongkir</strong>.
                    Contoh: biaya servis Rp 200.000 → fee platform Rp <?= rupiah(200000 * $feeP / 100) ?>.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">🚗 Minimum Ongkir (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="min_transport_fee" class="form-control"
                           value="<?= $minFee ?>" min="0" step="1000" required>
                </div>
                <div class="form-text">Ongkir minimum meskipun jarak sangat dekat.</div>
            </div>

            <div class="mb-4">
                <label class="form-label">📍 Maksimum Jarak Layanan (km)</label>
                <div class="input-group">
                    <input type="number" name="max_transport_distance" class="form-control"
                           value="<?= $maxDist ?>" min="1" max="100" required>
                    <span class="input-group-text">km</span>
                </div>
                <div class="form-text">Di atas jarak ini, sistem memperingatkan user agar konfirmasi langsung ke teknisi.</div>
            </div>

            <!-- Penjelasan alur fee -->
            <div class="p-3 rounded mb-4" style="background:#EFF6FF;border:1px solid #BFDBFE;font-size:.82rem;color:#1E40AF;">
                <div style="font-weight:800;margin-bottom:.5rem;">💡 Alur Keuangan ReTech Hub</div>
                <div style="line-height:1.7;">
                    Customer bayar COD → Total = biaya servis + ongkir + fee platform<br>
                    <strong>Teknisi terima:</strong> biaya servis + ongkir<br>
                    <strong>ReTech Hub terima:</strong> fee platform (<?= $feeP ?>%)<br>
                    <em>Semua transparan di struk digital.</em>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-circle me-1"></i>Simpan Fee & Batas
            </button>
        </form>
    </div>
</div>
</div>

<!-- Semua Setting Saat Ini -->
<div class="col-12">
<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Semua Pengaturan Aktif</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Kunci</th><th>Nilai</th><th>Keterangan</th><th>Diperbarui</th></tr></thead>
            <tbody>
            <?php
            $all = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key")->fetchAll();
            foreach ($all as $s): ?>
            <tr>
                <td style="font-size:.82rem;font-family:monospace;color:var(--sage-dk);"><?= e($s['setting_key']) ?></td>
                <td style="font-weight:700;font-size:.85rem;"><?= e($s['setting_value']) ?></td>
                <td style="font-size:.8rem;color:var(--muted);"><?= e($s['description'] ?? '') ?></td>
                <td style="font-size:.78rem;color:var(--muted);"><?= $s['updated_at'] ? date('d M Y H:i', strtotime($s['updated_at'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>



<!-- GEMINI API KEY -->
<div class="col-12">
<div class="card">
    <div class="card-header" style="background:var(--sage-lt,#E8F5EE);">
        <i class="bi bi-cpu me-2" style="color:var(--sage,#4D7C5F)"></i>
        Konfigurasi AI Diagnosa (Google Gemini)
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="transport_fee_per_km" value="<?= $perKm ?>">
            <input type="hidden" name="app_fee_percent" value="<?= $feeP ?>">
            <input type="hidden" name="min_transport_fee" value="<?= $minFee ?>">
            <input type="hidden" name="max_transport_distance" value="<?= $maxDist ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Google Gemini API Key</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="text" name="gemini_api_key_val" class="form-control"
                               placeholder="AIzaSy..."
                               value="<?= e(getSetting('gemini_api_key','')) ?>">
                    </div>
                    <div class="form-text">
                        Dapatkan gratis di <a href="https://makersuite.google.com/app/apikey" target="_blank" style="color:var(--sage,#4D7C5F)">makersuite.google.com</a>.
                        Kosongkan = diagnosa pakai respon fallback default.
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle me-1"></i>Simpan API Key
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
</div><!-- row -->
<?php pageFooter(); ?>
