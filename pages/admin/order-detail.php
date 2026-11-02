<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/order_service.php';

require_admin_page();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ./index.php?p=admin-orders');
    exit;
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_order'])) {
            delete_order_admin($orderId);
            header('Location: ./index.php?p=admin-orders');
            exit;
        }

        $status = trim((string)($_POST['status'] ?? ''));
        update_order_status_admin($orderId, $status);
        $message = 'Đã cập nhật trạng thái đơn hàng.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$order = get_order_admin_detail($orderId);
if (!$order) {
    header('Location: ./index.php?p=admin-orders');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chi tiết đơn #<?= (int)$order['id'] ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Chi tiết đơn hàng #<?= (int)$order['id'] ?></p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;"><img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;"><span>STK Gears</span></a>
    <nav class="nav">
      <a href="./index.php?p=admin-products">Sản phẩm</a>
      <a href="./index.php?p=admin-orders">Đơn hàng</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </nav>
  </header>

  <main class="page-wrap stack">
    <section class="panel">
      <div class="panel-header"><h3>Thông tin đơn hàng</h3></div>
      <div class="admin-panel stack">
        <?php if ($message !== ''): ?><p style="color:#018965"><?= esc($message) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p style="color:#b51d45"><?= esc($error) ?></p><?php endif; ?>

        <p><strong>Mã đơn:</strong> <?= esc((string)$order['order_code']) ?></p>
        <p><strong>Khách hàng:</strong> <?= esc((string)$order['customer_name']) ?></p>
        <p><strong>SĐT:</strong> <?= esc((string)$order['customer_phone']) ?></p>
        <p><strong>Địa chỉ:</strong> <?= esc((string)$order['customer_address']) ?></p>
        <p><strong>Ghi chú:</strong> <?= esc((string)($order['customer_note'] ?? '')) ?></p>
        <p><strong>Tổng tiền:</strong> <?= vnd((int)$order['subtotal']) ?></p>

        <?php $orderCancelled = resolve_order_status((string)$order['status']) === 'Đã hủy đơn hàng'; ?>
        <?php if ($orderCancelled): ?>
          <p class="muted">Đơn đã hủy không thể được xác nhận lại.</p>
        <?php endif; ?>
        <form method="post" class="checkout-form" style="max-width:420px;">
          <label>Trạng thái đơn</label>
          <select name="status" required <?= $orderCancelled ? 'disabled' : '' ?>>
            <?php foreach (ORDER_STATUS_OPTIONS as $status): ?>
              <?php if ($status === 'Đang xét duyệt đơn hàng' && !can_user_cancel_order((string)$order['status'])) continue; ?>
              <option value="<?= esc($status) ?>" <?= $status === $order['status'] ? 'selected' : '' ?>><?= esc($status) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-primary full" <?= $orderCancelled ? 'disabled' : '' ?>>Cập nhật trạng thái</button>
        </form>

        <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn loại bỏ đơn này?')">
          <input type="hidden" name="delete_order" value="1">
          <button class="danger-btn" type="submit">Loại bỏ đơn</button>
        </form>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header"><h3>Chi tiết sản phẩm trong đơn</h3></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Sản phẩm</th><th>ID Sản phẩm</th><th>Màu</th><th>Số lượng</th><th>Đơn giá</th></tr></thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
              <td><?= esc((string)$item['product_name']) ?></td>
              <td><?= esc((string)$item['product_sku']) ?></td>
              <td><?= esc((string)($item['variant_color'] ?: '-')) ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= vnd((int)$item['unit_price']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <script src="./assets/theme.js?v=20261001_1840"></script>
</body>
</html>


