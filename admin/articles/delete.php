<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/articles/index.php');
verifyCsrf();
getDB()->prepare("DELETE FROM articles WHERE id=?")->execute([(int)post('id')]);
redirect('/admin/articles/index.php', 'Artikel berhasil dihapus.', 'success');
