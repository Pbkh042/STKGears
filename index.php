<?php
declare(strict_types=1);
// Ghi chú: Router trung tâm, chỉ giữ 1 entry-point để điều hướng toàn bộ trang.

$routes = [
    'home' => __DIR__ . '/pages/shop/home.php',
    'products' => __DIR__ . '/pages/shop/products.php',
    'product' => __DIR__ . '/pages/shop/product.php',
    'policy' => __DIR__ . '/pages/shop/policy.php',
    'login' => __DIR__ . '/pages/auth/login.php',
    'logout' => __DIR__ . '/pages/auth/logout.php',

    'admin-products' => __DIR__ . '/pages/admin/products.php',
    'admin-add-product' => __DIR__ . '/pages/admin/add-product.php',
    'admin-edit-product' => __DIR__ . '/pages/admin/edit-product.php',
    'admin-orders' => __DIR__ . '/pages/admin/orders.php',
    'admin-order-detail' => __DIR__ . '/pages/admin/order-detail.php',

    'user-history' => __DIR__ . '/pages/user/history.php',
    'user-order-detail' => __DIR__ . '/pages/user/order-detail.php',
    'user-password' => __DIR__ . '/pages/user/password.php',
    'profile' => __DIR__ . '/pages/account/profile.php',
];

$page = (string)($_GET['p'] ?? 'home');
$target = $routes[$page] ?? $routes['home'];

require $target;


