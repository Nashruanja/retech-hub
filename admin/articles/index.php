<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
$articles = getDB()->query("SELECT a.*,u.name AS author_name FROM articles a JOIN users u ON a.author_id=u.id ORDER BY a.created_at DESC")->fetchAll();
pageHeader('Kelola Artikel','Kelola Artikel');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?= count($articles) ?> artikel</p>
    <a href="create.php" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus me-1"></i>Tulis Artikel</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if(empty($articles)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada artikel.</td></tr>
            <?php else: foreach($articles as $a): ?>
                <tr>
                    <td style="font-weight:600;font-size:.87rem;"><?= e(limitStr($a['title'],50)) ?></td>
                    <td><span class="badge bg-light text-muted" style="font-size:.72rem;"><?= e($a['category'] ?? 'Umum') ?></span></td>
                    <td style="font-size:.83rem;"><?= e($a['author_name']) ?></td>
                    <td><?= $a['is_published'] ? '<span class="badge bg-success" style="font-size:.7rem;">Published</span>' : '<span class="badge bg-secondary" style="font-size:.7rem;">Draft</span>' ?></td>
                    <td style="font-size:.78rem;"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/articles/show.php?id=<?= $a['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-eye"></i></a>
                            <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-warning" style="font-size:.7rem;padding:.2rem .45rem;"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus artikel ini?')">
                                <?php csrfField(); ?>
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
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
