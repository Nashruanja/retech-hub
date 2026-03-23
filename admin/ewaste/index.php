<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
$pdo = getDB();
$locations = $pdo->query("SELECT * FROM e_waste_locations ORDER BY created_at DESC")->fetchAll();
pageHeader('Lokasi E-Waste','Kelola Lokasi E-Waste');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?= count($locations) ?> lokasi terdaftar</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Tambah Lokasi</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Nama Tempat</th><th>Kota</th><th>Harga</th><th>Jam Buka</th><th>WA</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(empty($locations)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data.</td></tr>
            <?php else: foreach($locations as $l): ?>
                <tr>
                    <td style="font-weight:600;font-size:.87rem;"><?= e($l['name']) ?></td>
                    <td style="font-size:.82rem;"><?= e($l['city'] ?? '-') ?></td>
                    <td><?php if($l['is_free']): ?><span class="badge" style="background:#ECFDF5;color:#065F46;font-size:.7rem;">✅ Gratis</span><?php else: ?><span class="badge bg-warning" style="font-size:.7rem;"><?= e(limitStr($l['price_range']??'-',20)) ?></span><?php endif; ?></td>
                    <td style="font-size:.78rem;"><?= e($l['open_hours']) ?></td>
                    <td><?php if($l['wa_number']): ?><a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $l['wa_number']) ?>" target="_blank" class="btn btn-sm" style="background:#ECFDF5;color:#065F46;border:none;border-radius:6px;font-size:.68rem;padding:.2rem .5rem;"><i class="bi bi-whatsapp"></i></a><?php else: ?><span style="color:#C4B5FD;font-size:.78rem;">—</span><?php endif; ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="edit.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-warning" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus lokasi ini?')">
                                <?php csrfField(); ?>
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php pageFooter(); ?>
