<?php
// technician/profile.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('technician');

$pdo = getDB();
$uid = $_SESSION['user_id'];

$techStmt = $pdo->prepare("SELECT t.*,u.name,u.phone,u.address FROM technicians t JOIN users u ON t.user_id=u.id WHERE t.user_id=?");
$techStmt->execute([$uid]); $tech = $techStmt->fetch();
if (!$tech) redirect('/technician/dashboard.php', 'Profil teknisi tidak ditemukan.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $workshopAddr = post('workshop_address');
    $lat          = post('latitude');
    $lng          = post('longitude');
    $bio          = post('bio');
    $keahlian     = post('keahlian');
    $phone        = post('phone');

    $pdo->prepare("UPDATE technicians SET workshop_address=?,latitude=?,longitude=?,bio=?,keahlian=?,updated_at=NOW() WHERE user_id=?")
        ->execute([$workshopAddr ?: null, $lat ?: null, $lng ?: null, $bio ?: null, $keahlian, $uid]);

    $pdo->prepare("UPDATE users SET phone=?,updated_at=NOW() WHERE id=?")
        ->execute([$phone ?: null, $uid]);

    redirect('/technician/profile.php', 'Profil berhasil diperbarui!', 'success');
}

pageHeader('Profil & Lokasi', 'Profil & Lokasi Bengkel');
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="alert" style="background:var(--sage-lt);border:1.5px solid var(--sage-md);font-size:.85rem;color:var(--sage-dk);">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Penting:</strong> Isi alamat bengkel dan koordinat agar pelanggan bisa melihat lokasi kamu di maps,
    dan sistem bisa menghitung ongkir otomatis untuk layanan home visit.
</div>

<div class="card">
    <div class="card-header" style="background:var(--sage-lt);">
        <i class="bi bi-person-gear me-2" style="color:var(--sage)"></i>Profil & Lokasi Bengkel
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Nama Teknisi</label>
                    <input type="text" class="form-control" value="<?= e($tech['name'] ?? '') ?>" readonly style="background:var(--sand);">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">No. Telepon / WA</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-whatsapp" style="color:#25D366"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="<?= e($tech['phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Keahlian</label>
                <input type="text" name="keahlian" class="form-control"
                       placeholder="Contoh: Laptop, Handphone, TV, AC"
                       value="<?= e($tech['keahlian'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Bio / Deskripsi Singkat</label>
                <textarea name="bio" rows="2" class="form-control"
                          placeholder="Ceritakan pengalaman dan spesialisasi kamu..."><?= e($tech['bio'] ?? '') ?></textarea>
            </div>

            <hr style="border-color:var(--border);">
            <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--sage-dk);margin-bottom:1rem;">
                📍 Lokasi Bengkel
            </div>

            <div class="mb-3">
                <label class="form-label">Alamat Lengkap Bengkel *</label>
                <textarea name="workshop_address" id="workshopAddress" rows="2" class="form-control"
                          placeholder="Jl. ... No. ..., Kelurahan, Kecamatan, Kota"
                          required><?= e($tech['workshop_address'] ?? '') ?></textarea>
                <div class="form-text">Ini yang akan ditampilkan ke pelanggan saat memilih teknisi.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Latitude (Koordinat)</label>
                    <input type="text" name="latitude" id="techLat" class="form-control"
                           placeholder="-6.2900" value="<?= e((string)($tech['latitude'] ?? '')) ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Longitude (Koordinat)</label>
                    <input type="text" name="longitude" id="techLng" class="form-control"
                           placeholder="106.7946" value="<?= e((string)($tech['longitude'] ?? '')) ?>">
                </div>
            </div>

            <!-- Cara ambil koordinat -->
            <div class="p-3 rounded mb-3" style="background:var(--sand);border:1px solid var(--border);font-size:.81rem;">
                <div style="font-weight:700;color:var(--sage-dk);margin-bottom:.5rem;">📌 Cara ambil koordinat:</div>
                <ol style="color:var(--muted);margin:0;padding-left:1.25rem;line-height:1.8;">
                    <li>Buka <a href="https://maps.google.com" target="_blank" style="color:var(--sage);">Google Maps</a></li>
                    <li>Cari lokasi bengkelmu</li>
                    <li>Klik kanan di titik lokasi → "Apa yang ada di sini?"</li>
                    <li>Koordinat muncul di bawah (format: -6.2900, 106.7946)</li>
                    <li>Angka pertama = Latitude, kedua = Longitude</li>
                </ol>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="getMyLocation()">
                        <i class="bi bi-crosshair me-1"></i>Gunakan Lokasi Saat Ini (GPS)
                    </button>
                </div>
            </div>

            <!-- Preview Maps jika ada koordinat -->
            <?php if ($tech['latitude'] && $tech['longitude']): ?>
            <div class="mb-3">
                <a href="https://maps.google.com/?q=<?= $tech['latitude'] ?>,<?= $tech['longitude'] ?>" target="_blank"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-map me-1"></i>Lihat Lokasi Bengkelmu di Maps
                </a>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>Simpan Profil & Lokasi
            </button>
        </form>
    </div>
</div>

</div>
</div>

<script>
function getMyLocation() {
    if (!navigator.geolocation) {
        alert('Browser tidak mendukung GPS.');
        return;
    }
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mendeteksi...';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            document.getElementById('techLat').value = pos.coords.latitude.toFixed(7);
            document.getElementById('techLng').value = pos.coords.longitude.toFixed(7);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Lokasi terdeteksi!';
            btn.style.color = 'var(--sage)';
        },
        function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-crosshair me-1"></i>Gunakan Lokasi Saat Ini (GPS)';
            alert('Gagal mendapatkan lokasi: ' + err.message);
        }
    );
}
</script>

<?php pageFooter(); ?>
