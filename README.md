# ♻️ ReTech Hub — PHP Native
> Platform Servis Elektronik & E-Waste | PHP Native + Bootstrap 5 + MySQL PDO

---

## 🚀 Cara Install (5 Menit)

### Persyaratan
- **Laragon** (sudah jalan) → Apache + MySQL + PHP 8.x
- Browser

---

### Langkah 1 — Letakkan di Folder Laragon

Ekstrak ZIP ini ke:
```
C:\laragon\www\retech-hub\
```

Struktur folder harus seperti ini:
```
C:\laragon\www\retech-hub\
├── index.php          ← homepage
├── login.php
├── register.php
├── logout.php
├── config\
│   └── database.php   ← konfigurasi DB
├── includes\
│   ├── functions.php
│   └── layout.php
├── user\
├── technician\
├── admin\
├── diagnosis\
├── ewaste\
├── articles\
└── sql\
    └── retech_hub.sql ← file SQL
```

---

### Langkah 2 — Import Database

1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Klik **Import** (tab atas)
3. Pilih file: `sql/retech_hub.sql`
4. Klik **Go**

> Database `retech_hub` beserta semua tabel dan data dummy akan otomatis dibuat.

---

### Langkah 3 — Konfigurasi

Buka file `config/database.php`, sesuaikan jika perlu:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'retech_hub');
define('DB_USER', 'root');
define('DB_PASS', '');          // Laragon default: kosong

define('GEMINI_API_KEY', '');   // Opsional — isi untuk aktifkan AI
define('BASE_URL', '/retech-hub'); // Sesuaikan nama folder
```

---

### Langkah 4 — Buka di Browser

```
http://localhost/retech-hub/
```

---

## 🔑 Akun Default

| Role     | Email                  | Password   |
|----------|------------------------|------------|
| 👑 Admin  | admin@retech.id        | password   |
| 🔧 Teknisi| budi@retech.id         | password   |
| 🔧 Teknisi| siti@retech.id         | password   |
| 👤 User   | dewi@example.com       | password   |
| 👤 User   | reza@example.com       | password   |

---

## 🤖 Aktifkan Diagnosa AI (Opsional)

1. Buka: https://makersuite.google.com/app/apikey
2. Buat API Key baru (gratis)
3. Buka `config/database.php`
4. Isi:
```php
define('GEMINI_API_KEY', 'AIzaSy_your_key_here');
```

> Tanpa API key, fitur diagnosa tetap berjalan dengan respons default.

---

## 📁 Struktur File

```
index.php                     Halaman utama (landing page)
login.php                     Halaman login
register.php                  Halaman registrasi
logout.php                    Proses logout

config/
  database.php                Koneksi PDO + konstanta konfigurasi

includes/
  functions.php               Helper: auth, flash, sanitasi, Gemini, format
  layout.php                  Header & footer dashboard (sidebar)

user/
  dashboard.php               Dashboard pelanggan
  devices/
    index.php                 Daftar perangkat
    create.php                Tambah perangkat
    show.php                  Detail + riwayat servis per barang ⭐
    edit.php                  Edit perangkat
    delete.php                Hapus perangkat
  service/
    index.php                 Daftar riwayat servis
    create.php                Form booking servis
    show.php                  Tracking status servis

diagnosis/
  index.php                   Form diagnosa AI
  result.php                  Hasil diagnosa Gemini

technician/
  dashboard.php               Dashboard teknisi
  services.php                Daftar permintaan servis
  update.php                  Update status + biaya + catatan ⭐

admin/
  dashboard.php               Dashboard admin
  services.php                Laporan semua servis
  users/
    index.php                 Kelola user
    create.php                Tambah user
    edit.php                  Edit user
    delete.php                Hapus user
  ewaste/
    index.php                 Daftar lokasi e-waste
    create.php                Tambah lokasi
    edit.php                  Edit lokasi
    delete.php                Hapus lokasi
  articles/
    index.php                 Daftar artikel
    create.php                Tulis artikel baru
    edit.php                  Edit artikel
    delete.php                Hapus artikel

ewaste/
  index.php                   Halaman publik lokasi e-waste

articles/
  index.php                   Halaman publik daftar artikel
  show.php                    Detail artikel

sql/
  retech_hub.sql              SQL lengkap (buat tabel + data dummy)
```

---

## 🔐 Sistem Role & Akses

| Fitur                        | User | Teknisi | Admin |
|------------------------------|:----:|:-------:|:-----:|
| Landing page & Edukasi       |  ✅  |   ✅    |  ✅   |
| Diagnosa AI                  |  ✅  |   —     |  —    |
| Kelola Perangkat             |  ✅  |   —     |  —    |
| Booking Servis               |  ✅  |   —     |  —    |
| Tracking Servis              |  ✅  |   —     |  —    |
| Update Status & Biaya        |  —   |   ✅    |  —    |
| Kelola User                  |  —   |   —     |  ✅   |
| Kelola E-Waste & Artikel     |  —   |   —     |  ✅   |
| Laporan Semua Servis         |  —   |   —     |  ✅   |

---

## ⚠️ Troubleshooting

**Halaman tidak ditemukan / error BASE_URL:**
→ Pastikan nama folder di `laragon/www/` adalah `retech-hub`
→ Atau ubah `BASE_URL` di `config/database.php` sesuai nama folder

**Koneksi database gagal:**
→ Pastikan Laragon sudah dijalankan (MySQL aktif)
→ Cek konfigurasi di `config/database.php`

**Session error:**
→ Pastikan PHP `session.save_path` bisa ditulis

---

**Dibuat dengan ❤️ — PHP Native, tanpa framework, langsung jalan!**
