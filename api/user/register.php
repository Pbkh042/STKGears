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
$confirmPassword = (string)($payload['confirmPassword'] ?? $payload['confirm_password'] ?? $payload['passwordConfirm'] ?? '');

try {
    $user = register_user($username, $password, $confirmPassword);
    json_ok(['user' => ['id' => $user['id'], 'username' => $user['username']]], 201);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 422);
} catch (Throwable $e) {
    json_error('Đăng ký thất bại.', 500);
}



