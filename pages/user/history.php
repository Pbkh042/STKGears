<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/order_service.php';
require_once __DIR__ . '/../../core/order_cancel.php';

require_user_page();
$userId = current_user_id();
$username = current_user_name();
$orders = list_orders_user($userId);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch sử đơn hàng | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body>
  <div class="topbar"><p>Xin chào, <?= esc($username) ?> | Lịch sử đơn hàng</p></div>
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

  <main class="page-wrap stack order-history-page">
    <section class="panel">
      <div class="panel-header"><h3>Lịch sử đơn hàng</h3></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>ID</th><th>Mã đơn</th><th>Ngày</th><th>Trạng thái</th><th>Tổng tiền</th><th>Thao tác</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= (int)$o['id'] ?></td>
              <td><?= esc((string)$o['order_code']) ?></td>
              <td><?= esc((string)$o['created_at']) ?></td>
              <td><?= esc((string)$o['status']) ?></td>
              <td><?= vnd((int)$o['subtotal']) ?></td>
              <td>
                <div class="order-history-actions">
                  <a class="product-link admin-link" href="./index.php?p=user-order-detail&id=<?= (int)$o['id'] ?>">Chi tiết</a>
                  <?php if (can_user_cancel_order((string)$o['status'])) render_order_cancel_form((int)$o['id']); ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($orders) === 0): ?>
            <tr><td colspan="6" class="muted">Bạn chưa có đơn hàng nào.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
</body>
</html>



