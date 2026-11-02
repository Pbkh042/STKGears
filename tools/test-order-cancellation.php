<?php
declare(strict_types=1);

// CLI integration test. Connection-scoped TEMPORARY tables shadow real tables.
// No production orders, products or stock are modified.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../services/order_service.php';
$pdo = db();
foreach ([
    'CREATE TEMPORARY TABLE orders (id INT PRIMARY KEY, user_id INT NOT NULL, status VARCHAR(100) NOT NULL) ENGINE=InnoDB',
    'CREATE TEMPORARY TABLE products (id INT PRIMARY KEY, stock INT NOT NULL) ENGINE=InnoDB',
    'CREATE TEMPORARY TABLE order_items (order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL) ENGINE=InnoDB',
] as $sql) {
    $pdo->exec($sql);
}
function check(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
}
function expect_rejected(callable $action, string $label): void {
    try {
        $action();
    } catch (RuntimeException $error) {
        check(!($error instanceof PDOException), $label . ': must be a business rejection');
        check(!db()->inTransaction(), $label . ': transaction released');
        return;
    }
    throw new RuntimeException('FAIL: ' . $label . ' was accepted');
}
function stock(int $id): int {
    return (int)db()->query('SELECT stock FROM products WHERE id = ' . $id)->fetchColumn();
}
function status(int $id): string {
    return (string)db()->query('SELECT status FROM orders WHERE id = ' . $id)->fetchColumn();
}
function pending(int $id): void {
    $stmt = db()->prepare('INSERT INTO orders (id, user_id, status) VALUES (?, 7, ?)');
    $stmt->execute([$id, 'Đang xét duyệt đơn hàng']);
}
$pdo->exec('INSERT INTO products VALUES (1, 10), (2, 20)');
pending(101);
$pdo->exec('INSERT INTO order_items VALUES (101, 1, 2), (101, 1, 3), (101, 2, 4)');
expect_rejected(fn() => cancel_order_user(8, 101), 'another customer');
expect_rejected(fn() => cancel_order_user(0, 101), 'invalid user');
expect_rejected(fn() => cancel_order_user(7, 999), 'missing order');
check(stock(1) === 10 && stock(2) === 20, 'rejected requests do not restore stock');
cancel_order_user(7, 101);
check(status(101) === 'Đã hủy đơn hàng', 'cancel status');
check(stock(1) === 15 && stock(2) === 24, 'restore all quantities including repeated product');
expect_rejected(fn() => cancel_order_user(7, 101), 'repeat cancellation');
check(stock(1) === 15 && stock(2) === 24, 'stock restored once');
expect_rejected(fn() => update_order_status_admin(101, 'Đã đặt hàng'), 'stale admin confirmation after cancellation');
update_order_status_admin(101, 'Đã hủy đơn hàng');
check(stock(1) === 15 && stock(2) === 24, 'admin refresh does not restore twice');

pending(102);
update_order_status_admin(102, 'Đã đặt hàng');
expect_rejected(fn() => cancel_order_user(7, 102), 'admin confirmation wins');
expect_rejected(fn() => update_order_status_admin(102, 'Đang xét duyệt đơn hàng'), 'confirmed order cannot become user-cancellable again');
check(status(102) === 'Đã đặt hàng', 'confirmed status remains');

foreach (['Đang vận chuyển', 'Đã vận chuyển', 'Đơn hàng bị từ chối'] as $index => $state) {
    $id = 110 + $index;
    pending($id);
    update_order_status_admin($id, $state);
    expect_rejected(fn() => cancel_order_user(7, $id), $state);
}
pending(120);
$pdo->exec('INSERT INTO order_items VALUES (120, 1, 2), (120, 999, 1)');
expect_rejected(fn() => cancel_order_user(7, 120), 'restoration failure');
check(status(120) === 'Đang xét duyệt đơn hàng', 'rollback restores pending status');
check(stock(1) === 15 && stock(2) === 24, 'rollback undoes partial stock restoration');
check(!db()->inTransaction(), 'no leaked transaction');
echo "PASS: ownership, pending-only cancellation, full stock restoration, repeat requests, both confirmation/cancellation orderings, terminal status guard and rollback.\n";
