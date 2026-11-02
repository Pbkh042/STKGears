<?php

declare(strict_types=1);

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/auth_service.php';

require_post();
$body = parse_json_body();
$payload = array_merge($_POST, $body);
$username = trim((string)($payload['username'] ?? $payload['user'] ?? $payload['email'] ?? ''));
$password = (string)($payload['password'] ?? $payload['pass'] ?? '');

try {
    $account = login_account($username, $password);
    json_ok($account);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 401);
} catch (Throwable $e) {
    json_error('Đăng nhập thất bại.', 500);
}
