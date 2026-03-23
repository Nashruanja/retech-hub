<?php
// admin/technician_verify.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$pdo = getDB();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = post('action');
    $tid    = (int)post('tech_id');
    $reason = post('reject_reason');

    if ($action === 'approve') {
        $pdo->prepare("UPDATE technicians SET is_verified=1,is_available=1,verified_at=NOW(),reject_reason=NULL WHERE id=?")
            ->execute([$tid]);
        // Aktifkan user role-nya tetap technician
        redirect('/admin/technician_verify.php', '✅ Teknisi berhasil diverifikasi!', 'success');

    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE technicians SET is_verified=2,is_available=0,reject_reason=?,verified_at=NOW() WHERE id=?")
            ->execute([$reason ?: 'Tidak memenuhi syarat', $tid]);
        redirect('/admin/technician_verify.php', '❌ Teknisi ditolak.', 'success');

    } elseif ($action === 'revoke') {
        $pdo->prepare("UPDATE technicians SET is_verified=0,is_available=0,verified_at=NULL WHERE id=?")
            ->execute([$tid]);
        redirect('/admin/technician_verify.php', 'Status teknisi direset.', 'success');
    }
}

// Filter berdasarkan status
$filter = get('status', 'pending');
$whereMap = [
    'pending'  => 'WHERE t.is_verified = 0',
    'verified' => 'WHERE t.is_verified = 1',
    'rejected' => 'WHERE t.is_verified = 2',
    'all'      => '',
];
$where = $whereMap[$filter] ?? $whereMap['pending'];

$techs = $pdo->query("
    SELECT t.*,u.name,u.email,u.phone,u.created_at AS registered_at
    FROM technicians t
    JOIN users u ON t.user_id=u.id
    $where
    ORDER BY u.created_at DESC
")->fetchAll();

// Hitung badge pending
$pendingCount = $pdo->query("SELECT COUNT(*) FROM technicians WHERE is_verified=0")->fetchColumn();

pageHeader('Verifikasi Teknisi', 'Verifikasi Teknisi');
?>

<!-- Filter tabs -->
<div class="d-flex gap-2 flex-wrap mb-4 align-items-center">
    <?php foreach (['pending'=>'⏳ Menunggu','verified'=>'✅ Terverifikasi','rejected'=>'❌ Ditolak','all'=>'Semua'] as $k=>$lbl): ?>
    <a href="technician_verify.php?status=<?= $k ?>"
       class="btn btn-sm <?= $filter===$k?'btn-primary':'btn-outline-secondary' ?>"
       style="border-radius:50px;font-size:.8rem;">
        <?= $lbl ?>
        <?php if ($k==='pending' && $pendingCount > 0): ?>
        <span class="badge ms-1" style="background:#fff;color:var(--sage-dk);font-size:.7rem;"><?= $pendingCount ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($techs)): ?>
<div class="card text-center py-5">
    <div class="card-body text-muted">
        <i class="bi bi-person-check display-4 d-block mb-3 opacity-25"></i>
        <h5><?= $filter === 'pending' ? 'Tidak ada teknisi yang menunggu verifikasi' : 'Tidak ada data' ?></h5>
    </div>
</div>
<?php else: ?>

<div class="row g-3">
<?php foreach ($techs as $t):
    $statusBg  = ['0'=>'#FFFBEB','1'=>'#E8F5EE','2'=>'#FFF1F2'][$t['is_verified']] ?? '#F4F8F5';
    $statusBdr = ['0'=>'#FDE68A','1'=>'#C5E0CF','2'=>'#FECDD3'][$t['is_verified']] ?? '#D0E8D8';
    $statusLabel = ['0'=>'⏳ Menunggu','1'=>'✅ Terverifikasi','2'=>'❌ Ditolak'][$t['is_verified']] ?? '-';
?>
<div class="col-lg-6">
<div class="card" style="border:1.5px solid <?= $statusBdr ?>;">
    <div class="card-body p-4">
        <!-- Header -->
        <div class="d-flex align-items-start justify-content-between mb-3">
            <div class="d-flex gap-3 align-items-center">
                <div style="width:46px;height:46px;background:linear-gradient(135deg,#A7D3B5,#4D7C5F);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.1rem;flex-shrink:0;">
                    <?= strtoupper(substr($t['name']??'T',0,1)) ?>
                </div>
                <div>
                    <div style="font-weight:800;font-size:.97rem;"><?= e($t['name']??'') ?></div>
                    <div style="font-size:.77rem;color:var(--muted,#6B8F78);"><?= e($t['email']??'') ?></div>
                </div>
            </div>
            <span class="badge" style="background:<?= $statusBg ?>;color:<?= $t['is_verified']=='1'?'#2D5016':($t['is_verified']=='2'?'#9F1239':'#B45309') ?>;font-size:.75rem;border-radius:8px;">
                <?= $statusLabel ?>
            </span>
        </div>

        <!-- Detail -->
        <div class="row g-2 mb-3" style="font-size:.83rem;">
            <div class="col-6">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">Keahlian</div>
                <div><?= e($t['keahlian']??'-') ?></div>
            </div>
            <div class="col-6">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">Kota/Area</div>
                <div><?= e($t['lokasi']??'-') ?></div>
            </div>
            <div class="col-12">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">Alamat Bengkel</div>
                <div><?= e($t['workshop_address']??'Belum diisi') ?></div>
            </div>
            <?php if ($t['bio']): ?>
            <div class="col-12">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">Bio</div>
                <div style="color:#6B7280;">"<?= e(limitStr($t['bio']??'',100)) ?>"</div>
            </div>
            <?php endif; ?>
            <div class="col-6">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">WhatsApp</div>
                <div>
                    <?php if ($t['phone']): ?>
                    <a href="https://wa.me/62<?= ltrim($t['phone'],'0') ?>?text=<?= urlencode('Halo '.$t['name'].', kami dari ReTech Hub ingin mengkonfirmasi pendaftaran teknisi Anda.') ?>"
                       target="_blank" style="color:#25D366;font-weight:600;text-decoration:none;font-size:.82rem;">
                        <i class="bi bi-whatsapp me-1"></i><?= e($t['phone']??'') ?>
                    </a>
                    <?php else: echo '<span style="color:#FECDD3;">Tidak ada</span>'; endif; ?>
                </div>
            </div>
            <div class="col-6">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:var(--muted,#6B8F78);">Daftar</div>
                <div><?= tglIndo($t['registered_at']) ?></div>
            </div>
            <?php if ($t['reject_reason']): ?>
            <div class="col-12">
                <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:#9F1239;">Alasan Penolakan</div>
                <div style="color:#9F1239;font-size:.82rem;"><?= e($t['reject_reason']??'') ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tombol Aksi -->
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($t['is_verified'] == 0): ?>
            <!-- PENDING: tombol approve & reject -->
            <form method="POST" class="m-0" onsubmit="return confirm('Verifikasi teknisi <?= e($t['name']??'') ?>?')">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="tech_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-success" style="font-size:.8rem;">
                    <i class="bi bi-check-circle me-1"></i>Verifikasi & Aktifkan
                </button>
            </form>
            <button class="btn btn-sm btn-danger" style="font-size:.8rem;"
                    onclick="showRejectForm(<?= $t['id'] ?>, '<?= e($t['name']??'') ?>')">
                <i class="bi bi-x-circle me-1"></i>Tolak
            </button>

            <?php elseif ($t['is_verified'] == 1): ?>
            <!-- VERIFIED: tombol revoke -->
            <form method="POST" class="m-0" onsubmit="return confirm('Cabut verifikasi teknisi ini?')">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="revoke">
                <input type="hidden" name="tech_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-outline-warning" style="font-size:.8rem;">
                    <i class="bi bi-shield-x me-1"></i>Cabut Verifikasi
                </button>
            </form>

            <?php elseif ($t['is_verified'] == 2): ?>
            <!-- REJECTED: tombol re-review -->
            <form method="POST" class="m-0" onsubmit="return confirm('Reset status & verifikasi ulang?')">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="tech_id" value="<?= $t['id'] ?>">
                <button class="btn btn-sm btn-success" style="font-size:.8rem;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Verifikasi Ulang
                </button>
            </form>
            <?php endif; ?>

            <!-- Kontak WA -->
            <?php if ($t['phone']): ?>
            <a href="https://wa.me/62<?= ltrim($t['phone'],'0') ?>?text=<?= urlencode('Halo '.$t['name'].', kami dari ReTech Hub.') ?>"
               target="_blank" class="btn btn-sm" style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;font-size:.8rem;border-radius:8px;">
                <i class="bi bi-whatsapp me-1"></i>Chat WA
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php endforeach; ?>
</div><!-- row -->

<?php endif; ?>

<!-- Modal Form Penolakan -->
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:2rem;max-width:440px;width:90%;box-shadow:0 16px 48px rgba(0,0,0,.2);">
        <h6 style="font-weight:800;color:#1A3A28;margin-bottom:1rem;">❌ Tolak Pendaftaran Teknisi</h6>
        <p id="rejectModalDesc" style="font-size:.85rem;color:#6B7280;margin-bottom:1rem;"></p>
        <form method="POST">
            <?php csrfField(); ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="tech_id" id="rejectTechId">
            <div class="mb-3">
                <label style="font-size:.8rem;font-weight:700;color:#1A3A28;margin-bottom:.3rem;display:block;">Alasan Penolakan</label>
                <textarea name="reject_reason" rows="3"
                          style="width:100%;border:1.5px solid #D0E8D8;border-radius:10px;padding:.6rem .8rem;font-size:.85rem;"
                          placeholder="Contoh: Keahlian tidak sesuai, data tidak lengkap, dll."
                          required></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" onclick="hideRejectForm()"
                        style="border:1.5px solid #D0E8D8;background:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.83rem;cursor:pointer;">
                    Batal
                </button>
                <button type="submit"
                        style="background:#DC2626;color:#fff;border:none;border-radius:8px;padding:.4rem 1rem;font-size:.83rem;font-weight:700;cursor:pointer;">
                    <i class="bi bi-x-circle me-1"></i>Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectForm(id, name) {
    document.getElementById('rejectTechId').value = id;
    document.getElementById('rejectModalDesc').textContent = 'Kamu akan menolak pendaftaran teknisi: ' + name;
    const modal = document.getElementById('rejectModal');
    modal.style.display = 'flex';
}
function hideRejectForm() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php pageFooter(); ?>
