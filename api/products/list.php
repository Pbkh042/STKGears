<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/product_service.php';

try {
    $search = trim((string)($_GET['search'] ?? ''));
    $category = trim((string)($_GET['category'] ?? 'all'));
    $sort = trim((string)($_GET['sort'] ?? 'popular'));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = max(1, min(50, (int)($_GET['pageSize'] ?? 10)));
    $offset = ($page - 1) * $pageSize;

    $total = count_products_public($search, $category);
    $products = list_products_public($search, $category, $sort, $pageSize, $offset);

    json_ok([
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => (int)max(1, ceil($total / $pageSize)),
        ],
    ]);
} catch (Throwable $e) {
    json_error('Failed to load products.', 500);
}



