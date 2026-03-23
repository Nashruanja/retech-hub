<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('admin');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = post('title'); $content = post('content');
    $cat   = post('category'); $published = isset($_POST['is_published']) ? 1 : 0;
    if (!$title)   $errors[] = 'Judul wajib diisi.';
    if (!$content) $errors[] = 'Isi artikel wajib diisi.';
    if (empty($errors)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-', $title));
        $slug = trim($slug,'-').'-'.time();
        getDB()->prepare("INSERT INTO articles (title,slug,content,category,author_id,is_published) VALUES (?,?,?,?,?,?)")
               ->execute([$title,$slug,$content,$cat?:null,$_SESSION['user_id'],$published]);
        redirect('/admin/articles/index.php', 'Artikel berhasil dipublish.', 'success');
    }
}
$cats = ['perawatan'=>'Perawatan Elektronik','e-waste'=>'E-Waste & Daur Ulang','tips'=>'Tips & Trik','teknologi'=>'Teknologi'];
pageHeader('Tulis Artikel','Tulis Artikel Baru');
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-header"><i class="bi bi-journal-plus me-2 text-success"></i>Artikel Edukasi Baru</div>
    <div class="card-body p-4">
        <?php if($errors): ?><div class="alert alert-danger py-2 mb-3" style="font-size:.82rem;border-radius:10px;"><?php foreach($errors as $er): echo '<div>• '.e($er).'</div>'; endforeach; ?></div><?php endif; ?>
        <form method="POST">
            <?php csrfField(); ?>
            <div class="mb-3"><label class="form-label fw-semibold">Judul *</label><input type="text" name="title" class="form-control" value="<?= e(post('title')) ?>" required></div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">-- Pilih --</option>
                        <?php foreach($cats as $v=>$l): ?><option value="<?= $v ?>" <?= post('category')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" id="pub" value="1" checked>
                        <label class="form-check-label fw-semibold" for="pub">Publish sekarang</label>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Isi Artikel *</label>
                <textarea name="content" rows="14" class="form-control" required placeholder="Tulis isi artikel di sini. Boleh menggunakan HTML sederhana..."><?= e(post('content')) ?></textarea>
                <div class="form-text">Boleh menggunakan HTML: &lt;p&gt;, &lt;h4&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;li&gt;</div>
            </div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish</button><a href="index.php" class="btn btn-outline-secondary">Batal</a></div>
        </form>
    </div>
</div>
</div></div>
<?php pageFooter(); ?>
