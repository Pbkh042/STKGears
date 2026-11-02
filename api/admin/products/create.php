<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../common.php';
require_once __DIR__ . '/../../../services/product_service.php';

require_post();
require_admin();

$body = parse_json_body();

try {
    $id = create_product($body, (int)$_SESSION['admin_user_id']);
    json_ok(['id' => $id], 201);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 422);
} catch (Throwable $e) {
    json_error('Failed to create product.', 500);
}



