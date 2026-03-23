<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('technician');

$pdo = getDB();
$uid = $_SESSION['user_id'];
$techStmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id=?");
$techStmt->execute([$uid]); $tech = $techStmt->fetch();
if (!$tech) redirect('/technician/dashboard.php');
$tid = $tech['id'];

// Filter status
$filterStatus = get('status');
$where = "WHERE sr.technician_id=?";
$params = [$tid];
if ($filterStatus) { $where .= " AND sr.status=?"; $params[] = $filterStatus; }

$stmt = $pdo->prepare("SELECT sr.*,d.device_name,d.brand,u.name AS customer_name
    FROM service_requests sr
    JOIN devices d ON sr.device_id=d.id
    JOIN users u ON d.user_id=u.id
    {$where} ORDER BY sr.created_at DESC");
$stmt->execute($params);
$services = $stmt->fetchAll();

pageHeader('Permintaan Servis','Semua Permintaan Servis');
?>

<div class="d-flex gap-2 flex-wrap mb-4">
    <?php
    $statuses = ['' => 'Semua', 'menunggu' => 'Menunggu', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'tidak_bisa_diperbaiki' => 'Tdk Bisa Diperbaiki'];
    foreach ($statuses as $val => $lbl):
    ?>
    <a href="services.php<?= $val ? '?status='.$val : '' ?>"
       class="btn btn-sm <?= $filterStatus === $val ? 'btn-dark' : 'btn-outline-secondary' ?>"
       style="border-radius:50px;font-size:.78rem;">
       <?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>#</th><th>Pelanggan & Perangkat</th><th>Keluhan</th><th>Tanggal</th><th>Jenis</th><th>Status</th><th>Biaya</th><th></th></tr>
            </thead>
            <tbody>
            <?php if(empty($services)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada permintaan servis.</td></tr>
            <?php else: foreach($services as $s): ?>
                <tr>
                    <td class="text-muted" style="font-size:.75rem;">#<?= str_pad($s['id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;"><?= e($s['customer_name']) ?></div>
                        <div class="text-muted" style="font-size:.75rem;"><?= e($s['brand'].' '.$s['device_name']) ?></div>
                    </td>
                    <td style="font-size:.82rem;max-width:180px;"><?= e(limitStr($s['complaint'],50)) ?></td>
                    <td style="font-size:.8rem;"><?= tglIndo($s['service_date']) ?></td>
                    <td><span class="badge bg-light text-muted" style="font-size:.7rem;"><?= $s['service_type']==='home_visit'?'Ke Rumah':'Bawa Masuk' ?></span></td>
                    <td><span class="badge bg-<?= statusBadge($s['status']) ?>"><?= statusLabel($s['status']) ?></span></td>
                    <td style="font-size:.82rem;"><?= $s['cost'] ? rupiah($s['cost']) : '-' ?></td>
                    <td>
                        <a href="update.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" style="font-size:.7rem;padding:.25rem .55rem;">
                            <i class="bi bi-pencil"></i> Update
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageFooter(); ?>
