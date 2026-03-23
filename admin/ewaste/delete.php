<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/ewaste/index.php');
verifyCsrf();
getDB()->prepare("DELETE FROM e_waste_locations WHERE id=?")->execute([(int)post('id')]);
redirect('/admin/ewaste/index.php', 'Lokasi berhasil dihapus.', 'success');
