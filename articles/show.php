<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDB(); $id = (int)get('id');
$stmt = $pdo->prepare("SELECT a.*,u.name AS author_name FROM articles a JOIN users u ON a.author_id=u.id WHERE a.id=? AND a.is_published=1");
$stmt->execute([$id]); $article = $stmt->fetch();
if (!$article) { header('Location: '.BASE_URL.'/articles/index.php'); exit; }
$related = $pdo->prepare("SELECT * FROM articles WHERE is_published=1 AND id!=? AND category=? LIMIT 3");
$related->execute([$id, $article['category']]); $related = $related->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($article['title']) ?> | ReTech Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--green:#10B981;--dark:#1E293B;}*{font-family:'Inter',sans-serif;}body{background:#F8FAFC;}
.topnav{background:var(--dark);padding:.9rem 0;position:sticky;top:0;z-index:999;}
.topnav .brand{color:var(--green);font-weight:800;font-size:1.2rem;text-decoration:none;}
.art-hero{background:linear-gradient(135deg,var(--dark),#1a252f);padding:50px 0;color:#fff;}
.content-card{background:#fff;border-radius:16px;padding:2.25rem;box-shadow:0 4px 20px rgba(0,0,0,.06);margin-top:-2rem;position:relative;z-index:10;}
.content-card .body{font-size:.97rem;line-height:1.85;color:#444;}
.content-card .body p{margin-bottom:1rem;}
.content-card .body h4,.content-card .body h5{font-weight:700;color:var(--dark);margin-top:1.5rem;}
.content-card .body ul{padding-left:1.5rem;}
.content-card .body strong{color:var(--dark);}
.rel-card{background:#fff;border-radius:12px;border:1px solid #E9ECEF;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:.2s;}
.rel-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-3px);color:inherit;}
.rel-thumb{height:90px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;background:linear-gradient(135deg,#EAFAF1,#D5F5E3);}
.rel-body{padding:.85rem;}
</style>
</head>
<body>
<nav class="topnav">
<div class="container d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>/" class="brand"><i class="bi bi-recycle me-1"></i>ReTech Hub</a>
    <a href="index.php" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Semua Artikel</a>
</div>
</nav>
<div class="art-hero">
<div class="container" style="max-width:740px;">
    <?php if($article['category']): ?>
    <span style="display:inline-block;background:rgba(46,204,113,.2);color:var(--green);border:1px solid rgba(46,204,113,.3);padding:.25rem .85rem;border-radius:50px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:.85rem;">
        <?= e($article['category']) ?>
    </span>
    <?php endif; ?>
    <h1 style="font-size:clamp(1.4rem,3vw,2rem);font-weight:800;line-height:1.3;margin-bottom:.85rem;"><?= e($article['title']) ?></h1>
    <div style="color:rgba(255,255,255,.5);font-size:.82rem;">
        <i class="bi bi-person me-1"></i><?= e($article['author_name']) ?>
        <span class="mx-2">·</span>
        <i class="bi bi-calendar me-1"></i><?= date('d F Y',strtotime($article['created_at'])) ?>
    </div>
</div>
</div>
<div class="container py-4" style="max-width:740px;">
    <div class="content-card">
        <div class="body"><?= $article['content'] ?></div>
        <hr class="my-4">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <?php if(!isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm"><i class="bi bi-lightning me-1"></i>Coba Diagnosa AI</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if($related): ?>
    <div class="mt-5">
        <h6 style="font-weight:700;color:#6C757D;font-size:.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:1rem;">Artikel Terkait</h6>
        <div class="row g-3">
        <?php foreach($related as $r): ?>
        <div class="col-sm-4">
            <a href="show.php?id=<?= $r['id'] ?>" class="rel-card">
                <div class="rel-thumb"><?php switch($r['category']){case 'e-waste':echo '♻️';break;case 'tips':echo '💡';break;case 'perawatan':echo '🔧';break;default:echo '📱';} ?></div>
                <div class="rel-body"><div style="font-size:.83rem;font-weight:700;line-height:1.4;"><?= e(limitStr($r['title'],55)) ?></div></div>
            </a>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<footer style="background:var(--dark);color:rgba(255,255,255,.45);padding:1.25rem 0;text-align:center;font-size:.8rem;margin-top:3rem;">
    <div class="container"><a href="<?= BASE_URL ?>/" style="color:var(--sage);font-weight:700;text-decoration:none;"><i class="bi bi-recycle me-1"></i>ReTech Hub</a> — Platform Servis Elektronik & E-Waste Indonesia</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
