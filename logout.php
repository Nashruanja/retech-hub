<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    session_destroy();
}
header('Location: ' . BASE_URL . '/login.php');
exit;
