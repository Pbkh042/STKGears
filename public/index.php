<?php
declare(strict_types=1);

// The container exposes only this directory and static assets. Application
// source, configuration and SQL remain outside the HTTP document root.
require_once __DIR__ . '/../config/app.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$apiRoutes = [
    '/api/admin/login.php',
    '/api/admin/logout.php',
    '/api/admin/me.php',
    '/api/admin/orders/delete.php',
    '/api/admin/orders/detail.php',
    '/api/admin/orders/list.php',
    '/api/admin/orders/update-status.php',
    '/api/admin/products/create.php',
    '/api/admin/products/delete.php',
    '/api/admin/products/list.php',
    '/api/admin/products/update.php',
    '/api/auth/login.php',
    '/api/orders/create.php',
    '/api/orders/detail.php',
    '/api/orders/history.php',
    '/api/products/list.php',
    '/api/user/change_password.php',
    '/api/user/login.php',
    '/api/user/me.php',
    '/api/user/register.php',
];

header('X-Content-Type-Options: nosniff');

if ($path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['status' => 'ok', 'databaseConfigured' => database_is_configured()]);
    return;
}

$isApi = in_array($path, $apiRoutes, true);
if (!$isApi && $path !== '/' && $path !== '/index.php') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    return;
}

// PHP responses can contain account/order information. Do not cache them.
header('Cache-Control: private, no-store');

$sessionOnlyRoutes = ['/api/admin/me.php', '/api/user/me.php', '/api/admin/logout.php'];
if ($isApi && !database_is_configured() && !in_array($path, $sessionOnlyRoutes, true)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'code' => 'DATABASE_NOT_CONFIGURED',
        'message' => 'Dịch vụ tạm thời chưa sẵn sàng. Vui lòng thử lại sau.',
    ], JSON_UNESCAPED_UNICODE);
    return;
}

ob_start();
try {
    if ($isApi) {
        require __DIR__ . '/..' . $path;
    } else {
        require __DIR__ . '/../index.php';
    }
    ob_end_flush();
} catch (Throwable $error) {
    ob_end_clean();
    error_log('STKGears request failed: ' . get_class($error));
    http_response_code($error instanceof PDOException || !database_is_configured() ? 503 : 500);
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Dịch vụ tạm thời chưa sẵn sàng.'], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>STK Gears</title><h1>Dịch vụ tạm thời chưa sẵn sàng</h1><p>Vui lòng quay lại sau.</p><a href="/">Về trang chủ</a></html>';
    }
}
