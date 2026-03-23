<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDB();
$cat = get('cat');
$where = $cat ? "WHERE a.is_published=1 AND a.category='" . $pdo->quote($cat) . "'" : "WHERE a.is_published=1";
$articles  = $pdo->query("SELECT a.*,u.name AS author_name FROM articles a JOIN users u ON a.author_id=u.id {$where} ORDER BY a.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM articles WHERE is_published=1 AND category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Edukasi | ReTech Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--green:#10B981;--dark:#1E293B;}*{font-family:'Inter',sans-serif;}body{background:#F8FAFC;}
.topnav{background:var(--dark);padding:.9rem 0;position:sticky;top:0;z-index:999;}
.topnav .brand{color:var(--green);font-weight:800;font-size:1.2rem;text-decoration:none;}
.page-hero{background:linear-gradient(135deg,var(--dark),#1a252f);padding:55px 0;text-align:center;color:#fff;}
.art-card{background:#fff;border-radius:16px;border:1px solid #E9ECEF;overflow:hidden;display:block;text-decoration:none;color:inherit;height:100%;transition:.2s;}
.art-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:var(--green);color:inherit;}
.art-thumb{height:150px;display:flex;align-items:center;justify-content:center;font-size:2.8rem;background:linear-gradient(135deg,#EAFAF1,#D5F5E3);}
.art-body{padding:1.1rem;}
.art-cat{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--green);margin-bottom:.3rem;}
.art-title{font-size:.95rem;font-weight:700;line-height:1.4;margin-bottom:.4rem;}
.art-exc{font-size:.82rem;color:#6C757D;line-height:1.6;}
.art-meta{font-size:.72rem;color:#ADB5BD;margin-top:.6rem;}
</style>
</head>
<body>
<nav class="topnav">
<div class="container d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>/" class="brand"><i class="bi bi-recycle me-1"></i>ReTech Hub</a>
    <div class="d-flex gap-2">
        <?php if(isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/<?= $_SESSION['user_role'] === 'admin' ? 'admin' : ($_SESSION['user_role'] === 'technician' ? 'technician' : 'user') ?>/dashboard.php" class="btn btn-sm btn-outline-light">Dashboard</a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline-light">Masuk</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-sm" style="background:var(--green);color:#fff;border-radius:8px;">Daftar</a>
        <?php endif; ?>
    </div>
</div>
</nav>
<div class="page-hero">
    <div class="container">
        <div style="font-size:2.5rem;margin-bottom:.75rem;">📚</div>
        <h1 style="font-size:2rem;font-weight:800;">Pusat <span style="color:var(--sage)">Edukasi</span></h1>
        <p style="color:rgba(255,255,255,.65);font-size:.9rem;max-width:460px;margin:.5rem auto 0;">Pelajari cara merawat elektronik dan dampak e-waste terhadap lingkungan.</p>
    </div>
</div>
<div class="container py-5">
    <?php if($categories): ?>
    <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="index.php" class="btn btn-sm <?= !$cat?'btn-dark':'btn-outline-secondary' ?>" style="border-radius:50px;font-size:.78rem;">Semua</a>
        <?php foreach($categories as $c): ?>
        <a href="index.php?cat=<?= urlencode($c) ?>" class="btn btn-sm <?= $cat===$c?'btn-dark':'btn-outline-secondary' ?>" style="border-radius:50px;font-size:.78rem;"><?= ucfirst(e($c)) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(empty($articles)): ?>
    <div class="text-center py-5 text-muted"><i class="bi bi-newspaper display-3 d-block mb-3 opacity-25"></i><h5>Belum ada artikel.</h5></div>
    <?php else: ?>
    <div class="row g-4">
    <?php foreach($articles as $a): ?>
    <div class="col-sm-6 col-lg-4">
        <a href="show.php?id=<?= $a['id'] ?>" class="art-card">
            <div class="art-thumb">
                <?php switch($a['category']){case 'e-waste':echo '♻️';break;case 'tips':echo '💡';break;case 'perawatan':echo '🔧';break;default:echo '📱';}?>
            </div>
            <div class="art-body">
                <?php if($a['category']): ?><div class="art-cat"><?= e($a['category']) ?></div><?php endif; ?>
                <div class="art-title"><?= e($a['title']) ?></div>
                <div class="art-exc"><?= e(limitStr($a['content'],95)) ?></div>
                <div class="art-meta"><i class="bi bi-person me-1"></i><?= e($a['author_name']) ?> · <?= date('d M Y',strtotime($a['created_at'])) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<footer style="background:var(--dark);color:rgba(255,255,255,.45);padding:1.25rem 0;text-align:center;font-size:.8rem;margin-top:2rem;">
    <div class="container"><a href="<?= BASE_URL ?>/" style="color:var(--sage);font-weight:700;text-decoration:none;"><i class="bi bi-recycle me-1"></i>ReTech Hub</a> — Platform Servis Elektronik & E-Waste Indonesia</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
