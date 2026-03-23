-- ============================================================
-- ReTech Hub v4 — Import di phpMyAdmin: Import → pilih file → Go
-- BARU: koordinat teknisi, alamat bengkel, setting ongkir/km, fee platform
-- ============================================================
CREATE DATABASE IF NOT EXISTS retech_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE retech_hub;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','technician','admin') DEFAULT 'user',
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    latitude DECIMAL(10,7) NULL,   -- koordinat lokasi user/customer
    longitude DECIMAL(10,7) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    keahlian VARCHAR(255) NOT NULL,
    lokasi VARCHAR(255) NOT NULL,         -- nama kota/area
    workshop_address TEXT NULL,           -- 🆕 alamat lengkap bengkel
    latitude DECIMAL(10,7) NULL,          -- 🆕 koordinat bengkel
    longitude DECIMAL(10,7) NULL,         -- 🆕 koordinat bengkel
    bio TEXT NULL,
    is_available TINYINT(1) DEFAULT 1,
    rating DECIMAL(2,1) DEFAULT 5.0,
    total_jobs INT DEFAULT 0,
    response_time INT DEFAULT 60,
    price_start INT DEFAULT 50000,
    is_verified TINYINT(1) DEFAULT 0,    -- 0=menunggu, 1=terverifikasi, 2=ditolak
    verified_at TIMESTAMP NULL,
    reject_reason VARCHAR(255) NULL,     -- alasan penolakan dari admin
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    device_type VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    purchase_year YEAR NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    technician_id INT NULL,
    complaint TEXT NOT NULL,
    diagnosis TEXT NULL,
    service_date DATE NOT NULL,
    service_type ENUM('home_visit','bring_in') DEFAULT 'bring_in',
    status ENUM('menunggu','diproses','selesai','tidak_bisa_diperbaiki') DEFAULT 'menunggu',
    -- 🆕 Alamat servis (untuk home_visit)
    customer_address TEXT NULL,           -- alamat customer saat booking
    customer_lat DECIMAL(10,7) NULL,
    customer_lng DECIMAL(10,7) NULL,
    distance_km DECIMAL(6,2) NULL,        -- jarak dalam km (dihitung otomatis)
    -- Sistem harga transparan
    service_cost DECIMAL(10,2) NULL,      -- biaya servis (teknisi terima ini)
    transport_fee DECIMAL(10,2) DEFAULT 0,-- ongkir (teknisi terima ini)
    app_fee DECIMAL(10,2) DEFAULT 0,      -- fee platform (masuk ke developer)
    total_cost DECIMAL(10,2) NULL,        -- total yang dibayar customer
    cost DECIMAL(10,2) NULL,              -- alias total_cost
    notes TEXT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    -- Garansi
    warranty_days INT DEFAULT 0,
    warranty_notes VARCHAR(255) NULL,
    warranty_until DATE NULL,
    -- Rating
    rating_by_user TINYINT NULL,
    rating_note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 🆕 Tabel pengaturan platform (admin)
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value VARCHAR(500) NOT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS e_waste_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    open_hours VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    wa_number VARCHAR(20) NULL,
    city VARCHAR(100) NULL,
    price_range VARCHAR(100) NULL,
    accepted_items TEXT NULL,
    description TEXT NULL,
    is_free TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NULL,
    author_id INT NOT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    technician_id INT NOT NULL,
    service_request_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open','answered','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA DUMMY
-- ============================================================

-- Pengaturan platform
INSERT INTO app_settings (setting_key, setting_value, description) VALUES
('transport_fee_per_km', '3000',  'Biaya ongkir per kilometer (Rp)'),
('app_fee_percent',      '5',     'Fee platform dalam persen dari biaya servis'),
('min_transport_fee',    '10000', 'Minimum biaya ongkir (Rp)'),
('max_transport_distance','30',   'Maksimal jarak layanan home visit (km)'),
('platform_name',        'ReTech Hub', 'Nama platform'),
('gemini_api_key',        '',          'API Key Google Gemini (opsional). Dapatkan di makersuite.google.com'),
('fee_info',             'Fee aplikasi 5% dari biaya servis masuk ke ReTech Hub sebagai biaya penggunaan platform.', 'Keterangan fee');

-- Users (password: password)
INSERT INTO users (name,email,password,role,phone,address,latitude,longitude) VALUES
('Admin ReTech',  'admin@retech.id',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',      '08111000001',NULL,NULL,NULL),
('Budi Santoso',  'budi@retech.id',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','technician', '08122000001',NULL,NULL,NULL),
('Siti Rahayu',   'siti@retech.id',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','technician', '08122000002',NULL,NULL,NULL),
('Ahmad Fauzi',   'ahmad@retech.id',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','technician', '08122000003',NULL,NULL,NULL),
('Dewi Kusuma',   'dewi@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user',       '08523000001','Jl. Merdeka No. 10, Jakarta Selatan',-6.2607,106.7816),
('Reza Pratama',  'reza@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','user',       '08523000002','Jl. Sudirman No. 5, Jakarta Pusat',-6.2088,106.8456);

-- Teknisi dengan koordinat bengkel
INSERT INTO technicians (user_id,keahlian,lokasi,workshop_address,latitude,longitude,bio,is_available,rating,total_jobs,response_time,price_start,is_verified,verified_at) VALUES
(2,'Laptop, PC, Printer','Jakarta Selatan','Jl. Fatmawati No. 88, Cilandak, Jakarta Selatan',-6.2900,106.7946,'Teknisi 7 tahun. Spesialis laptop gaming & ultrabook.',1,4.9,312,15,75000,1,NOW()),
(3,'Handphone, Tablet, Smartwatch','Jakarta Pusat','Jl. Imam Bonjol No. 12, Menteng, Jakarta Pusat',-6.1944,106.8319,'Ahli perangkat mobile. Ganti layar, baterai, konektor, IC.',1,4.7,198,30,50000,1,NOW()),
(4,'TV, AC, Kulkas, Mesin Cuci','Depok','Jl. Margonda Raya No. 45, Depok',-6.3728,106.8318,'Teknisi elektronik rumah tangga bersertifikat resmi.',1,4.8,245,20,100000,1,NOW());

INSERT INTO devices (user_id,device_name,device_type,brand,purchase_year,description) VALUES
(5,'Laptop ROG G15','Laptop','Asus',2021,'Laptop gaming, sering dipakai 8+ jam.'),
(5,'Galaxy A52 5G','Handphone','Samsung',2022,NULL),
(6,'Smart TV 43 Inch','TV','LG',2020,NULL);

-- Servis dengan data harga lengkap + jarak
INSERT INTO service_requests
(device_id,technician_id,complaint,diagnosis,service_date,service_type,status,
 customer_address,customer_lat,customer_lng,distance_km,
 service_cost,transport_fee,app_fee,total_cost,cost,notes,warranty_days,warranty_notes,warranty_until,payment_method)
VALUES
(1,1,'Layar bergaris dan sering mati.','Konektor fleksibel bermasalah.',
 DATE_ADD(NOW(),INTERVAL 2 DAY),'bring_in','diproses',
 'Jl. Merdeka No. 10, Jakarta Selatan',-6.2607,106.7816,0,
 300000,0,15000,315000,315000,'Konektor baru dipasang.',30,'Garansi konektor',DATE_ADD(NOW(),INTERVAL 32 DAY),'COD'),

(2,2,'Baterai cepat habis 2 jam.','Baterai aus, siklus >500x.',
 DATE_SUB(NOW(),INTERVAL 3 DAY),'home_visit','selesai',
 'Jl. Merdeka No. 10, Jakarta Selatan',-6.2607,106.7816,5.2,
 180000,15600,9000,204600,204600,'Baterai baru, 100%.',90,'Garansi baterai',DATE_ADD(NOW(),INTERVAL 87 DAY),'COD'),

(3,3,'TV tidak menyala sama sekali.',NULL,
 DATE_ADD(NOW(),INTERVAL 1 DAY),'bring_in','menunggu',
 NULL,NULL,NULL,0,
 NULL,0,0,NULL,NULL,NULL,0,NULL,NULL,'COD');

INSERT INTO consultations (user_id,technician_id,service_request_id,subject,status) VALUES
(5,1,1,'Estimasi biaya ganti konektor layar','answered'),
(5,2,2,'Konfirmasi garansi baterai','answered');

INSERT INTO chat_messages (consultation_id,sender_id,message) VALUES
(1,5,'Halo Pak Budi, estimasi biaya ganti konektor layar ROG G15 berapa ya?'),
(1,2,'Halo Kak! Biaya servis Rp 300.000. Fee platform 5% = Rp 15.000. Total COD Rp 315.000. Garansi 30 hari. Kamu bawa sendiri ya, gratis ongkir 😊'),
(1,5,'Oke, saya booking sekarang!'),
(2,5,'Pak Siti, garansi baterainya berapa lama?'),
(2,3,'90 hari Kak! Total tadi Rp 204.600 sudah termasuk ongkir Rp 15.600 (5.2km × Rp 3.000/km) + fee app Rp 9.000. Simpan notanya 🛡️');

INSERT INTO e_waste_locations (name,address,open_hours,phone,wa_number,city,price_range,accepted_items,description,is_free) VALUES
('Bank Sampah Elektronik Jakarta','Jl. Gatot Subroto No. 25, Jaksel','Sen-Jum 08:00-17:00','021-5551234','6281234567890','Jakarta Selatan','GRATIS','Laptop, HP, TV, Kulkas, AC','Drop-off gratis semua jenis elektronik.',1),
('Pusat Daur Ulang Depok','Jl. Margonda Raya No. 112, Depok','Sen-Sab 09:00-16:00','021-7771234','6289876543210','Depok','Rp 10.000–50.000','Baterai, PCB, Kabel','Spesialis daur ulang baterai lithium.',0),
('Eco Tech Recycling Bogor','Jl. Raya Bogor KM 30','Sel-Min 08:00-15:00','0251-8881234','6285551234567','Bogor','GRATIS + Reward','Semua elektronik','Kumpulkan poin tiap antar e-waste.',1),
('Green Electronics Tangerang','Jl. MH Thamrin Blok B5, Tangerang','Sen-Jum 07:30-16:30','021-5442222','6287890123456','Tangerang','Rp 5.000–25.000','HP, Laptop, Kamera','Sertifikat daur ulang resmi.',0);

INSERT INTO articles (title,slug,content,category,author_id,is_published) VALUES
('7 Tips Merawat Laptop agar Tahan Lama','tips-laptop','<p>Laptop butuh perawatan rutin.</p><h4>1. Ventilasi</h4><p>Gunakan cooling pad.</p><h4>2. Bersihkan Debu</h4><p>Compressed air tiap 3 bulan.</p><h4>3. Kelola Baterai</h4><p>Jaga 20%-80%.</p>','perawatan',1,1),
('Bahaya E-Waste','bahaya-e-waste','<p>2 juta ton e-waste tiap tahun di Indonesia.</p><h4>Bahan Berbahaya</h4><ul><li>Timbal, Merkuri, Kadmium</li></ul><h4>Solusi</h4><p>Serahkan ke bank sampah elektronik.</p>','e-waste',1,1),
('Tips Baterai HP Awet','tips-baterai','<p>Isi di kisaran 20%-80%. Hindari charge semalam penuh.</p>','tips',1,1),
('Circular Economy Elektronik','circular-economy','<p>Perbaiki dulu, baru daur ulang jika tidak bisa. Itulah circular economy.</p>','teknologi',1,1);
