<?php
// user/service/create.php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$uid = $_SESSION['user_id'];

// Ambil data user (termasuk koordinat kalau sudah diset)
$userRow = currentUser();

// Sort teknisi
$sort     = get('sort', 'rating');
$deviceId = (int)get('device_id');
$orderMap = [
    'rating'   => 't.rating DESC, t.total_jobs DESC',
    'terlaris' => 't.total_jobs DESC',
    'tercepat' => 't.response_time ASC',
    'termurah' => 't.price_start ASC',
];
$orderSQL = $orderMap[$sort] ?? $orderMap['rating'];

$devStmt = $pdo->prepare("SELECT * FROM devices WHERE user_id=? ORDER BY device_name");
$devStmt->execute([$uid]); $devices = $devStmt->fetchAll();

$technicians = $pdo->query("SELECT t.*,u.name,u.phone FROM technicians t JOIN users u ON t.user_id=u.id WHERE t.is_available=1 AND t.is_verified=1 ORDER BY $orderSQL")->fetchAll();

// Setting platform
$feePercent = (float)getSetting('app_fee_percent', '5');
$pricePerKm = (int)getSetting('transport_fee_per_km', '3000');
$minFee     = (int)getSetting('min_transport_fee', '10000');
$maxDist    = (int)getSetting('max_transport_distance', '30');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $did  = (int)post('device_id');
    $tid  = (int)post('technician_id');
    $comp = post('complaint');
    $date = post('service_date');
    $type = post('service_type');
    $addr = post('customer_address');
    $lat  = (float)post('customer_lat') ?: null;
    $lng  = (float)post('customer_lng') ?: null;

    if (!$did)  $errors[] = 'Pilih perangkat.';
    if (!$tid)  $errors[] = 'Pilih teknisi.';
    if (!$comp) $errors[] = 'Keluhan wajib diisi.';
    if (!$date) $errors[] = 'Tanggal servis wajib diisi.';
    if ($type === 'home_visit' && !$addr) $errors[] = 'Alamat wajib diisi untuk layanan ke rumah.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM devices WHERE id=? AND user_id=?");
        $chk->execute([$did, $uid]);
        if (!$chk->fetch()) $errors[] = 'Perangkat tidak valid.';
    }

    if (empty($errors)) {
        // Hitung jarak & ongkir
        $distanceKm  = 0;
        $transportFee = 0;

        if ($type === 'home_visit' && $lat && $lng) {
            $tech = array_filter($technicians, fn($t) => $t['id'] == $tid);
            $tech = reset($tech);
            if ($tech && $tech['latitude'] && $tech['longitude']) {
                $distanceKm   = calcDistanceKm($lat, $lng, (float)$tech['latitude'], (float)$tech['longitude']);
                $transportFee = calcTransportFee($distanceKm);
            }
        }

        $diagnosis = $_SESSION['last_diagnosis']['diagnosis'] ?? null;
        unset($_SESSION['last_diagnosis']);

        $pdo->prepare("INSERT INTO service_requests
            (device_id,technician_id,complaint,diagnosis,service_date,service_type,status,
             customer_address,customer_lat,customer_lng,distance_km,transport_fee,payment_method)
            VALUES (?,?,?,?,?,?,'menunggu',?,?,?,?,?,'COD')")
            ->execute([$did,$tid,$comp,$diagnosis,$date,$type,
                       $addr ?: null, $lat, $lng, $distanceKm, $transportFee]);

        redirect('/user/service/index.php', 'Booking berhasil! Teknisi akan segera menghubungi Anda.', 'success');
    }
}

pageHeader('Booking Servis', 'Booking Servis Baru');
?>

<div class="row justify-content-center">
<div class="col-lg-11">

<?php if (empty($devices)): ?>
<div class="card text-center py-5"><div class="card-body">
    <div style="font-size:3rem;margin-bottom:1rem;">📱</div>
    <h5>Belum ada perangkat</h5>
    <a href="<?= BASE_URL ?>/user/devices/create.php" class="btn btn-primary mt-2"><i class="bi bi-plus-circle me-1"></i>Tambah Perangkat</a>
</div></div>
<?php else: ?>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= implode(' &bull; ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="POST" id="bookForm">
<?php csrfField(); ?>
<div class="row g-4">

<!-- KIRI: Form Detail -->
<div class="col-lg-4">
<div class="card">
    <div class="card-header" style="background:var(--sage-lt);">
        <i class="bi bi-calendar-plus me-2" style="color:var(--sage)"></i>Detail Booking
    </div>
    <div class="card-body p-3">

        <!-- Perangkat -->
        <div class="mb-3">
            <label class="form-label">Perangkat *</label>
            <select name="device_id" class="form-select" required>
                <option value="">-- Pilih Perangkat --</option>
                <?php foreach ($devices as $d): ?>
                <option value="<?= $d['id'] ?>" <?= (post('device_id', $deviceId) == $d['id']) ? 'selected' : '' ?>>
                    <?= e($d['brand'] ?? '') ?> <?= e($d['device_name'] ?? '') ?> (<?= e($d['device_type'] ?? '') ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Keluhan -->
        <div class="mb-3">
            <label class="form-label">Keluhan *</label>
            <textarea name="complaint" rows="3" class="form-control" placeholder="Deskripsikan masalah perangkat..." required><?= e(post('complaint')) ?></textarea>
        </div>

        <!-- Tanggal -->
        <div class="mb-3">
            <label class="form-label">Tanggal Servis *</label>
            <input type="date" name="service_date" class="form-control"
                   min="<?= date('Y-m-d') ?>"
                   value="<?= post('service_date', date('Y-m-d', strtotime('+1 day'))) ?>" required>
        </div>

        <!-- Jenis Servis -->
        <div class="mb-3">
            <label class="form-label">Jenis Servis *</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="radio" class="btn-check" name="service_type" id="st_bring" value="bring_in"
                           <?= post('service_type','bring_in') === 'bring_in' ? 'checked' : '' ?>
                           onchange="toggleHomeVisit(false)">
                    <label class="btn btn-outline-success w-100 py-2 text-start" for="st_bring" style="font-size:.82rem;">
                        🏪 Bawa ke Bengkel<br>
                        <small class="text-muted" style="font-size:.7rem;">Ongkir gratis</small>
                    </label>
                </div>
                <div class="col-6">
                    <input type="radio" class="btn-check" name="service_type" id="st_home" value="home_visit"
                           <?= post('service_type') === 'home_visit' ? 'checked' : '' ?>
                           onchange="toggleHomeVisit(true)">
                    <label class="btn btn-outline-success w-100 py-2 text-start" for="st_home" style="font-size:.82rem;">
                        🏠 Teknisi ke Rumah<br>
                        <small class="text-muted" style="font-size:.7rem;">+Ongkir otomatis</small>
                    </label>
                </div>
            </div>
        </div>

        <!-- Alamat (home visit) -->
        <div id="homeVisitFields" style="display:<?= post('service_type') === 'home_visit' ? 'block' : 'none' ?>;">
            <div class="mb-3">
                <label class="form-label">Alamat Kamu *</label>
                <textarea name="customer_address" id="customerAddress" rows="2" class="form-control"
                          placeholder="Jl. ... No. ..., Kelurahan, Kecamatan, Kota"><?= e(post('customer_address', $userRow['address'] ?? '')) ?></textarea>
                <input type="hidden" name="customer_lat" id="customerLat" value="<?= e((string)($userRow['latitude'] ?? '')) ?>">
                <input type="hidden" name="customer_lng" id="customerLng" value="<?= e((string)($userRow['longitude'] ?? '')) ?>">
            </div>

            <!-- Estimasi ongkir -->
            <div id="ongkirEstimate" class="p-2 rounded mb-2" style="background:#F0FDF4;border:1px solid var(--sage-md);font-size:.8rem;display:none;">
                <div style="font-weight:700;color:var(--sage-dk);margin-bottom:.2rem;">🚗 Estimasi Ongkir</div>
                <div id="ongkirDetail" style="color:var(--muted);"></div>
            </div>
        </div>

        <!-- Ringkasan harga -->
        <div id="pricePreview" class="p-3 rounded mb-3" style="background:var(--sage-lt);border:1.5px solid var(--sage-md);display:none;">
            <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--sage-dk);margin-bottom:.65rem;">💰 Estimasi Biaya</div>
            <div style="font-size:.81rem;color:var(--muted);">
                <div class="d-flex justify-content-between mb-1">
                    <span>🔧 Biaya servis (teknisi)</span>
                    <span id="prevService" style="color:var(--sage-dk);font-weight:600;">Mulai <?= rupiah($technicians[0]['price_start'] ?? 50000) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1" id="rowOngkir" style="display:none!important;">
                    <span>🚗 Ongkir <span id="prevDist"></span></span>
                    <span id="prevOngkir" style="font-weight:600;">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>📱 Fee platform (<?= $feePercent ?>%)</span>
                    <span id="prevFee" style="font-weight:600;">+5% biaya servis</span>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:1px dashed var(--sage-md);font-weight:800;color:var(--sage-dk);">
                <span>💳 Total COD</span>
                <span id="prevTotal">Ditentukan teknisi</span>
            </div>
            <div style="font-size:.72rem;color:var(--muted);margin-top:.35rem;">
                ℹ️ Fee platform masuk ke ReTech Hub. Teknisi menerima biaya servis + ongkir saja.
            </div>
        </div>

        <!-- Hidden selected tech -->
        <input type="hidden" name="technician_id" id="selectedTechId" value="<?= post('technician_id') ?>">

        <!-- Info teknisi terpilih -->
        <div id="techSelectedBadge" style="display:<?= post('technician_id') ? 'block' : 'none' ?>;" class="mb-3">
            <div class="p-2 rounded" style="background:var(--sage-lt);border:1px solid var(--sage-md);font-size:.8rem;color:var(--sage-dk);">
                <i class="bi bi-person-check me-1"></i>
                <span id="techSelectedName">Teknisi dipilih</span>
            </div>
        </div>

        <!-- Info COD -->
        <div class="alert py-2 px-3 mb-3" style="background:#EFF6FF;border:1px solid #BFDBFE;font-size:.78rem;color:#1E40AF;">
            <i class="bi bi-wallet2 me-1"></i><strong>Bayar COD</strong> setelah servis selesai kepada teknisi.
        </div>

        <button type="submit" class="btn btn-primary w-100" id="submitBtn" <?= !post('technician_id') ? 'disabled' : '' ?>>
            <i class="bi bi-check-circle me-2"></i>Konfirmasi Booking
        </button>
        <div class="text-center mt-1" style="font-size:.71rem;color:var(--muted);" id="techHint">
            <?= !post('technician_id') ? 'Pilih teknisi di sebelah kanan terlebih dahulu' : '' ?>
        </div>
    </div>
</div>
</div>

<!-- KANAN: Pilih Teknisi -->
<div class="col-lg-8">
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:var(--sage-lt);">
        <span style="color:var(--sage-dk);font-weight:700;"><i class="bi bi-people me-2"></i>Pilih Teknisi</span>
        <div class="d-flex gap-1 flex-wrap">
            <?php foreach (['rating'=>'⭐ Terbaik','terlaris'=>'🔥 Terlaris','tercepat'=>'⚡ Tercepat','termurah'=>'💰 Termurah'] as $k=>$lbl): ?>
            <a href="?sort=<?= $k ?><?= $deviceId ? '&device_id='.$deviceId : '' ?>"
               class="btn btn-sm <?= $sort === $k ? 'btn-success' : 'btn-outline-success' ?>"
               style="font-size:.68rem;padding:.18rem .5rem;border-radius:50px;"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-3" style="max-height:560px;overflow-y:auto;">
        <?php if (empty($technicians)): ?>
        <div class="text-center py-4 text-muted"><i class="bi bi-person-x display-4 d-block mb-2 opacity-25"></i><p>Belum ada teknisi.</p></div>
        <?php else: foreach ($technicians as $t):
            $isSel = (int)post('technician_id') === (int)$t['id'];
        ?>
        <div class="tech-card p-3 mb-2 rounded-3"
             style="border:2px solid <?= $isSel ? 'var(--sage)' : 'var(--border)' ?>;cursor:pointer;background:<?= $isSel ? 'var(--sage-lt)' : 'var(--cream)' ?>;transition:.18s;"
             id="techCard_<?= $t['id'] ?>"
             onclick="selectTech(<?= $t['id'] ?>, '<?= e($t['name'] ?? '') ?>', <?= $t['price_start'] ?? 50000 ?>, <?= $t['latitude'] ?? 'null' ?>, <?= $t['longitude'] ?? 'null' ?>, '<?= e($t['workshop_address'] ?? $t['lokasi'] ?? '') ?>')">

            <div class="d-flex align-items-start gap-3">
                <!-- Avatar -->
                <div style="width:46px;height:46px;background:linear-gradient(135deg,var(--sage-md),var(--sage));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.05rem;flex-shrink:0;">
                    <?= strtoupper(substr($t['name'] ?? 'T', 0, 1)) ?>
                </div>

                <div class="flex-grow-1">
                    <!-- Nama + rating -->
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                        <div style="font-weight:800;font-size:.9rem;"><?= e($t['name'] ?? '') ?></div>
                        <div style="font-size:.8rem;font-weight:700;color:#D97706;">
                            <?php for ($i=1;$i<=5;$i++) echo $i<=$t['rating']?'★':'☆'; ?>
                            <?= number_format($t['rating'],1) ?>
                            <span style="color:var(--muted);font-weight:400;font-size:.72rem;">(<?= $t['total_jobs'] ?>× servis)</span>
                        </div>
                    </div>

                    <!-- Keahlian -->
                    <div style="font-size:.77rem;color:var(--muted);margin-bottom:.2rem;"><?= e($t['keahlian'] ?? '') ?></div>

                    <!-- 🆕 Alamat bengkel — penting untuk customer yang mau datang -->
                    <?php if ($t['workshop_address']): ?>
                    <div style="font-size:.76rem;color:var(--sage-dk);margin-bottom:.35rem;line-height:1.4;">
                        <i class="bi bi-shop me-1"></i>
                        <strong>Bengkel:</strong> <?= e($t['workshop_address'] ?? '') ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.76rem;color:var(--muted);margin-bottom:.35rem;">
                        <i class="bi bi-geo-alt me-1"></i><?= e($t['lokasi'] ?? '') ?>
                    </div>
                    <?php endif; ?>

                    <!-- Badges -->
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge" style="background:var(--sage-lt);color:var(--sage-dk);font-size:.68rem;">
                            <i class="bi bi-lightning me-1"></i>~<?= $t['response_time'] ?> mnt
                        </span>
                        <span class="badge" style="background:#ECFDF5;color:#065F46;font-size:.68rem;">
                            <i class="bi bi-tag me-1"></i>Mulai <?= rupiah($t['price_start'] ?? 0) ?>
                        </span>
                        <?php if ($t['rating'] >= 4.8): ?>
                        <span class="badge" style="background:#FEF3C7;color:#B45309;font-size:.68rem;">🏆 Top</span>
                        <?php endif; ?>
                        <?php if ($t['response_time'] <= 20): ?>
                        <span class="badge" style="background:#DBEAFE;color:#1D4ED8;font-size:.68rem;">⚡ Cepat</span>
                        <?php endif; ?>
                    </div>

                    <!-- 🆕 Link Maps ke bengkel -->
                    <?php if ($t['latitude'] && $t['longitude']): ?>
                    <div class="mt-2">
                        <a href="https://maps.google.com/?q=<?= $t['latitude'] ?>,<?= $t['longitude'] ?>" target="_blank"
                           onclick="event.stopPropagation()"
                           style="font-size:.73rem;color:var(--sage);font-weight:600;text-decoration:none;">
                            <i class="bi bi-map me-1"></i>Lihat di Maps →
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Checkmark -->
                <div class="chk-circle" id="chk_<?= $t['id'] ?>"
                     style="width:24px;height:24px;border-radius:50%;border:2px solid <?= $isSel ? 'var(--sage)' : 'var(--border)' ?>;background:<?= $isSel ? 'var(--sage)' : 'transparent' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.18s;">
                    <?php if ($isSel): ?><i class="bi bi-check text-white" style="font-size:.75rem;"></i><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</div>

</div><!-- row -->
</form>
<?php endif; ?>
</div></div>

<script>
// Setting dari PHP
const PRICE_PER_KM  = <?= $pricePerKm ?>;
const MIN_FEE       = <?= $minFee ?>;
const FEE_PERCENT   = <?= $feePercent ?>;
const MAX_DIST      = <?= $maxDist ?>;

// Data teknisi terpilih
let selectedTech    = null;
let customerLat     = parseFloat(document.getElementById('customerLat').value) || null;
let customerLng     = parseFloat(document.getElementById('customerLng').value) || null;

function rupiah(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function calcDistKm(lat1, lng1, lat2, lng2) {
    const R  = 6371;
    const dL = (lat2-lat1) * Math.PI/180;
    const dG = (lng2-lng1) * Math.PI/180;
    const a  = Math.sin(dL/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dG/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function updatePricePreview() {
    const type = document.querySelector('input[name="service_type"]:checked')?.value;

    if (!selectedTech) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }

    document.getElementById('pricePreview').style.display = 'block';
    document.getElementById('prevService').textContent = 'Mulai ' + rupiah(selectedTech.priceStart);

    let distKm = 0, transportFee = 0;

    if (type === 'home_visit' && customerLat && customerLng && selectedTech.lat && selectedTech.lng) {
        distKm = calcDistKm(customerLat, customerLng, selectedTech.lat, selectedTech.lng);
        transportFee = Math.max(Math.round(distKm * PRICE_PER_KM), MIN_FEE);

        const ongkirRow = document.getElementById('rowOngkir');
        ongkirRow.style.display = 'flex';
        document.getElementById('prevDist').textContent = '(' + distKm.toFixed(1) + ' km)';
        document.getElementById('prevOngkir').textContent = rupiah(transportFee);

        // Update estimate box
        const estBox = document.getElementById('ongkirEstimate');
        const estDetail = document.getElementById('ongkirDetail');
        estBox.style.display = 'block';
        estDetail.innerHTML = 
    '📍 Dari <strong>alamatmu</strong> ke <strong>bengkel teknisi</strong><br>' +
    distKm.toFixed(1) + ' km × Rp ' + PRICE_PER_KM.toLocaleString('id') + '/km = <strong>' + rupiah(transportFee) + '</strong><br>' +
    '<span style="font-size:.71rem;opacity:.8;">Ongkir dihitung otomatis berdasarkan jarak GPS</span>';

        // Update hidden fields
        document.getElementById('customerLat').value = customerLat;
        document.getElementById('customerLng').value = customerLng;

        if (distKm > MAX_DIST) {
            estDetail.innerHTML += '<br>⚠️ Jarak melebihi ' + MAX_DIST + ' km. Hubungi teknisi untuk konfirmasi.';
            estBox.style.background = '#FFFBEB';
        }
    } else {
        document.getElementById('rowOngkir').style.display = 'none';
        document.getElementById('ongkirEstimate').style.display = 'none';
    }

    const appFee    = Math.round(selectedTech.priceStart * FEE_PERCENT / 100);
    const totalMin  = selectedTech.priceStart + transportFee + appFee;
    document.getElementById('prevFee').textContent  = rupiah(appFee) + ' (estimasi)';
    document.getElementById('prevTotal').textContent = 'Mulai ' + rupiah(totalMin);
}

function selectTech(id, name, priceStart, lat, lng, workshopAddr) {
    // Reset semua card
    document.querySelectorAll('.tech-card').forEach(c => {
        c.style.border = '2px solid var(--border)';
        c.style.background = 'var(--cream)';
    });
    document.querySelectorAll('.chk-circle').forEach(c => {
        c.style.border = '2px solid var(--border)';
        c.style.background = 'transparent';
        c.innerHTML = '';
    });

    // Aktifkan yang dipilih
    const card = document.getElementById('techCard_' + id);
    if (card) { card.style.border = '2px solid var(--sage)'; card.style.background = 'var(--sage-lt)'; }

    const chk = document.getElementById('chk_' + id);
    if (chk) {
        chk.style.border = '2px solid var(--sage)';
        chk.style.background = 'var(--sage)';
        chk.innerHTML = '<i class="bi bi-check text-white" style="font-size:.75rem;"></i>';
    }

    selectedTech = { id, name, priceStart, lat, lng };
    document.getElementById('selectedTechId').value = id;
    document.getElementById('techSelectedBadge').style.display = 'block';
    document.getElementById('techSelectedName').textContent = name + ' dipilih ✓';
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-circle me-2"></i>Booking dengan ' + name;
    document.getElementById('techHint').textContent = '';

    updatePricePreview();
}

function toggleHomeVisit(show) {
    document.getElementById('homeVisitFields').style.display = show ? 'block' : 'none';
    if (!show) {
        document.getElementById('rowOngkir').style.display = 'none';
        document.getElementById('ongkirEstimate').style.display = 'none';
    }
    updatePricePreview();
}

// Listen perubahan alamat
document.getElementById('customerAddress')?.addEventListener('change', function() {
    // Di production: pakai Google Geocoding API
    // Untuk sekarang, koordinat diinput manual atau dari profile user
    updatePricePreview();
});

// Init
document.querySelectorAll('input[name="service_type"]').forEach(r => r.addEventListener('change', () => toggleHomeVisit(r.value === 'home_visit')));
updatePricePreview();
</script>

<?php pageFooter(); ?>
