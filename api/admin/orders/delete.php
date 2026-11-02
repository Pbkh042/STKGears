<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../common.php';
require_once __DIR__ . '/../../../services/order_service.php';

require_admin();
require_post();

try {
    $payload = parse_json_body();
    $orderId = (int)($payload['id'] ?? 0);
    if ($orderId <= 0) {
        throw new RuntimeException('Mã đơn không hợp lệ.');
    }

    delete_order_admin($orderId);
    json_ok(['message' => 'Đã loại bỏ đơn hàng.']);
} catch (Throwable $e) {
    json_error($e->getMessage(), 400);
}



