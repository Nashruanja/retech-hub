<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/users/index.php');
verifyCsrf();
$id = (int)post('id');
if ($id === $_SESSION['user_id']) redirect('/admin/users/index.php', 'Tidak bisa menghapus akun sendiri.');
$pdo = getDB();
$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
redirect('/admin/users/index.php', 'User berhasil dihapus.', 'success');
