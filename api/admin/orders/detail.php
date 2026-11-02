<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../common.php';
require_once __DIR__ . '/../../../services/order_service.php';

require_admin();
$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    json_error('Mã đơn không hợp lệ.', 400);
}

try {
    $order = get_order_admin_detail($orderId);
    if (!$order) {
        json_error('Không tìm thấy đơn hàng.', 404);
    }
    json_ok(['order' => $order, 'statusOptions' => ORDER_STATUS_OPTIONS]);
} catch (Throwable $e) {
    json_error('Không tải được chi tiết đơn hàng.', 500);
}



