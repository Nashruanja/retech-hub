<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
$pdo = getDB(); $id = (int)get('id');
$art = $pdo->prepare("SELECT * FROM articles WHERE id=?"); $art->execute([$id]); $art = $art->fetch();
if (!$art) redirect('/admin/articles/index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $published = isset($_POST['is_published']) ? 1 : 0;
    $pdo->prepare("UPDATE articles SET title=?,content=?,category=?,is_published=?,updated_at=NOW() WHERE id=?")
        ->execute([post('title'),post('content'),post('category')?:null,$published,$id]);
    redirect('/admin/articles/index.php', 'Artikel berhasil diperbarui.', 'success');
}
$cats = ['perawatan'=>'Perawatan Elektronik','e-waste'=>'E-Waste & Daur Ulang','tips'=>'Tips & Trik','teknologi'=>'Teknologi'];
pageHeader('Edit Artikel','Edit Artikel');
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2 text-warning"></i>Edit Artikel</div>
    <div class="card-body p-4">
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3"><label class="form-label fw-semibold">Judul *</label><input type="text" name="title" class="form-control" value="<?= e($art['title']) ?>" required></div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach($cats as $v=>$l): ?><option value="<?= $v ?>" <?= $art['category']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" id="pub" value="1" <?= $art['is_published']?'checked':'' ?>>
                        <label class="form-check-label fw-semibold" for="pub">Published</label>
                    </div>
                </div>
            </div>
            <div class="mb-4"><label class="form-label fw-semibold">Isi Artikel *</label><textarea name="content" rows="14" class="form-control" required><?= e($art['content']) ?></textarea></div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button><a href="index.php" class="btn btn-outline-secondary">Batal</a></div>
        </form>
    </div>
</div>
</div></div>
<?php pageFooter(); ?>
