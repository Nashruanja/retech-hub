<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$id  = (int)get('id');
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT sr.*, d.device_name, d.brand, d.device_type, d.user_id,
           u2.name AS tech_name, u2.phone AS tech_phone,
           t.lokasi AS tech_lokasi, t.id AS tid, t.workshop_address
    FROM service_requests sr
    JOIN devices d ON sr.device_id = d.id
    LEFT JOIN technicians t ON sr.technician_id = t.id
    LEFT JOIN users u2 ON t.user_id = u2.id
    WHERE sr.id = ? AND d.user_id = ?
");
$stmt->execute([$id, $uid]); $s = $stmt->fetch();
if (!$s) redirect('/user/service/index.php','Data tidak ditemukan.');

$inWarranty      = !empty($s['warranty_until']) && strtotime($s['warranty_until']) >= time();
$warrantyDaysLeft= !empty($s['warranty_until']) ? max(0, ceil((strtotime($s['warranty_until'])-time())/86400)) : 0;

// Handle rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'rate') {
    verifyCsrf();
    $rating = (int)post('rating');
    $note   = post('rating_note');
    if ($rating >= 1 && $rating <= 5 && $s['status'] === 'selesai' && !$s['rating_by_user']) {
        $pdo->prepare("UPDATE service_requests SET rating_by_user=?,rating_note=?,updated_at=NOW() WHERE id=?")
            ->execute([$rating, $note ?: null, $id]);
        if ($s['tid']) {
            $pdo->prepare("UPDATE technicians SET rating=ROUND((rating*total_jobs+?)/(total_jobs+0.001),1) WHERE id=?")
                ->execute([$rating, $s['tid']]);
        }
        redirect('/user/service/show.php?id='.$id,'Rating berhasil diberikan! Terima kasih.','success');
    }
}

$steps = ['menunggu'=>1,'diproses'=>2,'selesai'=>3,'tidak_bisa_diperbaiki'=>3];
$cur   = $steps[$s['status']] ?? 1;
$borderColor = ['menunggu'=>'#F59E0B','diproses'=>'#7BC8E0','selesai'=>'#4D7C5F','tidak_bisa_diperbaiki'=>'#E8826A'][$s['status']] ?? '#ccc';

pageHeader('Detail Servis','Tracking Servis #'.str_pad($id,5,'0',STR_PAD_LEFT));
?>

<div class="row justify-content-center"><div class="col-lg-8">

<!-- Status + Progress -->
<div class="card mb-4" style="border-left:4px solid <?= $borderColor ?>">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:.3rem;">Status Servis</div>
                <span class="badge bg-<?= statusBadge($s['status']) ?>" style="font-size:.95rem;padding:.4rem .9rem;"><?= statusLabel($s['status']) ?></span>
            </div>
            <div class="text-end">
                <div style="font-size:.68rem;color:var(--muted);">ID Servis</div>
                <div style="font-weight:800;font-size:1rem;">#<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></div>
            </div>
        </div>
        <!-- Progress -->
        <div class="d-flex align-items-center">
            <?php foreach (['Menunggu'=>1,'Diproses'=>2,'Selesai'=>3] as $lbl=>$step):
                $done = $step <= $cur; $last = $step === 3; ?>
            <div class="text-center flex-fill">
                <div class="mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center"
                     style="width:28px;height:28px;font-size:.72rem;font-weight:800;background:<?= $done?'var(--sage)':'var(--border)' ?>;color:<?= $done?'#fff':'var(--muted)' ?>;">
                    <?= $step<$cur?'✓':$step ?>
                </div>
                <div style="font-size:.67rem;color:<?= $done?'var(--sage)':'var(--muted)' ?>;font-weight:700;"><?= $lbl ?></div>
            </div>
            <?php if (!$last): ?><div class="flex-fill" style="height:2px;background:<?= $step<$cur?'var(--sage)':'var(--border)' ?>;margin-bottom:1.3rem;"></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Garansi (jika selesai & ada) -->
<?php if ($s['status'] === 'selesai' && ($s['warranty_days'] ?? 0) > 0): ?>
<div class="card mb-4" style="border:2px solid <?= $inWarranty?'#A7D3B5':'#D0E8D8' ?>;">
    <div class="card-body p-3 d-flex align-items-center gap-3">
        <div style="font-size:2rem;flex-shrink:0;"><?= $inWarranty?'🛡️':'🔒' ?></div>
        <div class="flex-grow-1">
            <div style="font-weight:700;font-size:.9rem;color:<?= $inWarranty?'#2D5016':'var(--muted)' ?>;">
                <?= $inWarranty ? "Garansi Aktif — Tersisa {$warrantyDaysLeft} hari" : "Masa Garansi Berakhir" ?>
            </div>
            <div style="font-size:.78rem;color:var(--muted);">
                <?= e($s['warranty_notes'] ?? '') ?> · s/d <?= tglIndo($s['warranty_until'] ?? '') ?>
            </div>
        </div>
        <?php if ($inWarranty && $s['tech_phone']): ?>
        <a href="https://wa.me/62<?= ltrim($s['tech_phone'],'0') ?>?text=<?= urlencode('Halo, saya ingin klaim garansi untuk servis #'.str_pad($id,5,'0',STR_PAD_LEFT)) ?>"
           target="_blank" class="btn btn-sm btn-success flex-shrink-0" style="background:#25D366;border-color:#25D366;font-size:.78rem;">
            <i class="bi bi-whatsapp me-1"></i>Klaim Garansi
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Detail -->
<div class="card mb-4">
    <div class="card-header" style="background:var(--sage-lt);"><i class="bi bi-info-circle me-2" style="color:var(--sage)"></i>Detail Servis</div>
    <div class="card-body p-4">
        <div class="row g-3" style="font-size:.87rem;">
            <div class="col-sm-6">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Perangkat</div>
                <div class="fw-bold mt-1"><?= e($s['device_name'] ?? '') ?></div>
                <div class="text-muted" style="font-size:.8rem;"><?= e($s['brand'] ?? '') ?></div>
            </div>
            <div class="col-sm-6">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Teknisi</div>
                <div class="fw-bold mt-1"><?= e($s['tech_name'] ?? '-') ?></div>
                <div class="text-muted" style="font-size:.78rem;"><?= e($s['tech_lokasi'] ?? '') ?></div>
                <?php if ($s['workshop_address']): ?>
                <div style="font-size:.75rem;color:var(--sage-dk);"><i class="bi bi-shop me-1"></i><?= e($s['workshop_address']) ?></div>
                <?php endif; ?>
                <?php if ($s['tech_phone']): ?>
                <a href="https://wa.me/62<?= ltrim($s['tech_phone'],'0') ?>?text=<?= urlencode('Halo, saya tanya tentang servis #'.str_pad($id,5,'0',STR_PAD_LEFT)) ?>"
                   target="_blank" style="font-size:.73rem;color:#25D366;font-weight:600;text-decoration:none;">
                    <i class="bi bi-whatsapp me-1"></i>Chat Teknisi
                </a>
                <?php endif; ?>
            </div>
            <div class="col-sm-6">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Tanggal</div>
                <div class="mt-1"><?= tglIndo($s['service_date']) ?></div>
            </div>
            <div class="col-sm-6">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Jenis</div>
                <div class="mt-1"><?= $s['service_type']==='home_visit'?'🏠 Teknisi ke Rumah':'🏪 Bawa ke Tempat' ?></div>
            </div>
            <div class="col-12">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Keluhan</div>
                <div class="mt-1"><?= e($s['complaint'] ?? '') ?></div>
            </div>

            <!-- Rincian harga -->
            <?php if ($s['total_cost']): ?>
            <div class="col-12">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Rincian Biaya</div>
                <div class="mt-2 p-3 rounded" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:.84rem;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">🔧 Biaya servis</span>
                        <span><?= rupiah($s['service_cost'] ?? 0) ?></span>
                    </div>
                    <?php if (($s['transport_fee'] ?? 0) > 0): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">🚗 Ongkir <?= $s['distance_km']>0?'('.$s['distance_km'].' km)':'' ?></span>
                        <span><?= rupiah($s['transport_fee']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">📱 Fee platform (<?= getSetting('app_fee_percent','5') ?>%)</span>
                        <span><?= rupiah($s['app_fee'] ?? 0) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:1.5px dashed #A7F3D0;font-weight:800;">
                        <span style="color:#065F46;">💳 Total Bayar (COD)</span>
                        <span style="color:#065F46;font-size:1.05rem;"><?= rupiah($s['total_cost']) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($s['notes']): ?>
            <div class="col-12">
                <div class="text-muted" style="font-size:.68rem;font-weight:700;text-transform:uppercase;">Catatan Teknisi</div>
                <div class="mt-1 p-2 rounded" style="background:var(--sand);font-size:.87rem;"><?= e($s['notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rating -->
<?php if ($s['status'] === 'selesai' && !$s['rating_by_user']): ?>
<div class="card mb-4" style="border:2px solid var(--border);">
    <div class="card-body p-4">
        <h6 style="font-weight:700;margin-bottom:1rem;"><i class="bi bi-star me-2 text-warning"></i>Beri Rating Teknisi</h6>
        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="rate">
            <div class="d-flex gap-2 flex-wrap mb-3">
                <?php for ($i=1;$i<=5;$i++): ?>
                <label style="cursor:pointer;">
                    <input type="radio" name="rating" value="<?= $i ?>" class="d-none" required>
                    <div onclick="setRating(<?= $i ?>)" id="rstar_<?= $i ?>"
                         style="width:44px;height:44px;border:2px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;transition:.15s;background:#fff;cursor:pointer;">⭐</div>
                </label>
                <?php endfor; ?>
                <span id="ratingLabel" style="align-self:center;font-size:.85rem;color:var(--muted);margin-left:.5rem;"></span>
            </div>
            <div class="mb-3"><input type="text" name="rating_note" class="form-control" placeholder="Komentar opsional..."></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Kirim Rating</button>
        </form>
    </div>
</div>
<?php elseif ($s['rating_by_user']): ?>
<div class="card mb-4"><div class="card-body p-3">
    <div style="font-size:.78rem;font-weight:700;color:var(--muted);">Rating kamu</div>
    <div style="font-size:1.3rem;"><?= str_repeat('⭐',$s['rating_by_user']) ?><?= str_repeat('☆',5-$s['rating_by_user']) ?></div>
    <?php if($s['rating_note']): ?><div style="font-size:.82rem;color:var(--muted);">"<?= e($s['rating_note']) ?>"</div><?php endif; ?>
</div></div>
<?php endif; ?>

<!-- E-Waste -->
<?php if ($s['status'] === 'tidak_bisa_diperbaiki'): ?>
<div class="card mb-4" style="border:2px solid #FECDD3;">
    <div class="card-body p-4 d-flex gap-3">
        <div style="font-size:2.5rem;flex-shrink:0;">♻️</div>
        <div>
            <h6 style="font-weight:800;color:#BE123C;margin-bottom:.5rem;">Perangkat Tidak Bisa Diperbaiki</h6>
            <p style="color:var(--muted);font-size:.87rem;margin-bottom:.75rem;">Jangan buang sembarangan! Kelola sebagai e-waste secara bertanggung jawab.</p>
            <a href="<?= BASE_URL ?>/ewaste/index.php" class="btn btn-sm" style="background:#FECDD3;color:#BE123C;border:none;font-weight:700;border-radius:8px;">
                <i class="bi bi-recycle me-1"></i>Cari Lokasi E-Waste
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div></div>

<script>
const labels = {1:'Sangat Buruk 😞',2:'Buruk 😕',3:'Cukup 😐',4:'Bagus 😊',5:'Sangat Bagus 🤩'};
function setRating(val){
    for(let i=1;i<=5;i++){
        const el=document.getElementById('rstar_'+i);
        if(el){el.style.background=i<=val?'#FEF3C7':'#fff';el.style.borderColor=i<=val?'#F59E0B':'var(--border)';}
    }
    document.querySelector('input[name="rating"][value="'+val+'"]').checked=true;
    document.getElementById('ratingLabel').textContent=labels[val]||'';
}
</script>
<?php pageFooter(); ?>
