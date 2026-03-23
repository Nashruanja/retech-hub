<?php
require_once __DIR__ . '/includes/functions.php';
$pdo      = getDB();
$articles = $pdo->query("SELECT a.*,u.name AS author_name FROM articles a JOIN users u ON a.author_id=u.id WHERE a.is_published=1 ORDER BY a.created_at DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReTech Hub | Platform Servis Elektronik & E-Waste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* =============================================
       ECO PASTEL COLOR PALETTE
       Inspired by nature, plants, recycling
    ============================================= */
    :root {
        /* Greens — soft sage & forest */
        --sage:      #4D7C5F;   /* primary green — sage forest */
        --sage-lt:   #E8F5EE;   /* very light sage bg */
        --sage-md:   #C5E0CF;   /* medium sage */
        --mint:      #6FCF9E;   /* mint accent */
        --forest:    #2D5016;   /* dark forest green (for text) */

        /* Warm neutrals — earth tones */
        --cream:     #FDFAF4;   /* warm white / cream */
        --sand:      #F2EDE3;   /* sandy beige */
        --stone:     #E8E0D4;   /* warm stone */
        --bark:      #8B7355;   /* warm brown text */

        /* Accents */
        --sun:       #F4A823;   /* warm amber/sun */
        --sun-lt:    #FEF3D0;   /* light amber */
        --sky:       #7BC8E0;   /* soft sky blue */
        --sky-lt:    #E8F6FB;   /* light sky */
        --coral:     #E8826A;   /* soft coral */
        --coral-lt:  #FDE9E4;   /* light coral */

        /* Page */
        --bg:        #F7F4EE;   /* warm cream background */
        --white:     #FFFFFF;
        --shadow:    0 2px 12px rgba(77,124,95,.08);
        --r:         16px;
    }

    * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
    body { background: var(--bg); color: var(--forest); margin: 0; }

    /* ── NAVBAR ──────────────────────────────── */
    .navbar {
        background: var(--white);
        border-bottom: 1.5px solid var(--sage-md);
        padding: .85rem 0;
        position: sticky; top: 0; z-index: 999;
        box-shadow: 0 2px 16px rgba(77,124,95,.06);
    }
    .navbar-brand {
        display: flex; align-items: center; gap: .5rem;
        color: var(--sage) !important;
        font-weight: 800; font-size: 1.2rem; text-decoration: none;
    }
    .navbar-brand .logo-leaf {
        width: 36px; height: 36px;
        background: var(--sage-lt);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        border: 1.5px solid var(--sage-md);
    }
    .nav-link-custom {
        color: var(--bark) !important;
        font-size: .87rem; font-weight: 500;
        text-decoration: none; padding: .3rem .7rem;
        border-radius: 8px; transition: all .18s;
    }
    .nav-link-custom:hover { background: var(--sage-lt); color: var(--sage) !important; }
    .btn-nav-outline {
        border: 1.5px solid var(--sage-md);
        color: var(--sage); background: transparent;
        padding: .35rem 1rem; border-radius: 50px;
        font-size: .84rem; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: .18s;
    }
    .btn-nav-outline:hover { background: var(--sage-lt); color: var(--sage); }
    .btn-nav-solid {
        background: var(--sage); color: var(--white) !important;
        padding: .38rem 1.1rem; border-radius: 50px;
        font-size: .84rem; font-weight: 700; text-decoration: none;
        border: none; transition: .18s;
    }
    .btn-nav-solid:hover { background: var(--forest); }

    /* ── HERO ────────────────────────────────── */
    .hero {
        background: linear-gradient(160deg, #EAF4EF 0%, #F2EDE3 45%, #EAF4EF 100%);
        padding: 80px 0 70px;
        position: relative;
        overflow: hidden;
    }
    /* Decorative circles */
    .hero::before {
        content: ''; position: absolute;
        width: 450px; height: 450px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(111,207,158,.18) 0%, transparent 65%);
        top: -120px; right: -80px;
    }
    .hero::after {
        content: ''; position: absolute;
        width: 300px; height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(244,168,35,.12) 0%, transparent 65%);
        bottom: -60px; left: -60px;
    }
    .hero-label {
        display: inline-flex; align-items: center; gap: .4rem;
        background: var(--sage-lt);
        border: 1.5px solid var(--sage-md);
        color: var(--sage);
        padding: .3rem .9rem;
        border-radius: 50px; font-size: .78rem; font-weight: 700;
        margin-bottom: 1.4rem;
    }
    .hero h1 {
        font-size: clamp(1.85rem, 4.5vw, 2.9rem);
        font-weight: 800; line-height: 1.25;
        color: var(--forest); margin-bottom: 1.1rem;
    }
    .hero h1 .hl { color: var(--sage); }
    .hero p {
        font-size: .97rem; color: var(--bark);
        line-height: 1.78; max-width: 500px; margin-bottom: 2rem;
    }
    .btn-hero-primary {
        background: var(--sage); color: var(--white);
        padding: .78rem 1.75rem; border-radius: 50px;
        font-size: .9rem; font-weight: 700;
        text-decoration: none; border: none;
        display: inline-flex; align-items: center; gap: .45rem;
        transition: all .2s;
    }
    .btn-hero-primary:hover {
        background: var(--forest); color: var(--white);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(45,80,22,.25);
    }
    .btn-hero-secondary {
        background: var(--white); color: var(--sage);
        padding: .78rem 1.75rem; border-radius: 50px;
        font-size: .9rem; font-weight: 700;
        text-decoration: none;
        border: 1.5px solid var(--sage-md);
        display: inline-flex; align-items: center; gap: .45rem;
        transition: all .2s;
    }
    .btn-hero-secondary:hover { background: var(--sage-lt); border-color: var(--sage); }

    /* Hero stats */
    .hero-stat { margin-top: 2.5rem; display: flex; gap: 2.5rem; }
    .hstat-num { font-size: 1.7rem; font-weight: 800; color: var(--sage); line-height: 1; }
    .hstat-lbl { font-size: .75rem; color: var(--bark); margin-top: .18rem; }

    /* Hero Panel Card */
    .hero-panel {
        background: var(--white);
        border-radius: var(--r);
        border: 1.5px solid var(--stone);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        position: relative; z-index: 2;
    }
    .panel-label {
        font-size: .65rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--bark); margin-bottom: .85rem;
        display: flex; align-items: center; gap: .35rem;
    }
    .panel-label .dot { width: 7px; height: 7px; background: #6FCF9E; border-radius: 50%; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .mini-card {
        display: flex; align-items: center; gap: .75rem;
        background: var(--sand); border: 1px solid var(--stone);
        border-radius: 12px; padding: .75rem .9rem;
        margin-bottom: .55rem;
    }
    .mini-icon {
        width: 38px; height: 38px;
        background: var(--sage-lt); border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        border: 1px solid var(--sage-md);
    }
    .mini-name { font-size: .82rem; font-weight: 700; color: var(--forest); }
    .mini-status { font-size: .7rem; }
    .ai-card {
        background: linear-gradient(135deg, #EAF4EF, #E0F5EA);
        border: 1.5px solid var(--sage-md);
        border-radius: 12px; padding: .85rem;
        margin-top: .75rem;
    }
    .ai-label { font-size: .68rem; font-weight: 800; color: var(--sage); text-transform: uppercase; letter-spacing: .8px; margin-bottom: .3rem; }
    .ai-text { font-size: .8rem; color: var(--bark); line-height: 1.55; }
    .ai-text strong { color: var(--forest); }

    /* ── FEATURES ──────────────────────────── */
    .sec-features { background: var(--white); padding: 80px 0; }
    .section-eyebrow {
        font-size: .72rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 1.5px;
        color: var(--sage); margin-bottom: .4rem;
    }
    .section-title { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; color: var(--forest); }
    .section-sub { color: var(--bark); font-size: .92rem; max-width: 520px; margin: 0 auto; line-height: 1.7; }

    .feat-card {
        background: var(--cream);
        border: 1.5px solid var(--stone);
        border-radius: var(--r); padding: 1.75rem;
        height: 100%; transition: all .25s;
    }
    .feat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 40px rgba(77,124,95,.1);
        border-color: var(--sage);
        background: var(--white);
    }
    .feat-icon {
        width: 54px; height: 54px;
        border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 1.1rem;
    }
    .feat-card h5 { font-weight: 800; color: var(--forest); margin-bottom: .45rem; font-size: .97rem; }
    .feat-card p { color: var(--bark); font-size: .86rem; line-height: 1.7; margin: 0; }

    /* ── PRICING SECTION ───────────────────── */
    .sec-pricing {
        background: linear-gradient(160deg, #EAF4EF, #F2EDE3);
        padding: 75px 0;
    }
    .price-card {
        background: var(--white);
        border-radius: var(--r);
        border: 1.5px solid var(--stone);
        padding: 1.5rem;
        height: 100%;
    }
    .price-tag {
        font-size: .72rem; font-weight: 700;
        background: var(--sage-lt); color: var(--sage);
        border: 1px solid var(--sage-md);
        padding: .2rem .65rem; border-radius: 50px;
        display: inline-block; margin-bottom: .75rem;
    }
    .price-num { font-size: 1.6rem; font-weight: 800; color: var(--forest); }
    .price-desc { color: var(--bark); font-size: .83rem; line-height: 1.6; }
    .fee-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: .5rem 0; border-bottom: 1px solid var(--sand);
        font-size: .84rem;
    }
    .fee-row .fee-label { color: var(--bark); }
    .fee-row .fee-val { font-weight: 700; color: var(--forest); }
    .fee-row.total { border-bottom: none; padding-top: .75rem; margin-top: .25rem; }
    .fee-row.total .fee-label { font-weight: 700; color: var(--forest); }
    .fee-row.total .fee-val { color: var(--sage); font-size: 1rem; }

    /* ── HOW IT WORKS ──────────────────────── */
    .sec-steps { background: var(--sand); padding: 75px 0; }
    .step-num {
        width: 52px; height: 52px;
        background: var(--white); border: 2.5px solid var(--sage-md);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: var(--sage); font-weight: 800; font-size: 1.1rem;
        margin: 0 auto 1.1rem;
    }
    .step-card { text-align: center; padding: 1.25rem; }
    .step-card h5 { font-weight: 800; color: var(--forest); margin-bottom: .4rem; }
    .step-card p { color: var(--bark); font-size: .85rem; line-height: 1.65; margin: 0; }
    .step-connector { height: 2px; background: var(--sage-md); margin: 0 .5rem; margin-bottom: 3.5rem; flex: 1; }

    /* ── ARTICLES ──────────────────────────── */
    .sec-articles { background: var(--cream); padding: 75px 0; }
    .art-card {
        background: var(--white); border-radius: var(--r);
        border: 1.5px solid var(--stone);
        overflow: hidden; height: 100%;
        display: block; text-decoration: none; color: inherit;
        transition: all .25s;
    }
    .art-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(77,124,95,.1); border-color: var(--sage); color: inherit; }
    .art-thumb {
        height: 145px; display: flex; align-items: center; justify-content: center;
        font-size: 2.75rem;
    }
    .art-body { padding: 1.15rem; }
    .art-cat { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--sage); margin-bottom: .35rem; }
    .art-title { font-size: .93rem; font-weight: 800; line-height: 1.4; color: var(--forest); margin-bottom: .4rem; }
    .art-exc { font-size: .82rem; color: var(--bark); line-height: 1.6; }
    .art-meta { font-size: .72rem; color: #B5A898; margin-top: .6rem; }

    /* ── CTA ───────────────────────────────── */
    .sec-cta {
        background: var(--sage);
        padding: 75px 0; text-align: center;
        position: relative; overflow: hidden;
    }
    .sec-cta::before {
        content: ''; position: absolute;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,.1) 0%, transparent 60%);
        top: -100px; right: -80px; border-radius: 50%;
    }
    .sec-cta h2 { color: var(--white); font-weight: 800; font-size: 1.9rem; margin-bottom: .7rem; }
    .sec-cta p { color: rgba(255,255,255,.78); font-size: .93rem; margin-bottom: 1.75rem; max-width: 500px; margin-left: auto; margin-right: auto; }
    .btn-cta {
        background: var(--white); color: var(--sage);
        padding: .82rem 2.25rem; border-radius: 50px;
        font-weight: 800; text-decoration: none;
        display: inline-block; transition: all .2s; font-size: .93rem;
        box-shadow: 0 4px 16px rgba(0,0,0,.1);
    }
    .btn-cta:hover { background: var(--cream); transform: translateY(-2px); color: var(--forest); }

    /* ── FOOTER ────────────────────────────── */
    footer {
        background: var(--forest); color: rgba(255,255,255,.55);
        padding: 52px 0 20px;
    }
    footer h6 { color: var(--white); font-weight: 700; margin-bottom: .85rem; font-size: .9rem; }
    footer a { color: rgba(255,255,255,.5); text-decoration: none; font-size: .84rem; transition: color .18s; }
    footer a:hover { color: var(--mint); }
    footer li { margin-bottom: .35rem; }
    .footer-brand { color: var(--mint); font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: .4rem; }
    .footer-desc { font-size: .84rem; line-height: 1.7; margin-top: .4rem; color: rgba(255,255,255,.45); }
    .footer-divider { border-color: rgba(255,255,255,.1); margin: 1.75rem 0 1rem; }
    .footer-bottom { font-size: .78rem; color: rgba(255,255,255,.3); }
    </style>
</head>
<body>

<!-- ════════════════ NAVBAR ════════════════ -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <div class="logo-leaf">🌿</div>
            ReTech Hub
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                style="color:var(--bark)">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <div class="d-flex align-items-center gap-1 ms-auto flex-wrap">
                <a href="#fitur" class="nav-link-custom">Fitur</a>
                <a href="#harga" class="nav-link-custom">Harga</a>
                <a href="#cara-kerja" class="nav-link-custom">Cara Kerja</a>
                <a href="<?= BASE_URL ?>/ewaste/index.php" class="nav-link-custom">E-Waste</a>
                <a href="<?= BASE_URL ?>/articles/index.php" class="nav-link-custom">Edukasi</a>

                <?php if (isLoggedIn()):
                    $dashUrl = match($_SESSION['user_role'] ?? '') {
                        'admin'      => BASE_URL.'/admin/dashboard.php',
                        'technician' => BASE_URL.'/technician/dashboard.php',
                        default      => BASE_URL.'/user/dashboard.php',
                    };
                ?>
                <a href="<?= $dashUrl ?>" class="btn-nav-solid ms-2">
                    <i class="bi bi-grid me-1"></i>Dashboard
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn-nav-outline ms-2">Masuk</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn-nav-solid ms-1">Daftar Gratis</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ════════════════ HERO ════════════════ -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">

            <!-- Left: Copy -->
            <div class="col-lg-6" style="position:relative;z-index:2;">
                <div class="hero-label">
                    <span>🌿</span> Platform Circular Economy #1 Indonesia
                </div>
                <h1>
                    Perbaiki <span class="hl">Jika Bisa</span>,<br>
                    Kelola <span class="hl">Jika Tidak Bisa</span>
                </h1>
                <p>
                    Platform servis elektronik berbasis <strong style="color:var(--sage)">circular economy</strong>.
                    Diagnosa AI, teknisi terrating, harga transparan, dan pengelolaan e-waste bertanggung jawab.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/diagnosis/index.php" class="btn-hero-primary">
                        <i class="bi bi-cpu"></i>Diagnosa Perangkat
                    </a>
                    <a href="<?= $dashUrl ?>" class="btn-hero-secondary">
                        <i class="bi bi-grid"></i>Lihat Dashboard
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/register.php" class="btn-hero-primary">
                        <i class="bi bi-leaf"></i>Mulai Sekarang — Gratis
                    </a>
                    <a href="#cara-kerja" class="btn-hero-secondary">
                        <i class="bi bi-play-circle"></i>Lihat Cara Kerja
                    </a>
                    <?php endif; ?>
                </div>

                <div class="hero-stat">
                    <div><div class="hstat-num">500+</div><div class="hstat-lbl">Teknisi Aktif</div></div>
                    <div><div class="hstat-num">2K+</div><div class="hstat-lbl">Servis Selesai</div></div>
                    <div><div class="hstat-num">98%</div><div class="hstat-lbl">Puas</div></div>
                    <div><div class="hstat-num">5</div><div class="hstat-lbl">Lokasi E-Waste</div></div>
                </div>
            </div>

            <!-- Right: Panel -->
            <div class="col-lg-5 offset-lg-1" style="position:relative;z-index:2;">
                <div class="hero-panel">
                    <?php if (isLoggedIn()):
                        // Tampilkan servis aktif user
                        $act = $pdo->prepare("SELECT sr.*,d.device_name,d.device_type FROM service_requests sr JOIN devices d ON sr.device_id=d.id WHERE d.user_id=? ORDER BY sr.updated_at DESC LIMIT 3");
                        $act->execute([$_SESSION['user_id']]); $actList = $act->fetchAll();
                    ?>
                    <div class="panel-label">
                        <div class="dot"></div>
                        AKTIVITAS SERVIS KAMU
                    </div>
                    <?php if (empty($actList)): ?>
                    <div style="text-align:center;padding:1.5rem 0;color:var(--bark);font-size:.85rem;">
                        <div style="font-size:2.5rem;margin-bottom:.6rem;">📱</div>
                        Belum ada servis aktif.<br>
                        <a href="<?= BASE_URL ?>/user/service/create.php" style="color:var(--sage);font-weight:700;">Booking servis pertama →</a>
                    </div>
                    <?php else: foreach ($actList as $a):
                        $statusColors = ['menunggu'=>'#F4A823','diproses'=>'#7BC8E0','selesai'=>'#4D7C5F','tidak_bisa_diperbaiki'=>'#E8826A'];
                        $statusBg = ['menunggu'=>'#FEF3D0','diproses'=>'#E0F5FB','selesai'=>'#E8F5EE','tidak_bisa_diperbaiki'=>'#FDE9E4'];
                        $statusClr = ['menunggu'=>'#B07818','diproses'=>'#1E7A94','selesai'=>'#2D5016','tidak_bisa_diperbaiki'=>'#A0412A'];
                    ?>
                    <div class="mini-card">
                        <div class="mini-icon"><?= deviceEmoji($a['device_type']) ?></div>
                        <div class="flex-grow-1">
                            <div class="mini-name"><?= e($a['device_name'] ?? '') ?></div>
                            <span class="mini-status badge" style="background:<?= $statusBg[$a['status']] ?? '#F0F0F0' ?>;color:<?= $statusClr[$a['status']] ?? '#666' ?>;font-size:.68rem;border-radius:6px;">
                                <?= statusLabel($a['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                    <div style="text-align:center;margin-top:.75rem;">
                        <a href="<?= BASE_URL ?>/user/service/index.php" style="color:var(--sage);font-size:.75rem;font-weight:700;text-decoration:none;">
                            Lihat semua servis <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <?php else: // Belum login — tampilkan demo ?>
                    <div class="panel-label">
                        <span style="font-size:.85rem;">🌿</span>
                        DEMO PLATFORM
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon">💻</div>
                        <div>
                            <div class="mini-name">Laptop Asus ROG</div>
                            <span class="mini-status badge" style="background:#E0F5FB;color:#1E7A94;font-size:.68rem;border-radius:6px;">Diproses</span>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="mini-icon">📱</div>
                        <div>
                            <div class="mini-name">Samsung Galaxy A52</div>
                            <span class="mini-status badge" style="background:#E8F5EE;color:#2D5016;font-size:.68rem;border-radius:6px;">Selesai ✓</span>
                        </div>
                    </div>
                    <div class="ai-card">
                        <div class="ai-label">🤖 Diagnosa AI</div>
                        <div class="ai-text">Kemungkinan: <strong>Baterai lemah & IC charging</strong><br>
                        Tingkat: <span style="color:var(--sun);font-weight:700;">● Sedang</span></div>
                    </div>
                    <div style="margin-top:1rem;text-align:center;">
                        <a href="<?= BASE_URL ?>/register.php" class="btn-hero-primary" style="font-size:.82rem;padding:.55rem 1.4rem;border-radius:50px;">
                            🌿 Coba Gratis Sekarang
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════ FEATURES ════════════════ -->
<section class="sec-features" id="fitur">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-eyebrow">Fitur Unggulan</div>
            <h2 class="section-title">Semua yang Kamu Butuhkan</h2>
            <p class="section-sub mt-2">Dari diagnosa AI hingga e-waste — satu platform untuk semua.</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['bg'=>'#EAF4EF','em'=>'🤖','title'=>'Diagnosa AI',         'desc'=>'Ceritakan keluhan perangkat, dapatkan diagnosa awal dari Google Gemini AI sebelum booking teknisi.'],
                ['bg'=>'#FEF3D0','em'=>'⭐','title'=>'Teknisi Terrating',    'desc'=>'Filter teknisi by rating, terlaris, tercepat, atau termurah. Semua transparan dengan ulasan nyata.'],
                ['bg'=>'#E0F5FB','em'=>'💰','title'=>'Harga Transparan',     'desc'=>'Biaya servis + ongkir (kalau ke rumah) + fee aplikasi 5% semuanya tertera jelas sebelum konfirmasi.'],
                ['bg'=>'#FDE9E4','em'=>'🛡️','title'=>'Garansi Servis',       'desc'=>'Teknisi berikan garansi hingga 90 hari. Klaim mudah langsung via WhatsApp dengan bukti nota COD.'],
                ['bg'=>'#EAF4EF','em'=>'💬','title'=>'Chat Konsultasi',      'desc'=>'Tanya teknisi sebelum booking. Lihat riwayat servis & chat per teknisi dalam satu dashboard.'],
                ['bg'=>'#F2EDE3','em'=>'♻️','title'=>'E-Waste Management',   'desc'=>'Tidak bisa diperbaiki? Kami arahkan ke lokasi daur ulang terdekat. Chat WA langsung dari platform.'],
            ];
            foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-4">
                <div class="feat-card">
                    <div class="feat-icon" style="background:<?= $f['bg'] ?>"><?= $f['em'] ?></div>
                    <h5><?= $f['title'] ?></h5>
                    <p><?= $f['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════ PRICING ════════════════ -->
<section class="sec-pricing" id="harga">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-eyebrow">Struktur Harga</div>
            <h2 class="section-title">Transparan, Tanpa Biaya Tersembunyi</h2>
            <p class="section-sub mt-2">Semua biaya tertera jelas sebelum kamu konfirmasi booking.</p>
        </div>
        <div class="row g-4 justify-content-center">

            <!-- Bring In -->
            <div class="col-md-5">
                <div class="price-card">
                    <div class="price-tag">🏪 Bawa ke Tempat Teknisi</div>
                    <h5 style="font-weight:800;color:var(--forest);margin-bottom:.25rem;">Biaya Servis</h5>
                    <p class="price-desc mb-3">Harga tergantung jenis kerusakan, ditentukan teknisi.</p>

                    <div class="fee-row">
                        <span class="fee-label">🔧 Biaya Servis</span>
                        <span class="fee-val">Mulai Rp 50.000</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">🚗 Ongkir</span>
                        <span class="fee-val" style="color:var(--sage);">GRATIS</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">📱 Fee Aplikasi</span>
                        <span class="fee-val">5% dari biaya servis</span>
                    </div>
                    <div class="fee-row total" style="border-top:2px dashed var(--stone);">
                        <span class="fee-label">💳 Total (COD)</span>
                        <span class="fee-val">Servis + Fee App</span>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background:var(--sage-lt);border:1px solid var(--sage-md);font-size:.78rem;color:var(--sage);">
                        <i class="bi bi-check-circle me-1"></i>Hemat ongkir — kamu yang datang ke teknisi
                    </div>
                </div>
            </div>

            <!-- Home Visit -->
            <div class="col-md-5">
                <div class="price-card" style="border-color:var(--sage);box-shadow:0 4px 24px rgba(77,124,95,.12);">
                    <div style="position:absolute;top:-12px;right:16px;">
                        <span style="background:var(--sage);color:#fff;font-size:.72rem;font-weight:800;padding:.25rem .75rem;border-radius:50px;">Paling Populer</span>
                    </div>
                    <div class="price-tag" style="background:var(--sage-lt);border-color:var(--sage);">🏠 Teknisi ke Rumah</div>
                    <h5 style="font-weight:800;color:var(--forest);margin-bottom:.25rem;">Biaya Servis + Ongkir</h5>
                    <p class="price-desc mb-3">Teknisi datang ke lokasi kamu. Ada biaya transportasi.</p>

                    <div class="fee-row">
                        <span class="fee-label">🔧 Biaya Servis</span>
                        <span class="fee-val">Mulai Rp 50.000</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">🚗 Ongkir Teknisi</span>
                        <span class="fee-val">Rp 15.000 – 35.000</span>
                    </div>
                    <div class="fee-row">
                        <span class="fee-label">📱 Fee Aplikasi</span>
                        <span class="fee-val">5% dari biaya servis</span>
                    </div>
                    <div class="fee-row total" style="border-top:2px dashed var(--stone);">
                        <span class="fee-label">💳 Total (COD)</span>
                        <span class="fee-val">Servis + Ongkir + Fee</span>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background:var(--sun-lt);border:1px solid #F4C842;font-size:.78rem;color:#8B6914;">
                        <i class="bi bi-info-circle me-1"></i>Semua biaya tampil transparan sebelum booking dikonfirmasi
                    </div>
                </div>
            </div>

        </div>

        <!-- Contoh perhitungan -->
        <div class="text-center mt-5">
            <div class="d-inline-block text-start" style="background:var(--white);border-radius:var(--r);padding:1.25rem 1.75rem;border:1.5px solid var(--stone);">
                <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--bark);margin-bottom:.75rem;">Contoh Perhitungan</div>
                <div style="font-size:.87rem;color:var(--bark);">
                    Ganti baterai HP Rp 150.000 (home visit, ongkir Rp 20.000)<br>
                    = Rp 150.000 + Rp 20.000 + Rp 7.500 (fee 5%) = <strong style="color:var(--sage)">Rp 177.500</strong> COD
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════ CARA KERJA ════════════════ -->
<section class="sec-steps" id="cara-kerja">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-eyebrow">Cara Kerja</div>
            <h2 class="section-title">4 Langkah Mudah</h2>
        </div>
        <div class="row g-4 align-items-start">
            <?php
            $steps = [
                ['01','🔍','Input Keluhan',     'Ceritakan masalah perangkat. Diagnosa AI langsung membantu.'],
                ['02','🤖','Diagnosa AI',        'Gemini AI analisis keluhan dan berikan estimasi kerusakan.'],
                ['03','🔧','Booking Teknisi',    'Pilih teknisi, lihat harga lengkap (servis+ongkir+fee), konfirmasi.'],
                ['04','✅','Selesai / E-Waste',  'Perangkat diperbaiki dengan garansi, atau dikelola jadi e-waste.'],
            ];
            foreach ($steps as [$num,$em,$title,$desc]): ?>
            <div class="col-6 col-md-3">
                <div class="step-card">
                    <div class="step-num"><?= $num ?></div>
                    <div style="font-size:1.8rem;margin-bottom:.6rem;"><?= $em ?></div>
                    <h5><?= $title ?></h5>
                    <p><?= $desc ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════ ARTICLES ════════════════ -->
<?php if (!empty($articles)): ?>
<section class="sec-articles">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <div class="section-eyebrow">Edukasi</div>
                <h2 class="section-title mb-0">Artikel Terbaru</h2>
            </div>
            <a href="<?= BASE_URL ?>/articles/index.php" class="btn-hero-secondary" style="font-size:.84rem;padding:.5rem 1.2rem;">
                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($articles as $a):
                $thumbBg = match($a['category'] ?? '') {
                    'e-waste'    => 'linear-gradient(135deg,#E0F5EA,#C5E0CF)',
                    'tips'       => 'linear-gradient(135deg,#FEF3D0,#F9E0A0)',
                    'perawatan'  => 'linear-gradient(135deg,#E0F5FB,#B8E4F0)',
                    'teknologi'  => 'linear-gradient(135deg,#F2EDE3,#E8E0D4)',
                    default      => 'linear-gradient(135deg,#EAF4EF,#D0E8D8)',
                };
                $em = match($a['category'] ?? '') {
                    'e-waste' => '♻️', 'tips' => '💡', 'perawatan' => '🔧', default => '📱'
                };
            ?>
            <div class="col-md-4">
                <a href="<?= BASE_URL ?>/articles/show.php?id=<?= $a['id'] ?>" class="art-card">
                    <div class="art-thumb" style="background:<?= $thumbBg ?>"><?= $em ?></div>
                    <div class="art-body">
                        <?php if ($a['category']): ?>
                        <div class="art-cat"><?= e($a['category']) ?></div>
                        <?php endif; ?>
                        <div class="art-title"><?= e($a['title'] ?? '') ?></div>
                        <div class="art-exc"><?= e(limitStr($a['content'], 90)) ?></div>
                        <div class="art-meta">
                            <i class="bi bi-person me-1"></i><?= e($a['author_name'] ?? '') ?>
                            &nbsp;·&nbsp;
                            <?= date('d M Y', strtotime($a['created_at'])) ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════ CTA ════════════════ -->
<section class="sec-cta">
    <div class="container" style="position:relative;z-index:2;">
        <h2>Siap Memperbaiki Perangkatmu?</h2>
        <p>Daftar gratis dan dapatkan diagnosa AI langsung untuk perangkat pertamamu. Tidak perlu kartu kredit.</p>
        <?php if (!isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/register.php" class="btn-cta">🌿 Daftar Gratis Sekarang</a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/diagnosis/index.php" class="btn-cta">🤖 Mulai Diagnosa AI</a>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════ FOOTER ════════════════ -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand">🌿 ReTech Hub</div>
                <p class="footer-desc">
                    Platform servis elektronik & e-waste berbasis circular economy.
                    Perbaiki jika bisa, kelola jika tidak bisa.
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Platform</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>/diagnosis/index.php">Diagnosa AI</a></li>
                    <li><a href="<?= BASE_URL ?>/user/service/create.php">Booking Servis</a></li>
                    <li><a href="#harga">Struktur Harga</a></li>
                    <li><a href="<?= BASE_URL ?>/ewaste/index.php">E-Waste</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Informasi</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>/articles/index.php">Edukasi</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php">Daftar</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Masuk</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6>Tentang Kami</h6>
                <p class="footer-desc">
                    ReTech Hub hadir untuk mengurangi dampak e-waste di Indonesia dengan menghubungkan
                    pengguna, teknisi, dan pusat daur ulang dalam satu ekosistem digital.
                </p>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="footer-bottom text-center mb-0">
            © <?= date('Y') ?> ReTech Hub · Platform Servis Elektronik & E-Waste Indonesia
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
