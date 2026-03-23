<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('technician');

$pdo = getDB();
$uid = $_SESSION['user_id'];
$id  = (int)get('id');

$techStmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id=?");
$techStmt->execute([$uid]); $tech = $techStmt->fetch();
if (!$tech) redirect('/technician/dashboard.php');
$tid = $tech['id'];

$stmt = $pdo->prepare("
    SELECT sr.*, d.device_name, d.brand, d.device_type,
           u.name AS customer_name, u.phone AS customer_phone, u.address AS customer_address,
           sr.customer_address AS service_address, sr.distance_km
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE sr.id = ? AND sr.technician_id = ?
");
$stmt->execute([$id, $tid]); $s = $stmt->fetch();
if (!$s) redirect('/technician/services.php', 'Data tidak ditemukan.');

$feePercent = (float)getSetting('app_fee_percent', '5');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $status        = post('status');
    $service_cost  = (float)post('service_cost');
    $transport_fee = (float)post('transport_fee');
    $notes         = post('notes');
    $warranty_days = (int)post('warranty_days');
    $warranty_notes= post('warranty_notes');

    $allowed = ['menunggu','diproses','selesai','tidak_bisa_diperbaiki'];
    if (!in_array($status, $allowed)) redirect('/technician/services.php', 'Status tidak valid.');

    $app_fee      = $service_cost > 0 ? round($service_cost * $feePercent / 100) : 0;
    $total_cost   = $service_cost + $transport_fee + $app_fee;
    $warranty_until = $warranty_days > 0 ? date('Y-m-d', strtotime("+{$warranty_days} days")) : null;

    $pdo->prepare("UPDATE service_requests SET
        status=?, service_cost=?, transport_fee=?, app_fee=?, total_cost=?, cost=?,
        notes=?, warranty_days=?, warranty_notes=?, warranty_until=?, updated_at=NOW()
        WHERE id=? AND technician_id=?")
        ->execute([$status,
            $service_cost > 0 ? $service_cost : null,
            $transport_fee, $app_fee,
            $total_cost > 0 ? $total_cost : null,
            $total_cost > 0 ? $total_cost : null,
            $notes ?: null, $warranty_days, $warranty_notes ?: null, $warranty_until,
            $id, $tid]);

    if ($status === 'selesai' && $s['status'] !== 'selesai') {
        $pdo->prepare("UPDATE technicians SET total_jobs=total_jobs+1 WHERE id=?")->execute([$tid]);
    }
    redirect('/technician/update.php?id='.$id, 'Berhasil diperbarui!', 'success');
}

$curService  = (float)($s['service_cost'] ?? 0);
$curTransport= (float)($s['transport_fee'] ?? 0);
$curAppFee   = (float)($s['app_fee'] ?? 0);
$curTotal    = (float)($s['total_cost'] ?? 0);

pageHeader('Update Servis','Update Servis #'.str_pad($id,5,'0',STR_PAD_LEFT));
?>

<div class="row justify-content-center"><div class="col-lg-10">
<div class="row g-4">

<!-- Kiri: Info -->
<div class="col-md-4">
    <div class="card mb-3">
        <div class="card-header" style="background:var(--sage-lt);"><i class="bi bi-info-circle me-2" style="color:var(--sage)"></i>Info Permintaan</div>
        <div class="card-body p-4">
            <dl class="row mb-0" style="font-size:.85rem;">
                <dt class="col-5 text-muted">ID</dt>
                <dd class="col-7 fw-bold">#<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></dd>
                <dt class="col-5 text-muted">Pelanggan</dt>
                <dd class="col-7"><?= e($s['customer_name'] ?? '') ?></dd>
                <dt class="col-5 text-muted">Telepon</dt>
                <dd class="col-7">
                    <?php if ($s['customer_phone']): ?>
                    <a href="https://wa.me/62<?= ltrim($s['customer_phone'],'0') ?>?text=<?= urlencode('Halo, saya teknisi dari ReTech Hub mengenai servis #'.str_pad($id,5,'0',STR_PAD_LEFT)) ?>"
                       target="_blank" style="color:#25D366;font-weight:600;text-decoration:none;font-size:.8rem;">
                        <i class="bi bi-whatsapp me-1"></i><?= e($s['customer_phone']) ?>
                    </a>
                    <?php else: echo '-'; endif; ?>
                </dd>
                <dt class="col-5 text-muted">Perangkat</dt>
                <dd class="col-7"><?= e($s['device_name'] ?? '') ?></dd>
                <dt class="col-5 text-muted">Merek</dt>
                <dd class="col-7"><?= e($s['brand'] ?? '') ?></dd>
                <dt class="col-5 text-muted">Tanggal</dt>
                <dd class="col-7"><?= tglIndo($s['service_date']) ?></dd>
                <dt class="col-5 text-muted">Jenis</dt>
                <dd class="col-7">
                    <?= $s['service_type'] === 'home_visit'
                        ? '<span class="badge bg-warning">🏠 Ke Rumah</span>'
                        : '<span class="badge bg-secondary">🏪 Bawa Masuk</span>' ?>
                </dd>
                <?php if ($s['service_type'] === 'home_visit' && ($s['service_address'] ?? $s['customer_address'])): ?>
                <dt class="col-5 text-muted">Alamat</dt>
                <dd class="col-7" style="font-size:.78rem;"><?= e($s['service_address'] ?? $s['customer_address'] ?? '') ?></dd>
                <?php endif; ?>
                <?php if ($s['distance_km'] > 0): ?>
                <dt class="col-5 text-muted">Jarak</dt>
                <dd class="col-7"><?= number_format($s['distance_km'],1) ?> km</dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="bi bi-chat-left me-2"></i>Keluhan</div>
        <div class="card-body p-3">
            <p style="font-size:.86rem;line-height:1.7;margin:0;"><?= e($s['complaint'] ?? '') ?></p>
            <?php if ($s['diagnosis']): ?>
            <hr class="my-2">
            <div style="font-size:.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:.3rem;">Diagnosa AI</div>
            <p style="font-size:.8rem;color:#6B7280;line-height:1.6;margin:0;"><?= e($s['diagnosis']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Kanan: Form Update -->
<div class="col-md-8">
<div class="card">
    <div class="card-header" style="background:#FFFBEB;"><i class="bi bi-pencil-square me-2 text-warning"></i>Update Status & Harga</div>
    <div class="card-body p-4">
        <?php showFlash(); ?>
        <form method="POST">
            <?php csrfField(); ?>

            <!-- Status -->
            <div class="mb-4">
                <label class="form-label">Status Servis *</label>
                <div class="row g-2">
                    <?php foreach ([
                        'menunggu'              => ['Menunggu',            'warning','hourglass'],
                        'diproses'              => ['Diproses',            'info',   'tools'],
                        'selesai'               => ['Selesai',             'success','check-circle'],
                        'tidak_bisa_diperbaiki' => ['Tdk Bisa Diperbaiki', 'danger', 'x-circle'],
                    ] as $val => [$lbl,$col,$ico]): ?>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="status" id="st_<?= $val ?>" value="<?= $val ?>"
                               <?= ($s['status'] ?? 'menunggu') === $val ? 'checked' : '' ?>>
                        <label class="btn btn-outline-<?= $col ?> w-100 text-start py-2" for="st_<?= $val ?>" style="font-size:.82rem;">
                            <i class="bi bi-<?= $ico ?> me-1"></i><?= $lbl ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sistem Harga -->
            <div class="p-3 rounded-3 mb-4" style="background:#F0FDF4;border:1.5px solid #BBF7D0;">
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#065F46;margin-bottom:.85rem;">
                    💰 Rincian Harga
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:.8rem;">🔧 Biaya Servis (Rp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="service_cost" id="serviceCost" class="form-control"
                                   placeholder="0" min="0" value="<?= (int)$curService ?>" oninput="calcTotal()">
                        </div>
                        <div class="form-text" style="font-size:.7rem;">Teknisi menerima biaya ini</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:.8rem;">
                            🚗 Ongkir (Rp)
                            <?= $s['service_type'] !== 'home_visit' ? '<span style="color:#4D7C5F;font-size:.72rem;">— Gratis</span>' : '' ?>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="transport_fee" id="transportFee" class="form-control"
                                   placeholder="0" min="0" value="<?= (int)$curTransport ?>"
                                   <?= $s['service_type'] !== 'home_visit' ? 'readonly style="background:#F0FDF4;"' : '' ?>
                                   oninput="calcTotal()">
                        </div>
                        <?php if ($s['distance_km'] > 0): ?>
                        <div class="form-text" style="font-size:.7rem;color:#4D7C5F;">
                            Jarak: <?= number_format($s['distance_km'],1) ?> km · Estimasi: <?= rupiah(calcTransportFee($s['distance_km'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:.8rem;">📱 Fee Platform (<?= $feePercent ?>% — masuk ke ReTech Hub)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="appFeeDisplay" class="form-control" readonly
                                   style="background:#F8FAFB;color:#6B7280;"
                                   value="<?= number_format((int)$curAppFee,0,',','.') ?>">
                        </div>
                        <div class="form-text" style="font-size:.7rem;">Otomatis <?= $feePercent ?>% dari biaya servis</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" style="font-size:.8rem;font-weight:800;color:#065F46;">💳 TOTAL BAYAR CUSTOMER (COD)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background:#D1FAE5;border-color:#A7F3D0;color:#065F46;font-weight:700;">Rp</span>
                            <input type="text" id="totalDisplay" class="form-control" readonly
                                   style="background:#ECFDF5;color:#065F46;font-weight:800;font-size:.95rem;border-color:#A7F3D0;"
                                   value="<?= number_format((int)$curTotal,0,',','.') ?>">
                        </div>
                    </div>
                </div>

                <!-- Keterangan pembagian -->
                <div id="priceBreakdown" class="mt-3 p-2 rounded" style="background:#fff;border:1px dashed #BBF7D0;font-size:.75rem;color:#6B7280;<?= $curService>0?'':'display:none;' ?>">
                    <strong style="color:#065F46;">Kamu (teknisi) terima:</strong>
                    Biaya servis Rp <span id="bdService"><?= number_format((int)$curService,0,',','.') ?></span>
                    + Ongkir Rp <span id="bdTransport"><?= number_format((int)$curTransport,0,',','.') ?></span>
                    = <strong>Rp <span id="bdTechTotal"><?= number_format((int)($curService+$curTransport),0,',','.') ?></span></strong><br>
                    <strong style="color:#E8826A;">ReTech Hub terima:</strong>
                    Fee platform Rp <span id="bdFee"><?= number_format((int)$curAppFee,0,',','.') ?></span>
                </div>
            </div>

            <!-- Catatan -->
            <div class="mb-4">
                <label class="form-label">Catatan Teknisi</label>
                <textarea name="notes" rows="4" class="form-control"
                          placeholder="Hasil servis, komponen yang diganti, saran perawatan..."><?= e($s['notes'] ?? '') ?></textarea>
            </div>

            <!-- Garansi -->
            <div class="p-3 rounded-3 mb-4" style="background:#EFF6FF;border:1.5px solid #BFDBFE;">
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#1E40AF;margin-bottom:.75rem;">🛡️ Garansi Servis</div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" style="font-size:.8rem;">Lama Garansi (hari)</label>
                        <input type="number" name="warranty_days" class="form-control form-control-sm"
                               placeholder="0" min="0" max="365" value="<?= (int)($s['warranty_days'] ?? 0) ?>">
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label" style="font-size:.8rem;">Keterangan Garansi</label>
                        <input type="text" name="warranty_notes" class="form-control form-control-sm"
                               placeholder="Contoh: Garansi baterai baru 90 hari"
                               value="<?= e($s['warranty_notes'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Perubahan</button>
                <a href="services.php" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
</div>

</div></div></div>

<script>
const FEE_PERCENT = <?= $feePercent ?>;
function fmt(n){return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
function calcTotal(){
    const s=parseFloat(document.getElementById('serviceCost').value)||0;
    const t=parseFloat(document.getElementById('transportFee').value)||0;
    const f=Math.round(s*FEE_PERCENT/100);
    const total=s+t+f;
    document.getElementById('appFeeDisplay').value=fmt(f);
    document.getElementById('totalDisplay').value=fmt(total);
    document.getElementById('bdService').textContent=fmt(s);
    document.getElementById('bdTransport').textContent=fmt(t);
    document.getElementById('bdFee').textContent=fmt(f);
    document.getElementById('bdTechTotal').textContent=fmt(s+t);
    document.getElementById('priceBreakdown').style.display=s>0?'block':'none';
}
calcTotal();
</script>
<?php pageFooter(); ?>
