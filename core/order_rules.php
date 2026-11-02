<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.



const ORDER_STATUS_OPTIONS = [
    'Đang xét duyệt đơn hàng',
    'Đã đặt hàng',
    'Đang vận chuyển',
    'Đã vận chuyển',
    'Đơn hàng bị từ chối',
    'Đã hủy đơn hàng',
];

function normalize_status_text(string $value): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    return mb_strtolower($value, 'UTF-8');
}
// Hàm chuẩn hóa chuỗi trạng thái để chống sai khác khoảng trắng/mã hóa khi so sánh.

function resolve_order_status(string $status): string
{
    $normalizedInput = normalize_status_text($status);
    $aliases = [
        'reviewing' => 'Đang xét duyệt đơn hàng',
        'placed' => 'Đã đặt hàng',
        'shipping' => 'Đang vận chuyển',
        'delivered' => 'Đã vận chuyển',
        'rejected' => 'Đơn hàng bị từ chối',
        'cancelled' => 'Đã hủy đơn hàng',
        'canceled' => 'Đã hủy đơn hàng',
    ];
    if (isset($aliases[$normalizedInput])) {
        return $aliases[$normalizedInput];
    }

    foreach (ORDER_STATUS_OPTIONS as $option) {
        if (normalize_status_text($option) === $normalizedInput) {
            return $option;
        }
    }

    throw new RuntimeException('Trạng thái đơn hàng không hợp lệ.');
}
// Hàm ánh xạ trạng thái đầu vào về trạng thái hợp lệ trong hệ thống.

function generate_order_code(): string
{
    return 'DH' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}
// Hàm tạo mã đơn để admin/user tra cứu nhanh khi số lượng đơn lớn.

function normalize_order_items(array $items): array
{
    if (count($items) === 0) {
        throw new RuntimeException('Giỏ hàng trống.');
    }

    $normalized = [];
    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $color = trim((string)($item['color'] ?? ''));
        if ($productId <= 0 || $quantity <= 0) {
            throw new RuntimeException('Dữ liệu sản phẩm trong giỏ không hợp lệ.');
        }
        $normalized[] = ['id' => $productId, 'quantity' => $quantity, 'color' => $color];
    }

    return $normalized;
}
// Hàm chuẩn hóa danh sách item checkout trước khi trừ tồn kho và tạo đơn.

