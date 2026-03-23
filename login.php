<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) {
    $r = match($_SESSION['user_role']) { 'admin'=>'admin','technician'=>'technician',default=>'user' };
    redirect("/$r/dashboard.php");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = post('email');
    $password = post('password');
    if ($email && $password) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $r = match($user['role']) { 'admin'=>'admin','technician'=>'technician',default=>'user' };
            redirect("/$r/dashboard.php", 'Selamat datang, '.$user['name'].'! 👋', 'success');
        } else { $error = 'Email atau password salah.'; }
    } else { $error = 'Email dan password wajib diisi.'; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Masuk | ReTech Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;margin:0;padding:0;}

body {
    min-height: 100vh;
    display: flex;
    background: #F4F8F5;
}

/* Left panel */
.left-panel {
    width: 420px;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 2.5rem;
    position: relative;
    z-index: 2;
    box-shadow: 4px 0 24px rgba(77,124,95,.06);
}

/* Right decorative panel */
.right-panel {
    flex: 1;
    background: linear-gradient(135deg, #1A3A28 0%, #2D5016 45%, #1A3A28 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}
.right-panel::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(111,207,158,.25) 0%, transparent 65%);
    top: -100px; right: -100px;
    border-radius: 50%;
}
.right-panel::after {
    content: '';
    position: absolute;
    width: 350px; height: 350px;
    background: radial-gradient(circle, rgba(139,92,246,.2) 0%, transparent 65%);
    bottom: -80px; left: -60px;
    border-radius: 50%;
}
.right-content { position: relative; z-index: 2; max-width: 380px; }
.right-content h2 { color: #fff; font-size: 1.9rem; font-weight: 800; line-height: 1.3; margin-bottom: .75rem; }
.right-content p { color: rgba(255,255,255,.58); font-size: .9rem; line-height: 1.7; margin-bottom: 2rem; }

/* Feature pills */
.feat-pill {
    display: flex; align-items: center; gap: .65rem;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px; padding: .7rem 1rem;
    margin-bottom: .6rem; color: #fff; font-size: .85rem;
}
.feat-pill .icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; }

/* Brand */
.brand { display: flex; align-items: center; gap: .6rem; margin-bottom: 2.5rem; }
.brand .logo { width: 40px; height: 40px; background: linear-gradient(135deg, #81C99A, #4D7C5F); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.brand .name { font-size: 1.1rem; font-weight: 800; color: #1A3A28; }
.brand .tagline { font-size: .65rem; color: #94A3B8; }

/* Form */
h4.title { font-size: 1.3rem; font-weight: 800; color: #1A3A28; margin-bottom: .2rem; }
p.subtitle { color: #94A3B8; font-size: .84rem; margin-bottom: 1.75rem; }

.form-label { font-size: .8rem; font-weight: 700; color: #1A3A28; margin-bottom: .3rem; }

.input-wrap { position: relative; }
.input-wrap i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #A7D3B5; font-size: .95rem; z-index: 5; }
.input-wrap input {
    height: 46px; padding-left: 2.5rem; padding-right: .85rem;
    border: 1.5px solid #E8F5EE; border-radius: 11px !important;
    font-size: .87rem; width: 100%; outline: none;
    background: #FAFDF8; color: #1A3A28; transition: .15s;
}
.input-wrap input:focus { border-color: #4D7C5F; box-shadow: 0 0 0 3px rgba(77,124,95,.1); background: #fff; }
.input-wrap input::placeholder { color: #A7D3B5; }

.btn-submit {
    width: 100%; height: 46px;
    background: linear-gradient(135deg, #81C99A, #4D7C5F);
    color: #fff; border: none; border-radius: 11px;
    font-size: .9rem; font-weight: 700; cursor: pointer;
    transition: .2s; display: flex; align-items: center; justify-content: center; gap: .4rem;
}
.btn-submit:hover { background: linear-gradient(135deg, #4D7C5F, #2D5016); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(77,124,95,.3); }

.divider { text-align: center; color: #E2E8F0; font-size: .78rem; margin: 1.25rem 0; position: relative; }
.divider::before, .divider::after { content: ''; position: absolute; top: 50%; height: 1px; width: 40%; background: #E8F5EE; }
.divider::before { left: 0; } .divider::after { right: 0; }

.link-text { text-align: center; font-size: .83rem; color: #94A3B8; }
.link-text a { color: #4D7C5F; font-weight: 700; text-decoration: none; }
.link-text a:hover { text-decoration: underline; }

.demo-box { background: #FAFDF8; border: 1px dashed #C5E0CF; border-radius: 10px; padding: .75rem 1rem; margin-top: 1.25rem; font-size: .75rem; color: #6B7280; }
.demo-box strong { color: #1A3A28; }

.alert-err { background: #FFF1F2; border: 1px solid #FECDD3; border-radius: 10px; padding: .6rem .9rem; font-size: .82rem; color: #BE123C; margin-bottom: 1rem; display: flex; align-items: center; gap: .4rem; }

@media (max-width: 768px) { .right-panel { display: none; } .left-panel { width: 100%; padding: 2rem 1.5rem; } }
</style>
</head>
<body>

<!-- Left: Form -->
<div class="left-panel">
    <div class="brand">
        <div class="logo">♻️</div>
        <div>
            <div class="name">ReTech Hub</div>
            <div class="tagline">Platform Servis Elektronik &amp; E-Waste</div>
        </div>
    </div>

    <h4 class="title">Selamat Datang 👋</h4>
    <p class="subtitle">Masuk untuk lanjutkan ke dashboard kamu.</p>

    <?php if ($error): ?>
    <div class="alert-err"><i class="bi bi-exclamation-circle"></i><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($flash = getFlash()): ?>
    <div class="alert-err" style="background:#ECFDF5;border-color:#A7F3D0;color:#065F46;"><i class="bi bi-check-circle"></i><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php csrfField(); ?>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" placeholder="nama@email.com" value="<?= e(post('email')) ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="bi bi-box-arrow-in-right"></i>Masuk ke Dashboard
        </button>
    </form>

    <div class="divider">atau</div>
    <div class="link-text">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar gratis</a></div>

    <div class="demo-box">
        <strong>Demo akun:</strong><br>
        👑 <strong>Admin</strong> — admin@retech.id<br>
        🔧 <strong>Teknisi</strong> — budi@retech.id<br>
        👤 <strong>User</strong> — dewi@example.com<br>
        <span style="color:#A7D3B5;">Password semua: <strong>password</strong></span>
    </div>
</div>

<!-- Right: Decorative -->
<div class="right-panel">
    <div class="right-content">
        <h2>Perbaiki Jika Bisa,<br>Kelola Jika Tidak</h2>
        <p>Platform servis elektronik berbasis circular economy. Diagnosa AI, teknisi terpercaya, dan pengelolaan e-waste bertanggung jawab.</p>

        <div class="feat-pill">
            <div class="icon" style="background:rgba(111,207,158,.25);">🤖</div>
            <div><strong style="display:block;font-size:.83rem;">Diagnosa AI</strong><span style="font-size:.75rem;opacity:.65;">Powered by Google Gemini</span></div>
        </div>
        <div class="feat-pill">
            <div class="icon" style="background:rgba(52,211,153,.2);">⭐</div>
            <div><strong style="display:block;font-size:.83rem;">Teknisi Terrating</strong><span style="font-size:.75rem;opacity:.65;">Filter by rating, tercepat, terlaris</span></div>
        </div>
        <div class="feat-pill">
            <div class="icon" style="background:rgba(251,191,36,.2);">🛡️</div>
            <div><strong style="display:block;font-size:.83rem;">Garansi Servis</strong><span style="font-size:.75rem;opacity:.65;">Klaim garansi langsung via WhatsApp</span></div>
        </div>
        <div class="feat-pill">
            <div class="icon" style="background:rgba(248,113,113,.2);">♻️</div>
            <div><strong style="display:block;font-size:.83rem;">E-Waste Management</strong><span style="font-size:.75rem;opacity:.65;">Drop-off atau chat WA lokasi terdekat</span></div>
        </div>
    </div>
</div>

</body>
</html>
