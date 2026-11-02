<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/order_service.php';

require_post();
$userId = require_user();

try {
    $payload = parse_json_body();
    $orderId = create_order_from_cart(
        $userId,
        (array)($payload['customer'] ?? []),
        (array)($payload['items'] ?? [])
    );

    json_ok(['orderId' => $orderId], 201);
} catch (Throwable $e) {
    json_error($e->getMessage(), 400);
}



