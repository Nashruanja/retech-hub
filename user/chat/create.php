<?php
// user/chat/create.php
// Konsultasi terhubung ke teknisi yang pernah dipesan
// Menampilkan riwayat servis + harga per teknisi
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/layout.php';
requireRole('user');

$pdo = getDB();
$uid = $_SESSION['user_id'];

// Ambil teknisi yang pernah dipesan user ini (dari riwayat servis)
// Kalau belum pernah pesan, tampilkan semua teknisi tersedia
$serviceId = (int)get('service_id');   // dari halaman show servis
$techId    = (int)get('tech_id');      // pre-select teknisi tertentu

// Teknisi dari riwayat servis user (punya relasi nyata)
$techFromHistory = $pdo->prepare("
    SELECT DISTINCT t.*, u.name, u.phone,
        COUNT(sr.id) AS total_servis_dengan_user,
        MAX(sr.service_date) AS last_servis,
        SUM(sr.total_cost) AS total_spent
    FROM technicians t
    JOIN users u ON t.user_id = u.id
    JOIN service_requests sr ON sr.technician_id = t.id
    JOIN devices d ON sr.device_id = d.id
    WHERE d.user_id = ?
    GROUP BY t.id
    ORDER BY last_servis DESC
");
$techFromHistory->execute([$uid]);
$histTechs = $techFromHistory->fetchAll();

// Semua teknisi tersedia (untuk user yang belum pernah pesan)
$allTechs = $pdo->query("SELECT t.*,u.name,u.phone FROM technicians t JOIN users u ON t.user_id=u.id WHERE t.is_available=1 AND t.is_verified=1 ORDER BY t.rating DESC")->fetchAll();

// Riwayat servis per teknisi yang dipilih (untuk ditampilkan sebagai konteks)
$selectedTechId = $techId ?: (int)post('technician_id');
$techHistory = [];
if ($selectedTechId) {
    $hist = $pdo->prepare("
        SELECT sr.*, d.device_name, d.brand, d.device_type
        FROM service_requests sr
        JOIN devices d ON sr.device_id = d.id
        WHERE d.user_id = ? AND sr.technician_id = ?
        ORDER BY sr.created_at DESC
        LIMIT 5
    ");
    $hist->execute([$uid, $selectedTechId]);
    $techHistory = $hist->fetchAll();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $tid     = (int)post('technician_id');
    $subject = post('subject');
    $msg     = post('message');
    $sid     = (int)post('service_request_id') ?: null;

    if (!$tid)     $errors[] = 'Pilih teknisi.';
    if (!$subject) $errors[] = 'Topik pertanyaan wajib diisi.';
    if (!$msg)     $errors[] = 'Pesan wajib diisi.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO consultations (user_id,technician_id,service_request_id,subject,status) VALUES (?,?,?,?,'open')")
            ->execute([$uid, $tid, $sid, $subject]);
        $cid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO chat_messages (consultation_id,sender_id,message) VALUES (?,?,?)")
            ->execute([$cid, $uid, $msg]);
        redirect('/user/chat/chat.php?id='.$cid, 'Konsultasi berhasil dibuat!', 'success');
    }
}

pageHeader('Konsultasi Baru', 'Mulai Konsultasi');
?>

<div class="row g-4">

<!-- KIRI: Form Konsultasi -->
<div class="col-lg-7">
<div class="card">
    <div class="card-header" style="background:var(--sage-lt,#ECFDF5);">
        <i class="bi bi-chat-dots me-2" style="color:var(--mint,#10B981)"></i>
        Konsultasi Baru dengan Teknisi
    </div>
    <div class="card-body p-4">

        <?php if ($errors): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:.82rem;">
            <?= implode(' &bull; ', array_map('htmlspecialchars', $errors)) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="consultForm">
            <?php csrfField(); ?>

            <!-- Pilih Teknisi -->
            <div class="mb-4">
                <label class="form-label">Pilih Teknisi *</label>

                <?php if (!empty($histTechs)): ?>
                <!-- Teknisi dari riwayat servis -->
                <div class="mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted,#94A3B8);">
                    🔧 Pernah Servis Dengan
                </div>
                <?php foreach ($histTechs as $t):
                    $isSel = $selectedTechId === (int)$t['id'] || (int)post('technician_id') === (int)$t['id'];
                ?>
                <div class="tech-opt mb-2 p-3 rounded-3"
                     style="border:2px solid <?= $isSel ? 'var(--mint,#10B981)' : '#E2E8F0' ?>;cursor:pointer;background:<?= $isSel ? '#ECFDF5' : '#FAFBFC' ?>;transition:.15s;"
                     onclick="selectTechForm(this, <?= $t['id'] ?>, '<?= e($t['name'] ?? '') ?>')">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;background:linear-gradient(135deg,#A7F3D0,#6EE7B7);border-radius:11px;display:flex;align-items:center;justify-content:center;color:#065F46;font-weight:800;font-size:1rem;flex-shrink:0;">
                            <?= strtoupper(substr($t['name'] ?? 'T', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-weight:700;font-size:.88rem;"><?= e($t['name'] ?? '') ?></div>
                            <div style="font-size:.75rem;color:var(--muted,#94A3B8);"><?= e($t['keahlian'] ?? '') ?></div>
                            <div style="font-size:.73rem;color:#10B981;font-weight:600;margin-top:.15rem;">
                                <?= $t['total_servis_dengan_user'] ?>× servis bersama kamu
                                · Total: <?= rupiah($t['total_spent'] ?? 0) ?>
                            </div>
                        </div>
                        <div style="font-size:.78rem;font-weight:700;color:#D97706;">
                            ⭐ <?= number_format($t['rating'] ?? 0, 1) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!empty($allTechs)): ?>
                <div class="mt-3 mb-2" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted,#94A3B8);">
                    🌐 Atau Pilih Teknisi Lain
                </div>
                <select name="_other_tech" class="form-select form-select-sm" id="otherTechSelect"
                        onchange="if(this.value) selectTechForm(null, this.value, this.options[this.selectedIndex].text.split(' —')[0])">
                    <option value="">-- Teknisi lain --</option>
                    <?php foreach ($allTechs as $t):
                        $inHistory = array_filter($histTechs, fn($h) => $h['id'] == $t['id']);
                        if ($inHistory) continue; // Skip yang sudah tampil di atas
                    ?>
                    <option value="<?= $t['id'] ?>">
                        <?= e($t['name'] ?? '') ?> — <?= e($t['keahlian'] ?? '') ?> (⭐<?= number_format($t['rating'] ?? 0,1) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <?php else: ?>
                <!-- Belum pernah servis — tampilkan semua teknisi -->
                <select name="_other_tech" class="form-select" id="otherTechSelect"
                        onchange="if(this.value) selectTechForm(null, this.value, this.options[this.selectedIndex].text.split(' —')[0])">
                    <option value="">-- Pilih Teknisi --</option>
                    <?php foreach ($allTechs as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['name'] ?? '') ?> — <?= e($t['keahlian'] ?? '') ?> (⭐<?= number_format($t['rating'] ?? 0,1) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <!-- Hidden input yang benar-benar dikirim -->
                <input type="hidden" name="technician_id" id="techIdInput"
                       value="<?= post('technician_id', $techId) ?>">

                <div id="techSelectedInfo" class="mt-2" style="font-size:.78rem;color:#10B981;font-weight:600;display:<?= $selectedTechId ? 'block' : 'none' ?>;">
                    ✓ <span id="techSelectedName">Teknisi dipilih</span>
                </div>
            </div>

            <!-- Link ke servis (opsional) -->
            <?php
            // Ambil riwayat servis untuk di-link
            $userServices = $pdo->prepare("
                SELECT sr.id, d.device_name, d.brand, sr.status, sr.service_date, sr.technician_id
                FROM service_requests sr
                JOIN devices d ON sr.device_id = d.id
                WHERE d.user_id = ?
                ORDER BY sr.created_at DESC
                LIMIT 10
            ");
            $userServices->execute([$uid]);
            $userServices = $userServices->fetchAll();
            ?>
            <?php if (!empty($userServices)): ?>
            <div class="mb-3">
                <label class="form-label">Tautkan ke Servis (opsional)</label>
                <select name="service_request_id" class="form-select" id="serviceLink"
                        onchange="filterTechByService(this.value)">
                    <option value="">-- Konsultasi Umum --</option>
                    <?php foreach ($userServices as $sr): ?>
                    <option value="<?= $sr['id'] ?>"
                            data-tech="<?= $sr['technician_id'] ?>"
                            <?= ($serviceId == $sr['id'] || post('service_request_id') == $sr['id']) ? 'selected' : '' ?>>
                        #<?= str_pad($sr['id'],4,'0',STR_PAD_LEFT) ?>
                        — <?= e($sr['brand'] ?? '') ?> <?= e($sr['device_name'] ?? '') ?>
                        (<?= statusLabel($sr['status']) ?>)
                        · <?= tglIndo($sr['service_date']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Tautkan jika konsultasi terkait servis tertentu, agar teknisi bisa lihat konteksnya.</div>
            </div>
            <?php endif; ?>

            <!-- Topik -->
            <div class="mb-3">
                <label class="form-label">Topik Pertanyaan *</label>
                <input type="text" name="subject" class="form-control"
                       placeholder="Contoh: Tanya estimasi biaya, klaim garansi, follow-up servis..."
                       value="<?= e(post('subject')) ?>" required>
            </div>

            <!-- Pesan -->
            <div class="mb-4">
                <label class="form-label">Pesan Pertama *</label>
                <textarea name="message" rows="4" class="form-control"
                          placeholder="Tulis pertanyaanmu secara detail agar teknisi bisa bantu dengan tepat..."
                          required><?= e(post('message')) ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Kirim Konsultasi
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>

<!-- KANAN: Riwayat Servis dengan Teknisi Terpilih -->
<div class="col-lg-5">
    <div class="card" id="techHistoryCard" style="<?= $selectedTechId ? '' : 'display:none;' ?>">
        <div class="card-header" style="background:#ECFDF5;">
            <i class="bi bi-clock-history me-2 text-success"></i>
            Riwayat Servis Dengan Teknisi Ini
        </div>
        <div class="card-body p-0" id="techHistoryBody">
            <?php if (!empty($techHistory)): ?>
            <?php foreach ($techHistory as $h): ?>
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div style="font-weight:700;font-size:.85rem;"><?= e($h['device_name'] ?? '') ?></div>
                    <span class="badge bg-<?= statusBadge($h['status']) ?>" style="font-size:.68rem;"><?= statusLabel($h['status']) ?></span>
                </div>
                <div style="font-size:.77rem;color:var(--muted,#94A3B8);">
                    <?= e($h['brand'] ?? '') ?> · <?= tglIndo($h['service_date']) ?>
                </div>
                <div style="font-size:.77rem;color:var(--muted,#94A3B8);"><?= e(limitStr($h['complaint'] ?? '', 55)) ?></div>
                <?php if ($h['total_cost']): ?>
                <div style="font-size:.8rem;font-weight:700;color:#10B981;margin-top:.2rem;">
                    <?= rupiah($h['total_cost']) ?>
                    <?php if ($h['transport_fee'] > 0): ?>
                    <span style="font-size:.7rem;font-weight:400;color:var(--muted,#94A3B8);">
                        (termasuk ongkir <?= rupiah($h['transport_fee']) ?>)
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-4 text-muted" style="font-size:.85rem;">
                <i class="bi bi-clipboard-x d-block display-5 mb-2 opacity-25"></i>
                Belum ada riwayat servis dengan teknisi ini.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hint jika belum pilih teknisi -->
    <div id="techHintCard" class="card" style="<?= $selectedTechId ? 'display:none;' : '' ?>">
        <div class="card-body text-center py-5" style="color:var(--muted,#94A3B8);">
            <i class="bi bi-person-plus display-3 d-block mb-3 opacity-20"></i>
            <p style="font-size:.85rem;">Pilih teknisi di sebelah kiri untuk melihat riwayat servis dan harga bersama mereka.</p>
        </div>
    </div>
</div>

</div>

<script>
// Data riwayat servis per teknisi (di-load via AJAX atau tersedia dari PHP)
const techId = <?= json_encode($selectedTechId) ?>;

function selectTechForm(el, id, name) {
    // Reset card visual
    document.querySelectorAll('.tech-opt').forEach(c => {
        c.style.border = '2px solid #E2E8F0';
        c.style.background = '#FAFBFC';
    });

    // Aktifkan card yang dipilih
    if (el) {
        el.style.border = '2px solid var(--mint, #10B981)';
        el.style.background = '#ECFDF5';
    }

    // Update hidden input
    document.getElementById('techIdInput').value = id;
    document.getElementById('techSelectedInfo').style.display = 'block';
    document.getElementById('techSelectedName').textContent = name + ' terpilih ✓';

    // Show history panel
    document.getElementById('techHistoryCard').style.display = 'block';
    document.getElementById('techHintCard').style.display = 'none';

    // Load history via page reload dengan param
    const url = new URL(window.location.href);
    url.searchParams.set('tech_id', id);
    // Refresh halaman untuk tampilkan riwayat (simple approach)
    window.location.href = url.toString() + '#techHistoryCard';
}

function filterTechByService(serviceId) {
    if (!serviceId) return;
    const opt = document.querySelector(`#serviceLink option[value="${serviceId}"]`);
    if (opt) {
        const techIdFromService = opt.dataset.tech;
        if (techIdFromService) {
            document.getElementById('techIdInput').value = techIdFromService;
        }
    }
}

// Pre-select jika ada tech_id di URL
<?php if ($techId): ?>
document.querySelectorAll('.tech-opt').forEach(c => {
    if (c.querySelector('[onclick*="<?= $techId ?>"]') || c.getAttribute('onclick')?.includes('<?= $techId ?>')) {
        c.style.border = '2px solid var(--mint, #10B981)';
        c.style.background = '#ECFDF5';
    }
});
<?php endif; ?>
</script>

<?php pageFooter(); ?>
