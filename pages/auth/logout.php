<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';

$_SESSION = [];
session_destroy();

header('Location: ./index.php?p=login');
exit;





