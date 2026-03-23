<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDB();

$filter = get('filter','all');
$where  = $filter === 'free' ? "WHERE is_free=1" : ($filter === 'paid' ? "WHERE is_free=0" : "");
$city   = get('city','');
if ($city) $where = ($where ? $where." AND city LIKE " : "WHERE city LIKE ") . "'".$pdo->quote($city)."'";

$locations  = $pdo->query("SELECT * FROM e_waste_locations $where ORDER BY is_free DESC, name ASC")->fetchAll();
$cities     = $pdo->query("SELECT DISTINCT city FROM e_waste_locations WHERE city IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Lokasi E-Waste | ReTech Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--sage:#4D7C5F;--sage-lt:#E8F5EE;--mint:#10B981;--mint-lt:#ECFDF5;--bg:#F4F8F5;--border:#E9E3FF;--txt:#1A3A28;--muted:#7C7AAA;}
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
body{background:var(--bg);color:var(--txt);margin:0;}
.topnav{background:#1A3A28;padding:.85rem 0;position:sticky;top:0;z-index:999;box-shadow:0 2px 8px rgba(0,0,0,.12);}
.brand{color:#A7D3B5!important;font-weight:800;font-size:1.15rem;text-decoration:none;}
.page-hero{background:linear-gradient(135deg,#1A3A28,#2D5016);padding:52px 0;text-align:center;color:#fff;}
.page-hero h1{font-size:1.9rem;font-weight:800;}
.loc-card{background:#fff;border-radius:16px;border:2px solid var(--border);padding:1.35rem;height:100%;transition:.22s;position:relative;}
.loc-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(77,124,95,.12);border-color:var(--sage);}
.free-badge{position:absolute;top:-10px;right:12px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;padding:.2rem .7rem;border-radius:50px;font-size:.7rem;font-weight:800;}
.paid-badge{position:absolute;top:-10px;right:12px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;padding:.2rem .7rem;border-radius:50px;font-size:.7rem;font-weight:800;}
.info-row{display:flex;align-items:flex-start;gap:.5rem;font-size:.82rem;color:var(--muted);margin-bottom:.35rem;}
.info-row i{color:var(--sage);flex-shrink:0;margin-top:2px;}
.btn-wa{background:#25D366;color:#fff;border:none;border-radius:10px;padding:.5rem 1rem;font-size:.82rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;transition:.2s;}
.btn-wa:hover{background:#22BE5F;color:#fff;transform:translateY(-1px);}
.price-chip{display:inline-block;background:var(--sage-lt);color:var(--sage);padding:.25rem .75rem;border-radius:50px;font-size:.75rem;font-weight:700;border:1px solid var(--border);}
.price-chip.free{background:var(--mint-lt);color:var(--mint);border-color:#A7F3D0;}
.filter-btn{border-radius:50px;font-size:.78rem;padding:.28rem .8rem;}
</style>
</head>
<body>

<nav class="topnav">
<div class="container d-flex justify-content-between align-items-center">
    <a href="<?=BASE_URL?>/index.php" class="brand"><i class="bi bi-recycle me-1"></i>ReTech Hub</a>
    <div class="d-flex gap-2 align-items-center">
        <a href="<?=BASE_URL?>/articles/index.php" style="color:rgba(255,255,255,.65);text-decoration:none;font-size:.85rem;">Edukasi</a>
        <?php if(isLoggedIn()): ?>
        <?php $dash=match($_SESSION['user_role']??''){'admin'=>'admin','technician'=>'technician',default=>'user'}; ?>
        <a href="<?=BASE_URL?>/<?=$dash?>/dashboard.php" class="btn btn-sm btn-outline-light" style="border-radius:8px;font-size:.82rem;">Dashboard</a>
        <?php else: ?>
        <a href="<?=BASE_URL?>/login.php" class="btn btn-sm btn-outline-light" style="border-radius:8px;font-size:.82rem;">Masuk</a>
        <a href="<?=BASE_URL?>/register.php" class="btn btn-sm" style="background:#81C99A;color:#fff;border-radius:8px;font-size:.82rem;border:none;">Daftar</a>
        <?php endif; ?>
    </div>
</div>
</nav>

<div class="page-hero">
    <div class="container">
        <div style="font-size:2.5rem;margin-bottom:.7rem;">♻️</div>
        <h1>Lokasi <span style="color:#81C99A">E-Waste</span></h1>
        <p style="color:rgba(255,255,255,.62);font-size:.9rem;max-width:480px;margin:.5rem auto 0;">
            Temukan tempat daur ulang elektronik terdekat. Jadwalkan pick-up atau drop-off langsung via WhatsApp.
        </p>
    </div>
</div>

<div class="container py-5">
<?php showFlash(); ?>

<!-- Filter Bar -->
<div class="d-flex align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex gap-1">
        <a href="ewaste/index.php" class="btn btn-sm filter-btn <?=$filter==='all'?'btn-primary':'btn-outline-primary'?>">Semua</a>
        <a href="ewaste/index.php?filter=free" class="btn btn-sm filter-btn <?=$filter==='free'?'btn-success':'btn-outline-success'?>">✅ Gratis</a>
        <a href="ewaste/index.php?filter=paid" class="btn btn-sm filter-btn <?=$filter==='paid'?'btn-warning':'btn-outline-warning'?>">💰 Berbayar</a>
    </div>
    <!-- Filter kota -->
    <?php if (!empty($cities)): ?>
    <select onchange="if(this.value) window.location='ewaste/index.php?city='+this.value; else window.location='ewaste/index.php';"
            style="border:1.5px solid var(--border);border-radius:50px;padding:.28rem .8rem;font-size:.78rem;background:#fff;color:var(--txt);cursor:pointer;">
        <option value="">📍 Semua Kota</option>
        <?php foreach ($cities as $c): ?>
        <option value="<?=e($c)?>" <?=$city===$c?'selected':''?>><?=e($c)?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <span style="font-size:.78rem;color:var(--muted);margin-left:auto;"><?=count($locations)?> lokasi ditemukan</span>
</div>

<?php if (empty($locations)): ?>
<div class="text-center py-5" style="color:var(--muted);">
    <i class="bi bi-inbox display-3 d-block mb-3 opacity-25"></i>
    <h5>Tidak ada lokasi ditemukan.</h5>
    <a href="ewaste/index.php" class="btn btn-outline-primary mt-2">Reset Filter</a>
</div>
<?php else: ?>
<div class="row g-4 mb-5">
<?php foreach ($locations as $l): ?>
<div class="col-sm-6 col-lg-4">
    <div class="loc-card">
        <!-- Badge harga -->
        <?php if ($l['is_free']): ?>
        <div class="free-badge">✅ GRATIS</div>
        <?php else: ?>
        <div class="paid-badge">💰 BERBAYAR</div>
        <?php endif; ?>

        <div style="margin-bottom:.75rem;">
            <h6 style="font-weight:800;font-size:.95rem;margin-bottom:.2rem;"><?=e($l['name'])?></h6>
            <?php if($l['city']): ?>
            <span style="background:var(--sage-lt);color:var(--sage);padding:.15rem .6rem;border-radius:50px;font-size:.7rem;font-weight:700;">
                <i class="bi bi-geo-alt me-1"></i><?=e($l['city'])?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Harga -->
        <div class="mb-2">
            <span class="price-chip <?=$l['is_free']?'free':''?>">
                <i class="bi bi-tag me-1"></i><?=e($l['price_range'] ?? ($l['is_free'] ? 'GRATIS' : '-'))?>
            </span>
        </div>

        <div class="info-row"><i class="bi bi-map"></i><span><?=e($l['address'])?></span></div>
        <div class="info-row"><i class="bi bi-clock"></i><span><?=e($l['open_hours'])?></span></div>
        <?php if($l['phone'] && $l['phone'] !== '—'): ?>
        <div class="info-row"><i class="bi bi-telephone"></i><span><?=e($l['phone'])?></span></div>
        <?php endif; ?>
        <?php if($l['accepted_items']): ?>
        <div class="info-row"><i class="bi bi-box-seam"></i><span style="font-size:.78rem;"><?=e($l['accepted_items'])?></span></div>
        <?php endif; ?>
        <?php if($l['description']): ?>
        <div class="info-row"><i class="bi bi-info-circle"></i><span style="font-size:.77rem;"><?=e(limitStr($l['description'],80))?></span></div>
        <?php endif; ?>

        <!-- Tombol WA -->
        <?php if($l['wa_number']): ?>
        <div class="mt-3">
            <a href="https://wa.me/<?=preg_replace('/[^0-9]/','',$l['wa_number'])?>?text=<?=urlencode('Halo, saya ingin mengantar e-waste ke '.$l['name'].'. Apakah sedang buka?')?>"
               target="_blank" class="btn-wa w-100 justify-content-center">
                <i class="bi bi-whatsapp"></i> Chat WhatsApp
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Info Box -->
<div style="background:linear-gradient(135deg,#1A3A28,#2D5016);border-radius:16px;padding:2rem;text-align:center;color:#fff;">
    <div style="font-size:1.75rem;margin-bottom:.6rem;">♻️</div>
    <h5 style="font-weight:800;margin-bottom:.4rem;">Perangkatmu tidak bisa diperbaiki?</h5>
    <p style="color:rgba(255,255,255,.65);font-size:.87rem;margin-bottom:1.2rem;">
        Jangan buang sembarangan! Pilih lokasi di atas dan chat via WhatsApp untuk jadwalkan pengantaran.
    </p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="<?=BASE_URL?>/articles/index.php?cat=e-waste" class="btn btn-sm" style="background:#81C99A;color:#fff;border-radius:8px;font-weight:700;font-size:.85rem;">
            <i class="bi bi-book me-1"></i>Pelajari Bahaya E-Waste
        </a>
        <?php if(!isLoggedIn()): ?>
        <a href="<?=BASE_URL?>/register.php" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3);border-radius:8px;font-size:.85rem;">
            Daftar & Coba Diagnosa AI
        </a>
        <?php endif; ?>
    </div>
</div>
</div>

<footer style="background:#1A3A28;color:rgba(255,255,255,.4);padding:1.2rem 0;text-align:center;font-size:.78rem;margin-top:2rem;">
    <div class="container">
        <a href="<?=BASE_URL?>/index.php" style="color:#A7D3B5;font-weight:700;text-decoration:none;"><i class="bi bi-recycle me-1"></i>ReTech Hub</a>
        &nbsp;—&nbsp;Platform Servis Elektronik &amp; E-Waste Indonesia
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
