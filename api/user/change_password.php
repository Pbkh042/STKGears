<?php

declare(strict_types=1);
// Ghi chú: API đổi mật khẩu cho user đã đăng nhập.

require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../../services/auth_service.php';

require_post();
$userId = require_user();

$body = parse_json_body();
foreach (['currentPassword', 'newPassword', 'confirmPassword'] as $field) {
    if (isset($body[$field]) && !is_string($body[$field])) {
        json_error('Dữ liệu mật khẩu không hợp lệ.', 422);
    }
}
$currentPassword = (string)($body['currentPassword'] ?? '');
$newPassword = (string)($body['newPassword'] ?? '');
$confirmPassword = (string)($body['confirmPassword'] ?? '');

try {
    change_user_password($userId, $currentPassword, $newPassword, $confirmPassword);
    json_ok(['message' => 'Đổi mật khẩu thành công.']);
} catch (PDOException $e) {
    json_error('Không thể lưu mật khẩu mới. Vui lòng thử lại sau.', 500);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 422);
} catch (Throwable $e) {
    json_error('Không thể đổi mật khẩu. Vui lòng thử lại sau.', 500);
}
