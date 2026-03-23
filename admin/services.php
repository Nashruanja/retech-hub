<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$pdo = getDB();
$filter = get('status');
$where  = $filter ? "WHERE sr.status='".htmlspecialchars($filter)."'" : '';

$services = $pdo->query("SELECT sr.*,d.device_name,d.brand,u.name AS customer_name,u2.name AS tech_name
    FROM service_requests sr
    JOIN devices d ON sr.device_id=d.id
    JOIN users u ON d.user_id=u.id
    LEFT JOIN technicians t ON sr.technician_id=t.id
    LEFT JOIN users u2 ON t.user_id=u2.id
    {$where} ORDER BY sr.created_at DESC")->fetchAll();

pageHeader('Laporan Servis','Laporan Semua Servis');
?>

<div class="d-flex gap-2 flex-wrap mb-4">
    <?php foreach (['' => 'Semua','menunggu'=>'Menunggu','diproses'=>'Diproses','selesai'=>'Selesai','tidak_bisa_diperbaiki'=>'Tidak Bisa Diperbaiki'] as $v=>$l): ?>
    <a href="services.php<?= $v ? '?status='.$v : '' ?>"
       class="btn btn-sm <?= $filter===$v?'btn-dark':'btn-outline-secondary' ?>" style="border-radius:50px;font-size:.78rem;"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Perangkat</th><th>Teknisi</th><th>Tanggal</th><th>Status</th><th>Biaya</th></tr></thead>
            <tbody>
            <?php if(empty($services)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
            <?php else: foreach($services as $s): ?>
                <tr>
                    <td class="text-muted" style="font-size:.75rem;">#<?= str_pad($s['id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td style="font-size:.85rem;"><?= e($s['customer_name']) ?></td>
                    <td><div style="font-size:.85rem;font-weight:600;"><?= e($s['device_name']) ?></div><div class="text-muted" style="font-size:.75rem;"><?= e($s['brand']) ?></div></td>
                    <td style="font-size:.85rem;"><?= e($s['tech_name'] ?? '-') ?></td>
                    <td style="font-size:.8rem;"><?= tglIndo($s['service_date']) ?></td>
                    <td><span class="badge bg-<?= statusBadge($s['status']) ?>"><?= statusLabel($s['status']) ?></span></td>
                    <td style="font-size:.83rem;"><?= $s['cost'] ? rupiah($s['cost']) : '-' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageFooter(); ?>
