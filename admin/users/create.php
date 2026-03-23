<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = post('name');
    $email    = post('email');
    $password = post('password');
    $role     = post('role');
    $phone    = post('phone');
    $keahlian = post('keahlian');
    $lokasi   = post('lokasi');

    if (!$name)  $errors[] = 'Nama wajib diisi.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if (!in_array($role, ['user','technician','admin'])) $errors[] = 'Role tidak valid.';

    if (empty($errors)) {
        $pdo = getDB();
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email sudah terdaftar.';
        } else {
            $ins = $pdo->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)");
            $ins->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone ?: null]);
            $uid = $pdo->lastInsertId();
            if ($role === 'technician' && $keahlian) {
                $pdo->prepare("INSERT INTO technicians (user_id,keahlian,lokasi) VALUES (?,?,?)")
                    ->execute([$uid, $keahlian, $lokasi ?: '-']);
            }
            redirect('/admin/users/index.php', 'User berhasil ditambahkan.', 'success');
        }
    }
}
pageHeader('Tambah User','Tambah User Baru');
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-person-plus me-2 text-success"></i>Data User Baru</div>
    <div class="card-body p-4">
        <?php if($errors): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.82rem;border-radius:10px;">
            <?php foreach($errors as $e): echo '<div>• '.htmlspecialchars($e).'</div>'; endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama *</label>
                <input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e(post('email')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?= e(post('phone')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Role *</label>
                <select name="role" class="form-select" id="roleSelect">
                    <option value="user" <?= post('role')==='user'?'selected':'' ?>>User</option>
                    <option value="technician" <?= post('role')==='technician'?'selected':'' ?>>Teknisi</option>
                    <option value="admin" <?= post('role')==='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div id="techFields" style="display:none;">
                <hr>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Keahlian Teknisi</label>
                    <input type="text" name="keahlian" class="form-control" placeholder="Laptop, Handphone, TV">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Lokasi/Area</label>
                    <input type="text" name="lokasi" class="form-control" placeholder="Jakarta Selatan">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold">Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Konfirmasi</label>
                    <input type="password" name="password_confirm" class="form-control">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<script>
const rs = document.getElementById('roleSelect');
const tf = document.getElementById('techFields');
function toggle(){ tf.style.display = rs.value==='technician'?'block':'none'; }
rs.addEventListener('change', toggle); toggle();
</script>
<?php pageFooter(); ?>
