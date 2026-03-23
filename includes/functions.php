<?php
// ============================================================
// includes/functions.php
// Fungsi-fungsi pembantu yang digunakan di seluruh aplikasi
// ============================================================

require_once __DIR__ . '/../config/database.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── AUTH ─────────────────────────────────────────────────────

/** Cek apakah user sudah login */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Ambil data user yang sedang login */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/** Paksa login - redirect ke login jika belum */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/login.php', 'Silakan login terlebih dahulu.');
    }
}

/** Paksa role tertentu */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles)) {
        $default = match($_SESSION['user_role']) {
            'admin'      => '/admin/dashboard.php',
            'technician' => '/technician/dashboard.php',
            default      => '/user/dashboard.php',
        };
        redirect($default, 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}

// ── REDIRECT ─────────────────────────────────────────────────

/** Redirect dengan pesan flash */
function redirect(string $path, string $msg = '', string $type = 'error'): void {
    if ($msg) setFlash($msg, $type);
    header('Location: ' . BASE_URL . $path);
    exit;
}

// ── FLASH MESSAGES ───────────────────────────────────────────

function setFlash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Tampilkan flash message sebagai Bootstrap alert */
function showFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $type  = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger');
    $icon  = $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle';
    echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
        <i class='bi bi-{$icon} me-2'></i>" . htmlspecialchars($flash['msg']) . "
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

// ── SANITASI INPUT ───────────────────────────────────────────

// FIX: nullable string agar tidak error saat nilai dari DB adalah NULL
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function clean(string $str): string {
    return trim(htmlspecialchars(strip_tags($str)));
}

function post(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// ── FORMAT ───────────────────────────────────────────────────

function rupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function tglIndo(string $date): string {
    if (!$date) return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $d = date_create($date);
    return date_format($d, 'd') . ' ' . $bulan[(int)date_format($d, 'n')] . ' ' . date_format($d, 'Y');
}

function limitStr(string $str, int $limit = 80): string {
    $plain = strip_tags($str);
    return strlen($plain) > $limit ? substr($plain, 0, $limit) . '...' : $plain;
}

// ── STATUS SERVIS ─────────────────────────────────────────────

function statusLabel(string $status): string {
    return match($status) {
        'menunggu'              => 'Menunggu',
        'diproses'              => 'Diproses',
        'selesai'               => 'Selesai',
        'tidak_bisa_diperbaiki' => 'Tidak Bisa Diperbaiki',
        default                 => ucfirst($status),
    };
}

function statusBadge(string $status): string {
    return match($status) {
        'menunggu'              => 'warning',
        'diproses'              => 'info',
        'selesai'               => 'success',
        'tidak_bisa_diperbaiki' => 'danger',
        default                 => 'secondary',
    };
}

// ── GEMINI AI ─────────────────────────────────────────────────

function geminiDiagnose(string $deviceName, string $complaint): array {
    $apiKey = getSetting("gemini_api_key", "");

    if (empty($apiKey)) {
        return fallbackDiagnosis($deviceName, $complaint);
    }

    $prompt = "Kamu adalah teknisi elektronik berpengalaman. Berikan diagnosa awal untuk perangkat berikut:\n\n"
            . "Perangkat: {$deviceName}\n"
            . "Keluhan: {$complaint}\n\n"
            . "Berikan jawaban dalam format TEPAT seperti ini:\n"
            . "KEMUNGKINAN_KERUSAKAN: [tulis kemungkinan penyebab]\n"
            . "TINGKAT_KERUSAKAN: [pilih: Ringan / Sedang / Berat]\n"
            . "SARAN: [tulis saran tindakan]\n\n"
            . "Jawab singkat, jelas, dalam Bahasa Indonesia.";

    $url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}";
    $data = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 400],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false, // untuk Laragon lokal
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) return fallbackDiagnosis($deviceName, $complaint);

    $json = json_decode($response, true);
    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$text) return fallbackDiagnosis($deviceName, $complaint);

    // Parse hasil
    $result = [
        'kemungkinan_kerusakan' => '',
        'tingkat_kerusakan'     => 'Sedang',
        'saran'                 => '',
    ];

    if (preg_match('/KEMUNGKINAN_KERUSAKAN:\s*(.+)/i', $text, $m)) $result['kemungkinan_kerusakan'] = trim($m[1]);
    if (preg_match('/TINGKAT_KERUSAKAN:\s*(.+)/i', $text, $m)) {
        $t = trim($m[1]);
        if (in_array($t, ['Ringan','Sedang','Berat'])) $result['tingkat_kerusakan'] = $t;
    }
    if (preg_match('/SARAN:\s*(.+)/i', $text, $m)) $result['saran'] = trim($m[1]);

    if (empty($result['kemungkinan_kerusakan'])) $result['kemungkinan_kerusakan'] = $text;

    return $result;
}

function fallbackDiagnosis(string $deviceName, string $complaint): array {
    return [
        'kemungkinan_kerusakan' => "Berdasarkan keluhan \"{$complaint}\", kemungkinan ada masalah pada komponen internal {$deviceName}. Diperlukan pemeriksaan langsung oleh teknisi.",
        'tingkat_kerusakan'     => 'Sedang',
        'saran'                 => 'Disarankan membawa perangkat ke teknisi untuk pemeriksaan lebih lanjut.',
    ];
}

function damageColor(string $tingkat): string {
    return match(strtolower($tingkat)) {
        'ringan' => 'success',
        'berat'  => 'danger',
        default  => 'warning',
    };
}

// ── CSRF TOKEN ───────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): void {
    echo '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
}

// ── DEVICE ICON ───────────────────────────────────────────────

function deviceEmoji(string $type): string {
    return match(strtolower($type)) {
        'laptop'    => '💻',
        'handphone', 'hp', 'smartphone' => '📱',
        'tv'        => '📺',
        'ac'        => '❄️',
        'kulkas'    => '🧊',
        'tablet'    => '📟',
        'printer'   => '🖨️',
        default     => '🔌',
    };
}

// ── APP SETTINGS ──────────────────────────────────────────────

/**
 * Ambil nilai setting dari tabel app_settings
 * Contoh: getSetting('transport_fee_per_km') → '3000'
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val  = $stmt->fetchColumn();
        $cache[$key] = $val !== false ? $val : $default;
    } catch (\Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

// ── KALKULASI JARAK & ONGKIR ──────────────────────────────────

/**
 * Hitung jarak antara dua titik koordinat (Haversine formula)
 * Return: jarak dalam kilometer
 */
function calcDistanceKm(
    float $lat1, float $lng1,
    float $lat2, float $lng2
): float {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat/2) * sin($dLat/2)
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
          * sin($dLng/2) * sin($dLng/2);
    $c    = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earthRadius * $c, 2);
}

/**
 * Hitung ongkir berdasarkan jarak dan setting admin
 * Return: biaya ongkir dalam Rupiah
 */
function calcTransportFee(float $distanceKm): int {
    $perKm  = (int)getSetting('transport_fee_per_km', '3000');
    $minFee = (int)getSetting('min_transport_fee', '10000');
    $fee    = (int)round($distanceKm * $perKm);
    return max($fee, $minFee);
}

/**
 * Hitung fee aplikasi
 * Return: fee dalam Rupiah
 */
function calcAppFee(float $serviceCost): int {
    $percent = (float)getSetting('app_fee_percent', '5');
    return (int)round($serviceCost * $percent / 100);
}

/**
 * Format jarak ke string yang ramah
 */
function formatDistance(float $km): string {
    if ($km < 1) return round($km * 1000) . ' m';
    return number_format($km, 1, ',', '.') . ' km';
}
