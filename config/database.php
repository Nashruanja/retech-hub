<?php
// ============================================================
// config/database.php
// Konfigurasi koneksi database & konstanta aplikasi
//
// SESUAIKAN dengan pengaturan Laragon kamu:
// - DB_PASS: biasanya kosong '' di Laragon default
// - BASE_URL: sesuaikan nama folder di laragon/www/
// ============================================================

// ── DATABASE ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'retech_hub');
define('DB_USER',    'root');
define('DB_PASS',    '');          // Laragon default: kosong
define('DB_CHARSET', 'utf8mb4');

// ── BASE URL ─────────────────────────────────────────────────
// Sesuaikan dengan nama folder kamu di C:\laragon\www\
// Contoh: folder = retech-hub → BASE_URL = '/retech-hub'
// Contoh: folder = myapp     → BASE_URL = '/myapp'
define('BASE_URL', '/retech-hub');

// ── KONEKSI PDO ───────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Tampilkan pesan error yang mudah dibaca
        $msg = $e->getMessage();
        die('
        <div style="font-family:monospace;max-width:640px;margin:40px auto;padding:24px;
                    background:#FFF1F2;border:2px solid #FECDD3;border-radius:12px;">
            <h3 style="color:#9F1239;margin:0 0 12px">❌ Koneksi Database Gagal</h3>
            <p style="color:#6B7280;margin:0 0 8px;">Error: <strong style="color:#1F2937">'
            . htmlspecialchars($msg) . '</strong></p>
            <hr style="border-color:#FECDD3;margin:12px 0;">
            <p style="color:#6B7280;margin:0;font-size:.85rem;"><strong>Cek:</strong><br>
            1. Laragon sudah dijalankan (MySQL harus hijau)<br>
            2. Database <code>retech_hub</code> sudah dibuat di phpMyAdmin<br>
            3. Konfigurasi di <code>config/database.php</code> sudah benar<br>
            4. Sudah import file <code>sql/retech_hub.sql</code></p>
        </div>');
    }

    return $pdo;
}
