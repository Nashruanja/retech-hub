<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('user');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/user/devices/index.php');
verifyCsrf();
$id = (int)post('id');
$pdo = getDB();
$stmt = $pdo->prepare("DELETE FROM devices WHERE id=? AND user_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
redirect('/user/devices/index.php', 'Perangkat berhasil dihapus.', 'success');
