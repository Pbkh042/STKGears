<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/order_service.php';

$userId = require_user();

try {
    json_ok(['orders' => list_orders_user($userId)]);
} catch (Throwable $e) {
    json_error('Không tải được lịch sử đơn hàng.', 500);
}



