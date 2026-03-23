<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
$pdo = getDB();
$id  = (int)get('id');
$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$id]); $user = $user->fetch();
if (!$user) redirect('/admin/users/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = post('name');
    $email = post('email');
    $role  = post('role');
    $phone = post('phone');
    $pass  = post('password');

    if ($name && $email) {
        if ($pass) {
            $pdo->prepare("UPDATE users SET name=?,email=?,role=?,phone=?,password=?,updated_at=NOW() WHERE id=?")
                ->execute([$name,$email,$role,$phone ?: null,password_hash($pass,PASSWORD_DEFAULT),$id]);
        } else {
            $pdo->prepare("UPDATE users SET name=?,email=?,role=?,phone=?,updated_at=NOW() WHERE id=?")
                ->execute([$name,$email,$role,$phone ?: null,$id]);
        }
        redirect('/admin/users/index.php', 'User berhasil diperbarui.', 'success');
    }
}
pageHeader('Edit User','Edit User: '.$user['name']);
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2 text-warning"></i>Edit Data User</div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama</label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" class="form-select">
                    <option value="user" <?= $user['role']==='user'?'selected':'' ?>>User</option>
                    <option value="technician" <?= $user['role']==='technician'?'selected':'' ?>>Teknisi</option>
                    <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
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
