<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/auth_service.php';

require_post();
$body = parse_json_body();
$payload = array_merge($_POST, $body);
$username = trim((string)($payload['username'] ?? $payload['user'] ?? $payload['email'] ?? ''));
$password = (string)($payload['password'] ?? $payload['pass'] ?? '');

if ($username === '' || $password === '') {
    json_error('Username and password are required.', 422);
}

try {
    $user = login_admin($username, $password);
    json_ok(['user' => ['username' => $user['username']]]);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 401);
} catch (Throwable $e) {
    json_error('Login failed.', 500);
}



