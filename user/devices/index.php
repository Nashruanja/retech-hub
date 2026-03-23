<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$uid = $_SESSION['user_id'];
$devices = $pdo->prepare("SELECT d.*, (SELECT COUNT(*) FROM service_requests WHERE device_id=d.id) AS total_servis FROM devices d WHERE user_id=? ORDER BY created_at DESC");
$devices->execute([$uid]);
$devices = $devices->fetchAll();

pageHeader('Perangkat Saya', 'Perangkat Saya');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?= count($devices) ?> perangkat terdaftar</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Tambah Perangkat</a>
</div>

<?php if (empty($devices)): ?>
<div class="card"><div class="card-body text-center py-5">
    <i class="bi bi-phone display-3 text-muted opacity-25 d-block mb-3"></i>
    <h5>Belum ada perangkat</h5>
    <p class="text-muted">Tambahkan perangkat elektronik Anda untuk mulai melacak riwayat servis.</p>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Tambah Perangkat</a>
</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($devices as $d): ?>
<div class="col-sm-6 col-lg-4">
    <div class="card h-100" style="transition:.2s;cursor:pointer;"
         onclick="location.href='show.php?id=<?= $d['id'] ?>'"
         onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='#10B981'"
         onmouseout="this.style.transform='';this.style.borderColor=''">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div style="font-size:2rem;flex-shrink:0;"><?= deviceEmoji($d['device_type']) ?></div>
                <div class="flex-grow-1 min-w-0">
                    <h6 style="font-weight:700;margin-bottom:.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($d['device_name']) ?></h6>
                    <small class="text-muted"><?= e($d['brand']) ?></small>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge rounded-pill" style="background:#E8F5E9;color:#10B981;font-size:.72rem;"><?= e($d['device_type']) ?></span>
                <?php if ($d['purchase_year']): ?>
                <span class="badge rounded-pill bg-light text-muted" style="font-size:.72rem;"><?= $d['purchase_year'] ?></span>
                <?php endif; ?>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="bi bi-tools me-1"></i><?= $d['total_servis'] ?> servis</small>
                <div class="d-flex gap-1" onclick="event.stopPropagation()">
                    <a href="edit.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-warning" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus perangkat ini?')">
                        <?php csrfField(); ?>
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php pageFooter(); ?>
