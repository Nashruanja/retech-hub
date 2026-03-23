<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');

$pdo = getDB();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
pageHeader('Kelola User','Kelola User');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?= count($users) ?> user terdaftar</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Tambah User</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach($users as $i=>$u): ?>
                <tr>
                    <td class="text-muted" style="font-size:.78rem;"><?= $i+1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;background:#EAFAF1;color:#10B981;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0;">
                                <?= strtoupper(substr($u['name'],0,1)) ?>
                            </div>
                            <span style="font-size:.85rem;font-weight:600;"><?= e($u['name']) ?></span>
                        </div>
                    </td>
                    <td style="font-size:.83rem;"><?= e($u['email']) ?></td>
                    <td style="font-size:.83rem;"><?= e($u['phone'] ?? '-') ?></td>
                    <td>
                        <?php if($u['role']==='admin'): ?><span class="badge bg-danger">Admin</span>
                        <?php elseif($u['role']==='technician'): ?><span class="badge bg-info">Teknisi</span>
                        <?php else: ?><span class="badge bg-success">User</span><?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-pencil"></i></a>
                            <?php if($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus user <?= e($u['name']) ?>?')">
                                <?php csrfField(); ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php pageFooter(); ?>
