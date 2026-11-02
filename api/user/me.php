<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';

if (empty($_SESSION['user_id'])) {
    json_ok(['authenticated' => false]);
}

json_ok([
    'authenticated' => true,
    'user' => [
        'id' => (int)$_SESSION['user_id'],
        'username' => (string)($_SESSION['user_username'] ?? 'user'),
    ],
]);




