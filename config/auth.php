<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_user_id']);
}
// Hàm kiểm tra admin đã đăng nhập hay chưa.

function is_user_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}
// Hàm kiểm tra user thường đã đăng nhập hay chưa.

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}
// Hàm lấy user id hiện tại từ session để ràng buộc dữ liệu theo tài khoản.

function current_user_name(): string
{
    return (string)($_SESSION['user_username'] ?? '');
}
// Hàm lấy username hiện tại để hiển thị ở khu vực profile/header.

function current_admin_name(): string
{
    return (string)($_SESSION['admin_username'] ?? '');
}
// Hàm lấy admin username hiện tại cho giao diện quản trị.

function require_admin_page(): void
{
    if (!is_admin_logged_in()) {
        header('Location: ./index.php?p=login&tab=admin');
        exit;
    }
}
// Hàm chặn truy cập trang quản trị nếu chưa có phiên admin.

function require_user_page(): void
{
    if (!is_user_logged_in()) {
        header('Location: ./index.php?p=login&tab=user&error=login_required');
        exit;
    }
}
// Hàm chặn truy cập các trang user-only như lịch sử đơn hàng, hồ sơ, checkout.

function is_any_logged_in(): bool
{
    return is_admin_logged_in() || is_user_logged_in();
}
// Hàm kiểm tra tổng quát có đăng nhập hay chưa.

function require_any_page(): void
{
    if (!is_any_logged_in()) {
        header('Location: ./index.php?p=login&tab=user&error=login_required');
        exit;
    }
}
// Hàm chặn truy cập trang cần đăng nhập (user hoặc admin).



