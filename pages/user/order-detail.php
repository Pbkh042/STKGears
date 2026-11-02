<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/order_service.php';
require_once __DIR__ . '/../../core/order_cancel.php';

require_user_page();
$userId = current_user_id();
$username = current_user_name();
$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ./index.php?p=user-history');
    exit;
}
$error = '';
$message = (string)($_SESSION['order_cancel_success'][$orderId] ?? '');
unset($_SESSION['order_cancel_success'][$orderId]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(order_cancel_token(), $token)) {
        http_response_code(403);
        $error = 'Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.';
    } elseif (($_POST['action'] ?? '') === 'cancel_order') {
        try {
            cancel_order_user($userId, $orderId);
            $_SESSION['order_cancel_success'][$orderId] = 'Đã hủy đơn hàng thành công.';
            header('Location: ./index.php?p=user-order-detail&id=' . $orderId, true, 303);
            exit;
        } catch (PDOException $e) {
            $error = 'Không thể hủy đơn lúc này. Vui lòng thử lại.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
$order = get_order_user_detail($userId, $orderId);
if (!$order) {
    header('Location: ./index.php?p=user-history');
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
  <div class="topbar"><p>Chi tiết đơn hàng | <?= esc($username) ?></p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;">
      <img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;">
      <span>STK Gears</span>
    </a>
    <nav class="nav">
      <a href="./index.php?p=home">Trang chủ</a>
      <a href="./index.php?p=products">Sản phẩm</a>
      <a href="./index.php?p=products" data-menu="catalog">Danh mục</a>
      <a href="./index.php?p=user-history">Lịch sử</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </nav>
  </header>

  <main class="page-wrap stack">
    <section class="panel">
      <div class="panel-header"><h3>Đơn #<?= (int)$order['id'] ?> - <?= esc((string)$order['order_code']) ?></h3></div>
      <div class="admin-panel stack">
        <?php if ($message !== ''): ?><p class="status in" role="status"><?= esc($message) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="inline-error" role="alert"><?= esc($error) ?></p><?php endif; ?>
        <p><strong>Khách hàng:</strong> <?= esc((string)$order['customer_name']) ?></p>
        <p><strong>SĐT:</strong> <?= esc((string)$order['customer_phone']) ?></p>
        <p><strong>Địa chỉ:</strong> <?= esc((string)$order['customer_address']) ?></p>
        <p><strong>Ghi chú:</strong> <?= esc((string)($order['customer_note'] ?? '')) ?></p>
        <p><strong>Trạng thái:</strong> <?= esc((string)$order['status']) ?></p>
        <p><strong>Tổng tiền:</strong> <?= vnd((int)$order['subtotal']) ?></p>
        <?php if (can_user_cancel_order((string)$order['status'])): ?>
          <p class="muted">Bạn có thể hủy đơn trước khi cửa hàng xác nhận.</p>
          <?php render_order_cancel_form($orderId); ?>
        <?php endif; ?>
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


