<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
// Hàm escape dữ liệu trước khi render HTML để tránh XSS.

function vnd(int $amount): string
{
    return number_format($amount, 0, ',', '.') . 'đ';
}
// Hàm định dạng số tiền theo kiểu VND hiển thị cho người dùng.

function discount_badge(int $price, ?int $oldPrice): string
{
    if ($price < 0 || $oldPrice === null || $oldPrice <= 0 || $oldPrice <= $price) {
        return '';
    }
    $percent = (int)round(($oldPrice - $price) / $oldPrice * 100);
    $label = $percent > 0 ? (string)$percent : '&lt;1';
    return '<span class="discount-badge">Giảm ' . $label . '%</span>';
}



