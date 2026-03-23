<?php
// includes/layout.php — ReTech Hub v4 — Eco Sage Green Theme

function pageHeader(string $title = 'Dashboard', string $pageTitle = ''): void {
    $user   = currentUser();
    $role   = $_SESSION['user_role'] ?? '';
    $active = $_SERVER['PHP_SELF'];
    $base   = BASE_URL;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | ReTech Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* =============================================
       ECO SAGE GREEN — konsisten dengan homepage
    ============================================= */
    :root {
        --sage:     #4D7C5F;
        --sage-dk:  #2D5016;
        --sage-lt:  #E8F5EE;
        --sage-md:  #C5E0CF;
        --mint:     #6FCF9E;
        --forest:   #1A3A28;
        --cream:    #FAFDF8;
        --sand:     #F4F8F5;
        --border:   #D0E8D8;
        --txt:      #1A3A28;
        --muted:    #6B8F78;
        --white:    #FFFFFF;
        --shadow:   0 2px 10px rgba(77,124,95,.08);
        --r:        14px;
    }
    * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
    body { background: var(--sand); color: var(--txt); margin: 0; }

    /* ── SIDEBAR ─────────────────────────────── */
    .sidebar {
        width: 242px; min-height: 100vh;
        background: var(--forest);
        position: fixed; top: 0; left: 0; z-index: 1000;
        display: flex; flex-direction: column;
        transition: transform .3s;
    }
    .sb-brand {
        padding: 1.1rem .95rem;
        border-bottom: 1px solid rgba(255,255,255,.08);
        display: flex; align-items: center; gap: .55rem;
    }
    .sb-brand .lbox {
        width: 34px; height: 34px;
        background: var(--sage);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .sb-brand .bname { color: #fff; font-size: .95rem; font-weight: 800; line-height: 1.1; }
    .sb-brand .bsub  { color: rgba(255,255,255,.35); font-size: .61rem; }

    .sb-nav { padding: .35rem 0; flex: 1; overflow-y: auto; }
    .sb-sec {
        padding: .55rem .9rem .18rem;
        font-size: .6rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.3px;
        color: rgba(255,255,255,.22);
    }
    .sb-nav a {
        display: flex; align-items: center; gap: .55rem;
        padding: .52rem .85rem; margin: .03rem .5rem;
        color: rgba(255,255,255,.6);
        text-decoration: none; font-size: .82rem; font-weight: 500;
        border-radius: 9px; transition: .15s;
    }
    .sb-nav a i { font-size: .92rem; width: 17px; text-align: center; flex-shrink: 0; }
    .sb-nav a:hover { background: rgba(111,207,158,.15); color: rgba(255,255,255,.9); }
    .sb-nav a.active {
        background: rgba(111,207,158,.2);
        color: var(--mint); font-weight: 700;
    }
    .sb-nav a.active i { color: var(--mint); }

    .sb-user {
        padding: .8rem .95rem;
        border-top: 1px solid rgba(255,255,255,.07);
        display: flex; align-items: center; gap: .55rem;
    }
    .av {
        width: 30px; height: 30px;
        background: var(--sage);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 800; font-size: .73rem; flex-shrink: 0;
    }
    .un { color: #fff; font-size: .75rem; font-weight: 700; }
    .ur { color: rgba(255,255,255,.32); font-size: .63rem; }

    /* ── MAIN ───────────────────────────────── */
    .main { margin-left: 242px; min-height: 100vh; }
    .topbar {
        background: var(--white);
        padding: .78rem 1.3rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 100;
        box-shadow: 0 1px 4px rgba(77,124,95,.06);
    }
    .ptitle { font-weight: 700; font-size: .95rem; color: var(--txt); margin: 0; }
    .btn-lo {
        background: transparent; border: 1.5px solid #FECDD3;
        color: #E53E3E; border-radius: 8px;
        padding: .27rem .78rem; font-size: .77rem; font-weight: 600;
        cursor: pointer; transition: .15s;
        display: flex; align-items: center; gap: .3rem;
    }
    .btn-lo:hover { background: #FFF5F5; }
    .page-body { padding: 1.3rem; }

    /* ── CARDS ──────────────────────────────── */
    .card {
        border: 1px solid var(--border);
        border-radius: var(--r);
        box-shadow: var(--shadow);
        background: var(--white);
    }
    .card-header {
        background: transparent;
        border-bottom: 1px solid var(--border);
        padding: .82rem 1.1rem;
        font-weight: 700; font-size: .87rem; color: var(--txt);
    }
    .card-body { padding: 1.1rem; }

    /* ── STAT CARDS ─────────────────────────── */
    .stat-card {
        border-radius: var(--r);
        padding: 1.1rem 1.2rem;
        color: #fff; position: relative; overflow: hidden;
        border: none; box-shadow: 0 4px 16px rgba(0,0,0,.12);
    }
    .stat-card .num { font-size: 1.75rem; font-weight: 800; line-height: 1; }
    .stat-card .lbl { font-size: .74rem; font-weight: 500; opacity: .82; margin-top: .2rem; }
    .stat-card .ico { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%); font-size: 2.7rem; opacity: .15; }

    /* ── BUTTONS ───────────────────────────── */
    .btn-primary, .btn-primary:focus { background: var(--sage); border-color: var(--sage); color: #fff; font-weight: 600; }
    .btn-primary:hover { background: var(--sage-dk); border-color: var(--sage-dk); color: #fff; }
    .btn-outline-primary { color: var(--sage); border-color: var(--sage); font-weight: 600; }
    .btn-outline-primary:hover { background: var(--sage); border-color: var(--sage); color: #fff; }
    .btn-success, .btn-success:focus { background: var(--sage); border-color: var(--sage); color: #fff; font-weight: 600; }
    .btn-success:hover { background: var(--sage-dk); border-color: var(--sage-dk); color: #fff; }
    .btn-outline-success { color: var(--sage); border-color: var(--sage); font-weight: 600; }
    .btn-outline-success:hover { background: var(--sage); border-color: var(--sage); color: #fff; }

    /* ── FORMS ─────────────────────────────── */
    .form-control, .form-select {
        border-color: var(--border); border-radius: 9px !important;
        font-size: .86rem; padding: .5rem .8rem; background: #FAFDF8;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--sage);
        box-shadow: 0 0 0 3px rgba(77,124,95,.12);
        background: #fff;
    }
    .form-label { font-size: .8rem; font-weight: 600; color: var(--txt); margin-bottom: .3rem; }
    .form-text  { font-size: .73rem; color: var(--muted); }
    .input-group-text { background: var(--sage-lt); border-color: var(--border); font-size: .86rem; }

    /* ── TABLE ──────────────────────────────── */
    .table { font-size: .83rem; }
    .table th {
        font-size: .7rem; text-transform: uppercase; letter-spacing: .6px;
        color: var(--muted); font-weight: 700; background: var(--sand);
        padding: .58rem .95rem;
    }
    .table td { padding: .7rem .95rem; vertical-align: middle; }
    .table tbody tr:hover { background: var(--sage-lt); }

    /* ── BADGES ─────────────────────────────── */
    .badge { font-weight: 600; border-radius: 6px; padding: .3em .65em; }
    .bg-warning  { background: #FEF3C7 !important; color: #B45309 !important; }
    .bg-info     { background: #DBEAFE !important; color: #1D4ED8 !important; }
    .bg-success  { background: var(--sage-lt) !important; color: var(--sage-dk) !important; }
    .bg-danger   { background: #FFE4E6 !important; color: #BE123C !important; }
    .bg-secondary{ background: var(--sand) !important; color: var(--muted) !important; }
    .bg-light    { background: var(--sand) !important; }

    /* ── ALERTS ─────────────────────────────── */
    .alert { border-radius: var(--r); border: none; font-size: .86rem; }
    .alert-success { background: var(--sage-lt); color: var(--sage-dk); border: 1px solid var(--sage-md); }
    .alert-danger  { background: #FFF1F2; color: #9F1239; border: 1px solid #FECDD3; }
    .alert-warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .alert-info    { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-242px); }
        .sidebar.show { transform: translateX(0); }
        .main { margin-left: 0; }
    }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <div class="lbox">🌿</div>
        <div>
            <div class="bname">ReTech Hub</div>
            <div class="bsub">Servis &amp; E-Waste</div>
        </div>
    </div>
    <nav class="sb-nav">
        <?php if ($role === 'admin'): ?>
            <div class="sb-sec">Manajemen</div>
            <a href="<?= $base ?>/admin/dashboard.php" <?= str_contains($active,'admin/dashboard') ? 'class="active"' : '' ?>><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="<?= $base ?>/admin/users/index.php" <?= str_contains($active,'admin/users') ? 'class="active"' : '' ?>><i class="bi bi-people"></i> Kelola User</a>
            <a href="<?= $base ?>/admin/services.php" <?= str_contains($active,'admin/services') ? 'class="active"' : '' ?>><i class="bi bi-tools"></i> Semua Servis</a>
            <a href="<?= $base ?>/admin/technician_verify.php" <?= str_contains($active,'technician_verify') ? 'class="active"' : '' ?>><i class="bi bi-shield-check"></i> Verifikasi Teknisi</a>
            <a href="<?= $base ?>/admin/settings.php" <?= str_contains($active,'admin/settings') ? 'class="active"' : '' ?>><i class="bi bi-gear"></i> Pengaturan</a>
            <div class="sb-sec">Konten</div>
            <a href="<?= $base ?>/admin/ewaste/index.php" <?= str_contains($active,'admin/ewaste') ? 'class="active"' : '' ?>><i class="bi bi-recycle"></i> Lokasi E-Waste</a>
            <a href="<?= $base ?>/admin/articles/index.php" <?= str_contains($active,'admin/articles') ? 'class="active"' : '' ?>><i class="bi bi-journal-text"></i> Artikel</a>

        <?php elseif ($role === 'technician'): ?>
            <div class="sb-sec">Menu Teknisi</div>
            <a href="<?= $base ?>/technician/dashboard.php" <?= str_contains($active,'technician/dashboard') ? 'class="active"' : '' ?>><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="<?= $base ?>/technician/services.php" <?= str_contains($active,'technician/services') ? 'class="active"' : '' ?>><i class="bi bi-tools"></i> Permintaan Servis</a>
            <a href="<?= $base ?>/technician/chat.php" <?= str_contains($active,'technician/chat') ? 'class="active"' : '' ?>><i class="bi bi-chat-dots"></i> Konsultasi</a>
            <a href="<?= $base ?>/technician/profile.php" <?= str_contains($active,'technician/profile') ? 'class="active"' : '' ?>><i class="bi bi-person-gear"></i> Profil &amp; Lokasi</a>

        <?php else: ?>
            <div class="sb-sec">Menu</div>
            <a href="<?= $base ?>/user/dashboard.php" <?= str_contains($active,'user/dashboard') ? 'class="active"' : '' ?>><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="<?= $base ?>/diagnosis/index.php" <?= str_contains($active,'diagnosis') ? 'class="active"' : '' ?>><i class="bi bi-cpu"></i> Diagnosa AI</a>
            <a href="<?= $base ?>/user/devices/index.php" <?= str_contains($active,'user/devices') ? 'class="active"' : '' ?>><i class="bi bi-phone"></i> Perangkat Saya</a>
            <a href="<?= $base ?>/user/service/index.php" <?= str_contains($active,'user/service') ? 'class="active"' : '' ?>><i class="bi bi-tools"></i> Riwayat Servis</a>
            <a href="<?= $base ?>/user/chat/index.php" <?= str_contains($active,'user/chat') ? 'class="active"' : '' ?>><i class="bi bi-chat-dots"></i> Konsultasi</a>
            <div class="sb-sec">Informasi</div>
            <a href="<?= $base ?>/ewaste/index.php"><i class="bi bi-recycle"></i> Lokasi E-Waste</a>
            <a href="<?= $base ?>/articles/index.php"><i class="bi bi-book"></i> Edukasi</a>
        <?php endif; ?>
        <div class="sb-sec">Lainnya</div>
        <a href="<?= $base ?>/index.php"><i class="bi bi-house"></i> Beranda</a>
    </nav>
    <?php if ($user): ?>
    <div class="sb-user">
        <div class="av"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
        <div>
            <div class="un"><?= htmlspecialchars($user['name'] ?? '') ?></div>
            <div class="ur"><?= ucfirst($role) ?></div>
        </div>
    </div>
    <?php endif; ?>
</aside>

<div class="main">
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm border-0 d-md-none"
                    onclick="document.getElementById('sidebar').classList.toggle('show')"
                    style="color:var(--muted)">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="ptitle"><?= htmlspecialchars($pageTitle ?: $title) ?></span>
        </div>
        <form method="POST" action="<?= $base ?>/logout.php" class="m-0">
            <?php csrfField(); ?>
            <button type="submit" class="btn-lo">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
    <div class="page-body">
        <?php showFlash(); ?>
<?php
}

function pageFooter(): void {
    echo '</div></div>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</body></html>';
}
