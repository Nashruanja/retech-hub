<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = post('device_name');
    $type  = post('device_type');
    $brand = post('brand');
    $year  = post('purchase_year');
    $desc  = post('description');

    if (!$name)  $errors[] = 'Nama perangkat wajib diisi.';
    if (!$type)  $errors[] = 'Jenis perangkat wajib dipilih.';
    if (!$brand) $errors[] = 'Merek wajib diisi.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO devices (user_id,device_name,device_type,brand,purchase_year,description) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $name, $type, $brand, $year ?: null, $desc ?: null]);
        redirect('/user/devices/index.php', 'Perangkat berhasil ditambahkan!', 'success');
    }
}

$types = ['Laptop','Handphone','Tablet','TV','AC','Kulkas','Mesin Cuci','Printer','Kamera','Monitor','Speaker','Lainnya'];
pageHeader('Tambah Perangkat', 'Tambah Perangkat Baru');
?>

<div class="row justify-content-center"><div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-plus-circle me-2 text-success"></i>Data Perangkat Baru</div>
    <div class="card-body p-4">
        <?php if ($errors): ?>
        <div class="alert alert-danger py-2 mb-3" style="border-radius:10px;font-size:.82rem;">
            <?php foreach($errors as $e): echo '<div>• '.htmlspecialchars($e).'</div>'; endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Perangkat *</label>
                <input type="text" name="device_name" class="form-control" placeholder="Contoh: ROG G15, Galaxy A52" value="<?= e(post('device_name')) ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Jenis *</label>
                    <select name="device_type" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($types as $t): ?>
                        <option value="<?= $t ?>" <?= post('device_type')===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Merek *</label>
                    <input type="text" name="brand" class="form-control" placeholder="Asus, Samsung..." value="<?= e(post('brand')) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tahun Pembelian</label>
                <input type="number" name="purchase_year" class="form-control" placeholder="<?= date('Y') ?>" min="1990" max="<?= date('Y') ?>" value="<?= e(post('purchase_year')) ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Spesifikasi atau catatan..."><?= e(post('description')) ?></textarea>
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
