<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/firebase.php';
require_once __DIR__ . '/../../core/order_rules.php';

function create_order_from_cart(int $userId, array $customer, array $items): int
{
    $name = trim((string)($customer['name'] ?? ''));
    $phone = trim((string)($customer['phone'] ?? ''));
    $address = trim((string)($customer['address'] ?? ''));
    $note = trim((string)($customer['note'] ?? ''));
    if ($name === '' || mb_strlen($name) > 120) throw new RuntimeException('Họ tên không hợp lệ.');
    if (!preg_match('/^[0-9+ ]{8,15}$/', $phone)) throw new RuntimeException('Số điện thoại không hợp lệ.');
    if ($address === '' || mb_strlen($address) > 255) throw new RuntimeException('Địa chỉ nhận hàng không hợp lệ.');
    $cart = normalize_order_items($items);
    if (count($cart) > 100) throw new RuntimeException('Giỏ hàng có quá nhiều dòng sản phẩm.');
    $code = generate_order_code();
    return firebase_store_transaction(static function (array &$store) use ($userId, $name, $phone, $address, $note, $cart, $code): int {
        if (($store['users']['u_' . $userId]['role'] ?? '') !== 'user') throw new RuntimeException('Bạn cần đăng nhập trước.');
        $subtotal = 0;
        $resolved = [];
        foreach ($cart as $item) {
            $key = 'p_' . $item['id'];
            $product = $store['products'][$key] ?? null;
            if (!$product) throw new RuntimeException('Có sản phẩm không còn tồn tại.');
            if ((int)$product['stock'] < $item['quantity']) throw new RuntimeException('Sản phẩm "' . $product['name'] . '" đã vượt tồn kho.');
            // Decrement the transaction's working copy for each line, including
            // repeated products with different colours, before the atomic PUT.
            $store['products'][$key]['stock'] -= $item['quantity'];
            $subtotal += (int)$product['price'] * $item['quantity'];
            if ($subtotal > 4294967295) throw new RuntimeException('Giá trị đơn hàng vượt giới hạn.');
            $resolved[] = ['product_id' => (int)$product['id'], 'product_sku' => $product['sku'], 'product_name' => $product['name'],
                'unit_price' => (int)$product['price'], 'quantity' => $item['quantity'], 'variant_color' => $item['color'] !== '' ? $item['color'] : null];
        }
        $id = firebase_next_id($store, 'orders');
        $store['orders']['o_' . $id] = ['id' => $id, 'order_code' => $code, 'user_id' => $userId,
            'customer_name' => $name, 'customer_phone' => $phone, 'customer_address' => $address,
            'customer_note' => $note !== '' ? $note : null, 'subtotal' => $subtotal,
            'status' => ORDER_STATUS_OPTIONS[0], 'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'), 'items' => $resolved];
        return $id;
    });
}

function firebase_order_rows(string $search = '', ?int $userId = null): array
{
    $needle = firebase_normalize_search(trim($search));
    $rows = array_values(array_filter(firebase_store_read()['orders'] ?? [], static function (array $row) use ($needle, $search, $userId): bool {
        if ($userId !== null && (int)$row['user_id'] !== $userId) return false;
        return $needle === '' || (ctype_digit($search) && (int)$row['id'] === (int)$search)
            || str_contains(firebase_normalize_search(implode(' ', [$row['order_code'], $row['customer_name'], $row['customer_phone'], $row['customer_address']])), $needle);
    }));
    usort($rows, static fn(array $a, array $b): int => $b['id'] <=> $a['id']);
    return array_map(static function (array $row): array { unset($row['items']); return $row; }, $rows);
}

function list_orders_user(int $userId): array { return firebase_order_rows('', $userId); }

function get_order_user_detail(int $userId, int $orderId): ?array
{
    $order = firebase_store_read()['orders']['o_' . $orderId] ?? null;
    return $order !== null && (int)$order['user_id'] === $userId ? $order : null;
}

function count_orders_admin(string $search = ''): int { return count(firebase_order_rows($search)); }

function list_orders_admin(string $search = '', int $limit = 10, int $offset = 0): array
{
    return array_slice(firebase_order_rows($search), max(0, $offset), max(1, $limit));
}

function get_order_admin_detail(int $orderId): ?array
{
    return firebase_store_read()['orders']['o_' . $orderId] ?? null;
}

function update_order_status_admin(int $orderId, string $status): void
{
    $status = resolve_order_status($status);
    firebase_store_transaction(static function (array &$store) use ($orderId, $status): void {
        $key = 'o_' . $orderId;
        if (!isset($store['orders'][$key])) throw new RuntimeException('Không tìm thấy đơn hàng.');
        $current = resolve_order_status($store['orders'][$key]['status']);
        if ($current === 'Đã hủy đơn hàng' && $status !== $current) throw new RuntimeException('Đơn đã hủy không thể được xác nhận lại. Vui lòng tải lại trang.');
        if ($current !== ORDER_STATUS_OPTIONS[0] && $status === ORDER_STATUS_OPTIONS[0]) throw new RuntimeException('Không thể đưa đơn đã xử lý về trạng thái chờ xét duyệt.');
        $store['orders'][$key]['status'] = $status;
        $store['orders'][$key]['updated_at'] = gmdate('Y-m-d H:i:s');
    });
}

function can_user_cancel_order(string $status): bool { return resolve_order_status($status) === ORDER_STATUS_OPTIONS[0]; }

function cancel_order_user(int $userId, int $orderId): void
{
    if ($userId <= 0 || $orderId <= 0) throw new RuntimeException('Đơn hàng không hợp lệ.');
    firebase_store_transaction(static function (array &$store) use ($userId, $orderId): void {
        $key = 'o_' . $orderId;
        $order = $store['orders'][$key] ?? null;
        if (!$order || (int)$order['user_id'] !== $userId) throw new RuntimeException('Không tìm thấy đơn hàng của bạn.');
        if (!can_user_cancel_order($order['status'])) throw new RuntimeException('Chỉ có thể hủy đơn đang chờ xét duyệt. Đơn đã được xử lý hoặc đã hủy.');
        foreach ($order['items'] ?? [] as $item) {
            $productKey = 'p_' . $item['product_id'];
            if (!isset($store['products'][$productKey])) throw new RuntimeException('Không thể hoàn tồn kho. Vui lòng liên hệ cửa hàng.');
            $store['products'][$productKey]['stock'] += (int)$item['quantity'];
        }
        $store['orders'][$key]['status'] = 'Đã hủy đơn hàng';
        $store['orders'][$key]['updated_at'] = gmdate('Y-m-d H:i:s');
    });
}

function delete_order_admin(int $orderId): void
{
    firebase_store_transaction(static function (array &$store) use ($orderId): void { unset($store['orders']['o_' . $orderId]); });
}

function account_statistics(bool $admin, int $userId): array
{
    $store = firebase_store_read();
    $stats = ['valid_orders' => 0, 'products' => 0, 'total_stock' => 0, 'my_orders' => 0];
    if ($admin) {
        $stats['products'] = count($store['products'] ?? []);
        foreach ($store['products'] ?? [] as $product) $stats['total_stock'] += (int)$product['stock'];
        foreach ($store['orders'] ?? [] as $order) {
            if ($order['customer_name'] !== '' && $order['customer_phone'] !== '' && $order['customer_address'] !== '') $stats['valid_orders']++;
        }
    } else {
        foreach ($store['orders'] ?? [] as $order) if ((int)$order['user_id'] === $userId) $stats['my_orders']++;
    }
    return $stats;
}
