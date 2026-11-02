<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../common.php';
require_once __DIR__ . '/../../../services/product_service.php';

require_post();
require_admin();

$body = parse_json_body();
$id = isset($body['id']) ? (int)$body['id'] : 0;
if ($id <= 0) {
    json_error('Invalid product id.', 422);
}

try {
    update_product($id, $body);
    json_ok();
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 422);
} catch (Throwable $e) {
    json_error('Failed to update product.', 500);
}



