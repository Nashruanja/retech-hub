<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = post('name');
    $address  = post('address');
    $hours    = post('open_hours');
    $phone    = post('phone');
    $wa       = post('wa_number');
    $city     = post('city');
    $price    = post('price_range');
    $items    = post('accepted_items');
    $desc     = post('description');
    $isFree   = isset($_POST['is_free']) ? 1 : 0;

    if (!$name)    $errors[] = 'Nama tempat wajib diisi.';
    if (!$address) $errors[] = 'Alamat wajib diisi.';
    if (!$hours)   $errors[] = 'Jam buka wajib diisi.';

    if (empty($errors)) {
        getDB()->prepare("INSERT INTO e_waste_locations (name,address,open_hours,phone,wa_number,city,price_range,accepted_items,description,is_free) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$name, $address, $hours, $phone ?: null, $wa ?: null, $city ?: null, $price ?: null, $items ?: null, $desc ?: null, $isFree]);
        redirect('/admin/ewaste/index.php', 'Lokasi e-waste berhasil ditambahkan.', 'success');
    }
}

pageHeader('Tambah Lokasi E-Waste', 'Tambah Lokasi E-Waste');
?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header" style="background:var(--p-lt);"><i class="bi bi-recycle me-2" style="color:var(--p)"></i>Data Lokasi E-Waste Baru</div>
    <div class="card-body p-4">

        <?php if ($errors): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.83rem;border-radius:10px;">
            <?php foreach ($errors as $er): echo '<div>• '.e($er).'</div>'; endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?php csrfField(); ?>

            <!-- Nama & Kota -->
            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Nama Tempat *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Bank Sampah Elektronik Jakarta" value="<?= e(post('name')) ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city" class="form-control" placeholder="Jakarta" value="<?= e(post('city')) ?>">
                </div>
            </div>

            <!-- Alamat -->
            <div class="mb-3">
                <label class="form-label">Alamat Lengkap *</label>
                <textarea name="address" rows="2" class="form-control" placeholder="Jl. ..." required><?= e(post('address')) ?></textarea>
            </div>

            <!-- Jam Buka -->
            <div class="mb-3">
                <label class="form-label">Jam Buka *</label>
                <input type="text" name="open_hours" class="form-control" placeholder="Senin–Jumat: 08:00–17:00" value="<?= e(post('open_hours')) ?>" required>
            </div>

            <!-- Telepon & WhatsApp -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Nomor Telepon</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="021-xxxxx" value="<?= e(post('phone')) ?>">
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#ECFDF5;border-color:#A7F3D0;color:#065F46;"><i class="bi bi-whatsapp"></i></span>
                        <input type="text" name="wa_number" class="form-control" placeholder="628xxxxxxxx" value="<?= e(post('wa_number')) ?>">
                    </div>
                    <div class="form-text">Format internasional: 628xxxxxxxxxx</div>
                </div>
            </div>

            <!-- Harga & Gratis -->
            <div class="row g-3 mb-3">
                <div class="col-sm-8">
                    <label class="form-label">Range Harga</label>
                    <input type="text" name="price_range" class="form-control" placeholder="GRATIS atau Rp 10.000 – 50.000" value="<?= e(post('price_range')) ?>">
                </div>
                <div class="col-sm-4 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_free" id="isFree" value="1"
                               <?= post('is_free') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isFree">
                            ✅ Drop-off Gratis
                        </label>
                    </div>
                </div>
            </div>

            <!-- Barang Diterima -->
            <div class="mb-3">
                <label class="form-label">Barang yang Diterima</label>
                <input type="text" name="accepted_items" class="form-control" placeholder="Laptop, HP, TV, Kulkas, AC, Printer, Baterai, dll." value="<?= e(post('accepted_items')) ?>">
            </div>

            <!-- Deskripsi -->
            <div class="mb-4">
                <label class="form-label">Deskripsi Layanan</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Informasi tambahan tentang fasilitas atau layanan...">
<?= e(post('description')) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Lokasi</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<?php pageFooter(); ?>
