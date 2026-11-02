<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../config/session.php';
app_start_session();

header('Content-Type: application/json; charset=utf-8');

function json_ok(array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
// Hàm trả response thành công theo chuẩn JSON thống nhất.

function json_error(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
// Hàm trả response lỗi theo chuẩn JSON thống nhất.

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('Method not allowed.', 405);
    }
}
// Hàm bắt buộc endpoint chỉ nhận POST.

function require_admin(): void
{
    if (empty($_SESSION['admin_user_id'])) {
        json_error('Unauthorized.', 401);
    }
}
// Hàm bắt buộc người gọi API phải có session admin.

function require_user(): int
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        json_error('Bạn cần đăng nhập trước.', 401);
    }
    return $userId;
}
// Hàm bắt buộc người gọi API phải có session user, trả về user_id.

function parse_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
// Hàm đọc body JSON và trả mảng rỗng nếu dữ liệu không hợp lệ.



