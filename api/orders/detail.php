<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/order_service.php';

$userId = require_user();
$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    json_error('Mã đơn không hợp lệ.', 400);
}

try {
    $order = get_order_user_detail($userId, $orderId);
    if (!$order) {
        json_error('Không tìm thấy đơn hàng.', 404);
    }
    json_ok(['order' => $order]);
} catch (Throwable $e) {
    json_error('Không tải được chi tiết đơn.', 500);
}



