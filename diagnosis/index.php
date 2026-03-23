<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('user');

pageHeader('Diagnosa AI', 'Diagnosa AI Perangkat');
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="card mb-4" style="background:linear-gradient(135deg,#10B981,#059669);border:none;">
    <div class="card-body p-4 text-white d-flex align-items-center gap-3">
        <div style="font-size:2.5rem;">🤖</div>
        <div>
            <h5 style="font-weight:700;margin-bottom:.25rem;">Diagnosa AI dengan Google Gemini</h5>
            <p style="opacity:.8;margin:0;font-size:.87rem;">Ceritakan keluhan perangkat dan dapatkan diagnosa awal secara instan.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-search me-2"></i>Form Diagnosa</div>
    <div class="card-body p-4">
        <form method="POST" action="result.php" id="form">
            <?php csrfField(); ?>
            <div class="mb-3">
                <label class="form-label fw-600">Nama / Jenis Perangkat *</label>
                <input type="text" name="device_name" class="form-control"
                       placeholder="Contoh: Laptop Asus ROG, Samsung Galaxy A52" required>
                <div class="form-text">Sertakan merek dan seri jika memungkinkan</div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Keluhan Perangkat *</label>
                <textarea name="complaint" rows="5" class="form-control"
                          placeholder="Ceritakan keluhan secara detail. Contoh: Layar sering bergaris dan mati sendiri tiba-tiba..." required minlength="10"></textarea>
                <div class="form-text">Semakin detail keluhan, semakin akurat diagnosa</div>
            </div>
            <div class="alert py-2 px-3" style="background:#F0FBF4;border:1px solid #C3E6CB;border-radius:10px;font-size:.82rem;color:#155724;">
                <i class="bi bi-lightbulb me-1"></i><strong>Tips:</strong>
                Sebutkan kapan masalah mulai terjadi, kondisi perangkat, bunyi aneh yang muncul, atau kerusakan fisik yang ada.
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2" id="submitBtn">
                <i class="bi bi-cpu me-2"></i>Mulai Diagnosa AI
            </button>
        </form>
    </div>
</div>

</div>
</div>

<script>
document.getElementById('form').addEventListener('submit', function(){
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>AI sedang menganalisis...';
});
</script>
<?php pageFooter(); ?>
