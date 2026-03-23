<?php
require_once __DIR__ . '/includes/functions.php';
if (isLoggedIn()) redirect('/user/dashboard.php');

$registerAs = get('as', 'user'); // 'user' atau 'technician'
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name       = post('name');
    $email      = post('email');
    $phone      = post('phone');
    $password   = post('password');
    $confirm    = post('password_confirm');
    $role       = post('role') === 'technician' ? 'technician' : 'user';
    // Data teknisi
    $keahlian   = post('keahlian');
    $lokasi     = post('lokasi');
    $workshopAddr = post('workshop_address');
    $bio        = post('bio');
    $ktpNumber  = post('ktp_number'); // untuk verifikasi

    if (!$name)   $errors[] = 'Nama wajib diisi.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';
    if ($role === 'technician') {
        if (!$keahlian)     $errors[] = 'Keahlian wajib diisi.';
        if (!$lokasi)       $errors[] = 'Kota/area kerja wajib diisi.';
        if (!$workshopAddr) $errors[] = 'Alamat bengkel wajib diisi.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?"); $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Email sudah terdaftar. Gunakan email lain atau login.';
        } else {
            // Buat akun user
            $pdo->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)")
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone ?: null]);
            $uid = $pdo->lastInsertId();

            if ($role === 'technician') {
                // Buat profil teknisi — is_verified=0 (menunggu verifikasi admin)
                $pdo->prepare("INSERT INTO technicians (user_id,keahlian,lokasi,workshop_address,bio,is_available,is_verified,price_start) VALUES (?,?,?,?,?,0,0,50000)")
                    ->execute([$uid, $keahlian, $lokasi, $workshopAddr, $bio ?: null]);

                // Simpan nomor KTP ke session sementara untuk admin (dalam produksi simpan ke tabel docs)
                // Di sini kita simpan di bio saja sebagai placeholder
                redirect('/login.php',
                    '✅ Pendaftaran teknisi berhasil! Akunmu sedang dalam proses verifikasi admin (1-2 hari kerja). Kamu akan bisa login setelah disetujui.',
                    'success'
                );
            } else {
                $_SESSION['user_id']   = $uid;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $name;
                redirect('/user/dashboard.php', 'Akun berhasil dibuat! Selamat datang, '.$name.' 🎉', 'success');
            }
        }
    }
    $registerAs = post('role') === 'technician' ? 'technician' : 'user';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Daftar | ReTech Hub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;margin:0;padding:0;}
body{min-height:100vh;background:linear-gradient(160deg,#EAF4EF,#F2EDE3,#EAF4EF);display:flex;align-items:center;justify-content:center;padding:1.5rem;}
.wrap{width:100%;max-width:540px;}
.card-wrap{background:#fff;border-radius:20px;padding:2.25rem;box-shadow:0 8px 40px rgba(77,124,95,.12);}
.brand{display:flex;align-items:center;gap:.6rem;margin-bottom:1.75rem;}
.brand .logo{width:38px;height:38px;background:linear-gradient(135deg,#81C99A,#4D7C5F);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.brand .name{font-size:1rem;font-weight:800;color:#1A3A28;}
.brand .sub{font-size:.63rem;color:#6B8F78;}
h4.title{font-size:1.2rem;font-weight:800;color:#1A3A28;margin-bottom:.15rem;}
p.sub2{color:#6B8F78;font-size:.82rem;margin-bottom:1.25rem;}

/* Tab toggle */
.role-tabs{display:flex;gap:.5rem;background:#F4F8F5;border-radius:12px;padding:.3rem;margin-bottom:1.5rem;}
.role-tab{flex:1;text-align:center;padding:.5rem;border-radius:9px;cursor:pointer;font-size:.85rem;font-weight:600;color:#6B8F78;transition:.18s;border:none;background:transparent;}
.role-tab.active{background:#fff;color:#1A3A28;box-shadow:0 2px 8px rgba(77,124,95,.12);}
.role-tab:hover:not(.active){color:#4D7C5F;}

/* Form fields */
.form-label{font-size:.79rem;font-weight:700;color:#1A3A28;margin-bottom:.28rem;}
.inp{position:relative;}
.inp i{position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:#A7D3B5;font-size:.9rem;z-index:5;}
.inp input,.inp textarea{padding-left:2.4rem;border:1.5px solid #D0E8D8;border-radius:10px!important;font-size:.85rem;width:100%;outline:none;background:#FAFDF8;color:#1A3A28;transition:.15s;}
.inp input{height:44px;padding-right:.8rem;}
.inp input:focus,.inp textarea:focus{border-color:#4D7C5F;box-shadow:0 0 0 3px rgba(77,124,95,.1);background:#fff;}
.inp input::placeholder,.inp textarea::placeholder{color:#A7D3B5;}
.inp textarea{padding:.6rem .8rem .6rem 2.4rem;resize:vertical;}

.btn-submit{width:100%;height:44px;background:linear-gradient(135deg,#81C99A,#4D7C5F);color:#fff;border:none;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:.4rem;}
.btn-submit:hover{background:linear-gradient(135deg,#4D7C5F,#2D5016);transform:translateY(-1px);box-shadow:0 6px 20px rgba(77,124,95,.3);}
.link-text{text-align:center;font-size:.82rem;color:#6B8F78;margin-top:1rem;}
.link-text a{color:#4D7C5F;font-weight:700;text-decoration:none;}
.err-box{background:#FFF1F2;border:1px solid #FECDD3;border-radius:10px;padding:.6rem .9rem;font-size:.8rem;color:#BE123C;margin-bottom:1rem;}
.info-box{background:#E8F5EE;border:1px solid #C5E0CF;border-radius:10px;padding:.75rem 1rem;font-size:.8rem;color:#2D5016;margin-bottom:1rem;}
.section-divider{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#6B8F78;margin:1.25rem 0 .75rem;display:flex;align-items:center;gap:.5rem;}
.section-divider::before,.section-divider::after{content:'';flex:1;height:1px;background:#D0E8D8;}
</style>
</head>
<body>
<div class="wrap">
<div class="card-wrap">
    <!-- Brand -->
    <div class="brand">
        <div class="logo">🌿</div>
        <div><div class="name">ReTech Hub</div><div class="sub">Platform Servis Elektronik &amp; E-Waste</div></div>
    </div>

    <h4 class="title">Buat Akun Baru</h4>
    <p class="sub2">Pilih jenis akun yang sesuai dengan kamu.</p>

    <!-- Toggle Role -->
    <div class="role-tabs">
        <button type="button" class="role-tab <?= $registerAs !== 'technician' ? 'active' : '' ?>"
                onclick="switchRole('user')">
            👤 Saya Pelanggan
        </button>
        <button type="button" class="role-tab <?= $registerAs === 'technician' ? 'active' : '' ?>"
                onclick="switchRole('technician')">
            🔧 Saya Teknisi
        </button>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="err-box">
        <?php foreach ($errors as $er): echo '<div>⚠ '.e($er).'</div>'; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Info teknisi -->
    <div id="techInfo" class="info-box" style="display:<?= $registerAs === 'technician' ? 'block' : 'none' ?>;">
        🛡️ <strong>Akun teknisi perlu verifikasi admin</strong> sebelum bisa menerima servis.
        Proses verifikasi 1–2 hari kerja. Kamu akan dihubungi via WhatsApp.
    </div>

    <form method="POST" id="regForm">
        <?php csrfField(); ?>
        <input type="hidden" name="role" id="roleInput" value="<?= $registerAs === 'technician' ? 'technician' : 'user' ?>">

        <!-- Data Umum -->
        <div class="mb-3">
            <label class="form-label">Nama Lengkap *</label>
            <div class="inp"><i class="bi bi-person"></i>
                <input type="text" name="name" placeholder="Nama lengkap kamu" value="<?= e(post('name')) ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Email *</label>
            <div class="inp"><i class="bi bi-envelope"></i>
                <input type="email" name="email" placeholder="nama@email.com" value="<?= e(post('email')) ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">No. WhatsApp *</label>
            <div class="inp"><i class="bi bi-whatsapp" style="color:#25D366"></i>
                <input type="text" name="phone" placeholder="08xxxxxxxxxx" value="<?= e(post('phone')) ?>">
            </div>
            <div style="font-size:.72rem;color:#6B8F78;margin-top:.2rem;">
                <?php if ($registerAs === 'technician'): ?>
                Nomor WA aktif — admin akan menghubungi untuk verifikasi
                <?php else: ?>
                Untuk notifikasi status servis
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Khusus Teknisi -->
        <div id="techFields" style="display:<?= $registerAs === 'technician' ? 'block' : 'none' ?>;">
            <div class="section-divider">Informasi Bengkel</div>

            <div class="mb-3">
                <label class="form-label">Keahlian Servis *</label>
                <div class="inp"><i class="bi bi-tools"></i>
                    <input type="text" name="keahlian" placeholder="Contoh: Laptop, Handphone, TV, AC"
                           value="<?= e(post('keahlian')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Kota / Area Kerja *</label>
                <div class="inp"><i class="bi bi-geo-alt"></i>
                    <input type="text" name="lokasi" placeholder="Contoh: Jakarta Selatan, Depok"
                           value="<?= e(post('lokasi')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat Bengkel / Workshop *</label>
                <div class="inp"><i class="bi bi-shop"></i>
                    <input type="text" name="workshop_address"
                           placeholder="Jl. ... No. ..., Kelurahan, Kota"
                           value="<?= e(post('workshop_address')) ?>">
                </div>
                <div style="font-size:.72rem;color:#6B8F78;margin-top:.2rem;">Ditampilkan ke pelanggan saat memilih teknisi</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi / Pengalaman</label>
                <div class="inp" style="position:relative;">
                    <i class="bi bi-file-text" style="position:absolute;left:.82rem;top:.75rem;color:#A7D3B5;z-index:5;"></i>
                    <textarea name="bio" rows="2"
                              style="padding:.6rem .8rem .6rem 2.4rem;border:1.5px solid #D0E8D8;border-radius:10px;font-size:.85rem;width:100%;outline:none;background:#FAFDF8;color:#1A3A28;"
                              placeholder="Ceritakan pengalaman & spesialisasimu (opsional)"><?= e(post('bio')) ?></textarea>
                </div>
            </div>

            <div class="info-box" style="font-size:.78rem;">
                📋 Setelah mendaftar, admin akan memeriksa data kamu dan menghubungi via WhatsApp untuk konfirmasi.
                Akunmu aktif setelah diverifikasi.
            </div>
        </div>

        <!-- Password -->
        <div class="section-divider">Password</div>
        <div class="row g-2 mb-4">
            <div class="col-6">
                <label class="form-label">Password *</label>
                <div class="inp"><i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="Min. 8 karakter" required>
                </div>
            </div>
            <div class="col-6">
                <label class="form-label">Konfirmasi *</label>
                <div class="inp"><i class="bi bi-lock-fill"></i>
                    <input type="password" name="password_confirm" placeholder="Ulangi" required>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:.5rem;font-size:.78rem;color:#6B8F78;margin-bottom:1rem;">
            <input type="checkbox" required id="agree" style="margin-top:2px;accent-color:#4D7C5F;">
            <label for="agree">Saya menyetujui syarat &amp; ketentuan penggunaan ReTech Hub.</label>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <i class="bi bi-person-plus" id="submitIcon"></i>
            <span id="submitText">Buat Akun Pelanggan</span>
        </button>
    </form>

    <div class="link-text">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Masuk di sini</a></div>
</div>
<div style="text-align:center;margin-top:.85rem;">
    <a href="<?= BASE_URL ?>/index.php" style="color:#6B8F78;font-size:.78rem;text-decoration:none;">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke beranda
    </a>
</div>
</div>

<script>
function switchRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('techFields').style.display = role === 'technician' ? 'block' : 'none';
    document.getElementById('techInfo').style.display   = role === 'technician' ? 'block' : 'none';
    document.querySelectorAll('.role-tab').forEach((t,i) => {
        t.classList.toggle('active', (i===0 && role==='user') || (i===1 && role==='technician'));
    });
    document.getElementById('submitIcon').className = role === 'technician' ? 'bi bi-wrench' : 'bi bi-person-plus';
    document.getElementById('submitText').textContent = role === 'technician' ? 'Daftar sebagai Teknisi' : 'Buat Akun Pelanggan';
    // Required fields teknisi
    const techInputs = document.querySelectorAll('#techFields input[type="text"]');
    techInputs.forEach((inp, i) => { if(i < 3) inp.required = role === 'technician'; });
}
// Init
switchRole('<?= $registerAs === 'technician' ? 'technician' : 'user' ?>');
</script>
</body>
</html>
