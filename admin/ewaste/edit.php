<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$pdo = getDB();
$id  = (int)get('id');
$loc = $pdo->prepare("SELECT * FROM e_waste_locations WHERE id=?");
$loc->execute([$id]); $loc = $loc->fetch();
if (!$loc) redirect('/admin/ewaste/index.php', 'Lokasi tidak ditemukan.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $pdo->prepare("UPDATE e_waste_locations SET name=?,address=?,open_hours=?,phone=?,wa_number=?,city=?,price_range=?,accepted_items=?,description=?,is_free=?,updated_at=NOW() WHERE id=?")
        ->execute([
            post('name'), post('address'), post('open_hours'),
            post('phone') ?: null, post('wa_number') ?: null,
            post('city') ?: null, post('price_range') ?: null,
            post('accepted_items') ?: null, post('description') ?: null,
            $isFree, $id
        ]);
    redirect('/admin/ewaste/index.php', 'Lokasi berhasil diperbarui.', 'success');
}

pageHeader('Edit Lokasi E-Waste', 'Edit: ' . $loc['name']);
?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header" style="background:#FFFBEB;"><i class="bi bi-pencil me-2 text-warning"></i>Edit Data Lokasi</div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>

            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Nama Tempat *</label>
                    <input type="text" name="name" class="form-control" value="<?= e($loc['name']) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city" class="form-control" value="<?= e($loc['city']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Alamat Lengkap *</label>
                <textarea name="address" rows="2" class="form-control" required><?= e($loc['address']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Jam Buka *</label>
                <input type="text" name="open_hours" class="form-control" value="<?= e($loc['open_hours']) ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Nomor Telepon</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" value="<?= e($loc['phone']) ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#ECFDF5;border-color:#A7F3D0;color:#065F46;"><i class="bi bi-whatsapp"></i></span>
                        <input type="text" name="wa_number" class="form-control" value="<?= e($loc['wa_number']) ?>" placeholder="628xxxxxxxxxx">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Range Harga</label>
                    <input type="text" name="price_range" class="form-control" value="<?= e($loc['price_range']) ?>">
                </div>
                <div class="col-sm-4 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_free" id="isFree" value="1"
                               <?= $loc['is_free'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isFree">✅ Gratis</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Barang yang Diterima</label>
                <input type="text" name="accepted_items" class="form-control" value="<?= e($loc['accepted_items']) ?>">
            </div>

            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" rows="3" class="form-control"><?= e($loc['description']) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<?php pageFooter(); ?>
