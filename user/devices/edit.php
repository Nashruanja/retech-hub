<?php
// user/devices/edit.php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$id  = (int)get('id');
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);
$device = $stmt->fetch();
if (!$device) redirect('/user/devices/index.php', 'Perangkat tidak ditemukan.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = post('device_name');
    $type  = post('device_type');
    $brand = post('brand');
    $year  = post('purchase_year');
    $desc  = post('description');

    if ($name && $type && $brand) {
        $upd = $pdo->prepare("UPDATE devices SET device_name=?,device_type=?,brand=?,purchase_year=?,description=?,updated_at=NOW() WHERE id=? AND user_id=?");
        $upd->execute([$name, $type, $brand, $year ?: null, $desc ?: null, $id, $uid]);
        redirect('/user/devices/show.php?id='.$id, 'Perangkat berhasil diperbarui.', 'success');
    }
}

$types = ['Laptop','Handphone','Tablet','TV','AC','Kulkas','Mesin Cuci','Printer','Kamera','Monitor','Speaker','Lainnya'];
pageHeader('Edit Perangkat', 'Edit Perangkat');
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2 text-warning"></i>Edit Data Perangkat</div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3">
                <label class="form-label">Nama Perangkat *</label>
                <input type="text" name="device_name" class="form-control"
                       value="<?= e($device['device_name'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">Jenis *</label>
                    <select name="device_type" class="form-select" required>
                        <?php foreach ($types as $t): ?>
                        <option value="<?= $t ?>" <?= ($device['device_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Merek *</label>
                    <input type="text" name="brand" class="form-control"
                           value="<?= e($device['brand'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Tahun Pembelian</label>
                <input type="number" name="purchase_year" class="form-control"
                       value="<?= e((string)($device['purchase_year'] ?? '')) ?>"
                       min="1990" max="<?= date('Y') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" rows="3" class="form-control"><?= e($device['description'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                <a href="show.php?id=<?= $id ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<?php pageFooter(); ?>
