<?php

declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.

require_once __DIR__ . '/../../config/database.php';

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

function create_order_from_cart(int $userId, array $customer, array $items): int
{
    $name = trim((string)($customer['name'] ?? ''));
    $phone = trim((string)($customer['phone'] ?? ''));
    $address = trim((string)($customer['address'] ?? ''));
    $note = trim((string)($customer['note'] ?? ''));

    if ($name === '' || mb_strlen($name) > 120) {
        throw new RuntimeException('Họ tên không hợp lệ.');
    }
    if (!preg_match('/^[0-9+ ]{8,15}$/', $phone)) {
        throw new RuntimeException('Số điện thoại không hợp lệ.');
    }
    if ($address === '' || mb_strlen($address) > 255) {
        throw new RuntimeException('Địa chỉ nhận hàng không hợp lệ.');
    }

    $cartItems = normalize_order_items($items);
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $subtotal = 0;
        $resolvedItems = [];

        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare('SELECT id, sku, name, price, stock FROM products WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $item['id']]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new RuntimeException('Có sản phẩm không còn tồn tại.');
            }
            if ((int)$product['stock'] < $item['quantity']) {
                throw new RuntimeException('Sản phẩm "' . (string)$product['name'] . '" đã vượt tồn kho.');
            }

            $line = (int)$product['price'] * $item['quantity'];
            $subtotal += $line;

            $resolvedItems[] = [
                'product_id' => (int)$product['id'],
                'product_sku' => (string)$product['sku'],
                'product_name' => (string)$product['name'],
                'unit_price' => (int)$product['price'],
                'quantity' => $item['quantity'],
                'color' => $item['color'] !== '' ? $item['color'] : null,
            ];
        }

        $orderCode = generate_order_code();
        $orderStmt = $pdo->prepare(
            'INSERT INTO orders (order_code, user_id, customer_name, customer_phone, customer_address, customer_note, subtotal, status)
             VALUES (:order_code, :user_id, :customer_name, :customer_phone, :customer_address, :customer_note, :subtotal, :status)'
        );
        $orderStmt->execute([
            'order_code' => $orderCode,
            'user_id' => $userId,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'customer_note' => $note !== '' ? $note : null,
            'subtotal' => $subtotal,
            'status' => 'Đang xét duyệt đơn hàng',
        ]);

        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_sku, product_name, variant_color, unit_price, quantity)
             VALUES (:order_id, :product_id, :product_sku, :product_name, :variant_color, :unit_price, :quantity)'
        );
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');

        foreach ($resolvedItems as $line) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $line['product_id'],
                'product_sku' => $line['product_sku'],
                'product_name' => $line['product_name'],
                'variant_color' => $line['color'],
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
            ]);

            $stockStmt->execute([
                'qty' => $line['quantity'],
                'id' => $line['product_id'],
            ]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
// Hàm tạo đơn hàng chuẩn: validate, lock tồn kho, insert order + items, trừ kho theo transaction.

function list_orders_user(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT id, order_code, customer_name, customer_phone, customer_address, subtotal, status, created_at
         FROM orders
         WHERE user_id = :user_id
         ORDER BY id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}
// Hàm lấy lịch sử đơn hàng cho user (chỉ đơn của chính họ).

function get_order_user_detail(int $userId, int $orderId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, order_code, customer_name, customer_phone, customer_address, customer_note, subtotal, status, created_at
         FROM orders
         WHERE user_id = :user_id AND id = :id
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId, 'id' => $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $itemStmt = db()->prepare(
        'SELECT product_id, product_sku, product_name, variant_color, unit_price, quantity
         FROM order_items
         WHERE order_id = :order_id
         ORDER BY id ASC'
    );
    $itemStmt->execute(['order_id' => $orderId]);
    $order['items'] = $itemStmt->fetchAll();

    return $order;
}
// Hàm lấy chi tiết đơn cho user với danh sách sản phẩm trong đơn.

function count_orders_admin(string $search = ''): int
{
    $sql = 'SELECT COUNT(*) FROM orders';
    $params = [];
    if ($search !== '') {
        $sql .= ' WHERE (order_code LIKE :search_code OR customer_name LIKE :search_name OR customer_phone LIKE :search_phone OR customer_address LIKE :search_address';
        if (ctype_digit($search)) {
            $sql .= ' OR id = :order_id';
            $params['order_id'] = (int)$search;
        }
        $sql .= ')';
        $like = '%' . $search . '%';
        $params['search_code'] = $like;
        $params['search_name'] = $like;
        $params['search_phone'] = $like;
        $params['search_address'] = $like;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
// Hàm đếm tổng đơn hợp lệ cho dashboard admin.

function list_orders_admin(string $search = '', int $limit = 10, int $offset = 0): array
{
    $sql = 'SELECT id, order_code, user_id, customer_name, customer_phone, customer_address, subtotal, status, created_at
            FROM orders';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE (order_code LIKE :search_code OR customer_name LIKE :search_name OR customer_phone LIKE :search_phone OR customer_address LIKE :search_address';
        if (ctype_digit($search)) {
            $sql .= ' OR id = :order_id';
            $params['order_id'] = (int)$search;
        }
        $sql .= ')';
        $like = '%' . $search . '%';
        $params['search_code'] = $like;
        $params['search_name'] = $like;
        $params['search_phone'] = $like;
        $params['search_address'] = $like;
    }

    $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

    $stmt = db()->prepare($sql);
    if (isset($params['search_code'])) {
        $stmt->bindValue(':search_code', $params['search_code'], PDO::PARAM_STR);
        $stmt->bindValue(':search_name', $params['search_name'], PDO::PARAM_STR);
        $stmt->bindValue(':search_phone', $params['search_phone'], PDO::PARAM_STR);
        $stmt->bindValue(':search_address', $params['search_address'], PDO::PARAM_STR);
    }
    if (isset($params['order_id'])) {
        $stmt->bindValue(':order_id', $params['order_id'], PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
// Hàm lấy danh sách đơn cho admin với live search toàn DB và phân trang.

function get_order_admin_detail(int $orderId): ?array
{
    $stmt = db()->prepare(
        'SELECT id, order_code, user_id, customer_name, customer_phone, customer_address, customer_note, subtotal, status, created_at
         FROM orders
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $itemStmt = db()->prepare(
        'SELECT product_id, product_sku, product_name, variant_color, unit_price, quantity
         FROM order_items
         WHERE order_id = :order_id
         ORDER BY id ASC'
    );
    $itemStmt->execute(['order_id' => $orderId]);
    $order['items'] = $itemStmt->fetchAll();

    return $order;
}
// Hàm lấy chi tiết đơn cho admin, dùng ở trang "Chi tiết" để xử lý toàn bộ CRUD trạng thái.

function update_order_status_admin(int $orderId, string $status): void
{
    $status = resolve_order_status($status);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }
        $current = resolve_order_status((string)$order['status']);
        if ($current === 'Đã hủy đơn hàng' && $status !== $current) {
            throw new RuntimeException('Đơn đã hủy không thể được xác nhận lại. Vui lòng tải lại trang.');
        }
        if ($current !== 'Đang xét duyệt đơn hàng' && $status === 'Đang xét duyệt đơn hàng') {
            throw new RuntimeException('Không thể đưa đơn đã xử lý về trạng thái chờ xét duyệt.');
        }
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $orderId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
// Hàm cập nhật trạng thái đơn theo danh sách trạng thái hợp lệ.

function can_user_cancel_order(string $status): bool
{
    return resolve_order_status($status) === 'Đang xét duyệt đơn hàng';
}

function cancel_order_user(int $userId, int $orderId): void
{
    if ($userId <= 0 || $orderId <= 0) {
        throw new RuntimeException('Đơn hàng không hợp lệ.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Serialize cancellation with admin confirmation and repeated cancel requests.
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id AND user_id = :user_id FOR UPDATE');
        $stmt->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Không tìm thấy đơn hàng của bạn.');
        }
        if (!can_user_cancel_order((string)$order['status'])) {
            throw new RuntimeException('Chỉ có thể hủy đơn đang chờ xét duyệt. Đơn đã được xử lý hoặc đã hủy.');
        }

        $items = $pdo->prepare('SELECT product_id, SUM(quantity) AS quantity FROM order_items WHERE order_id = :id GROUP BY product_id ORDER BY product_id');
        $items->execute(['id' => $orderId]);
        $restore = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id');
        foreach ($items->fetchAll() as $item) {
            $restore->execute(['quantity' => (int)$item['quantity'], 'id' => (int)$item['product_id']]);
            if ($restore->rowCount() !== 1) {
                throw new RuntimeException('Không thể hoàn tồn kho. Vui lòng liên hệ cửa hàng.');
            }
        }
        $update = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $update->execute(['status' => 'Đã hủy đơn hàng', 'id' => $orderId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function delete_order_admin(int $orderId): void
{
    $stmt = db()->prepare('DELETE FROM orders WHERE id = :id');
    $stmt->execute(['id' => $orderId]);
}

function account_statistics(bool $admin, int $userId): array
{
    $pdo = db();
    $stats = ['valid_orders' => 0, 'products' => 0, 'total_stock' => 0, 'my_orders' => 0];
    if ($admin) {
        $stats['valid_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE customer_name <> '' AND customer_phone <> '' AND customer_address <> ''")->fetchColumn();
        $stats['products'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $stats['total_stock'] = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products')->fetchColumn();
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $statement->execute([$userId]);
        $stats['my_orders'] = (int)$statement->fetchColumn();
    }
    return $stats;
}
// Hàm xóa đơn khỏi hệ thống theo quyền admin.




