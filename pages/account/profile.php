<?php
declare(strict_types=1);
// Ghi chú: Trang hồ sơ chung cho user/admin kèm thống kê nhanh.

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/view.php';

require_any_page();

$isAdmin = is_admin_logged_in();
$role = $isAdmin ? 'admin' : 'user';
$username = $isAdmin ? current_admin_name() : current_user_name();

$stats = [
  'valid_orders' => 0,
  'products' => 0,
  'total_stock' => 0,
  'my_orders' => 0,
];

$pdo = db();
if ($isAdmin) {
  $stats['valid_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE customer_name <> '' AND customer_phone <> '' AND customer_address <> ''")->fetchColumn();
  $stats['products'] = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
  $stats['total_stock'] = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products')->fetchColumn();
} else {
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :uid');
  $stmt->execute(['uid' => current_user_id()]);
  $stats['my_orders'] = (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hồ sơ tài khoản | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Hồ sơ tài khoản</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;">
      <img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;">
      <span>STK Gears</span>
    </a>
    <nav class="nav">
      <a href="./index.php?p=home">Trang chủ</a>
      <a href="./index.php?p=products">Sản phẩm</a>
      <a href="./index.php?p=products" data-menu="catalog">Danh mục</a>
      <a href="./index.php?p=policy">Chính sách</a>
    </nav>
    <div class="actions">
      <a id="authLink" class="ghost" href="<?= $isAdmin ? './index.php?p=admin-products' : './index.php?p=user-history' ?>">Xin chào, <?= esc($username) ?></a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </div>
  </header>

  <main class="page-wrap stack">
    <section class="panel">
      <div class="panel-header"><h3>Thông tin tài khoản</h3></div>
      <div class="admin-panel stack">
        <p><strong>Tên tài khoản:</strong> <?= esc($username) ?></p>
        <p><strong>Vai trò:</strong> <?= $role === 'admin' ? 'Admin' : 'Khách hàng' ?></p>

        <?php if ($isAdmin): ?>
          <p><strong>Số đơn hàng hợp lệ:</strong> <?= $stats['valid_orders'] ?></p>
          <p><strong>Số sản phẩm:</strong> <?= $stats['products'] ?></p>
          <p><strong>Tổng tồn kho:</strong> <?= $stats['total_stock'] ?></p>
          <div class="stack" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <a class="btn-secondary" href="./index.php?p=admin-products">Quản trị sản phẩm</a>
            <a class="btn-secondary" href="./index.php?p=admin-orders">Quản trị đơn hàng</a>
            <a class="btn-secondary" href="./index.php?p=logout">Đăng xuất</a>
          </div>
        <?php else: ?>
          <p><strong>Số đơn hàng của bạn:</strong> <?= $stats['my_orders'] ?></p>
          <div class="stack" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <a class="btn-secondary" href="./index.php?p=user-history">Lịch sử đơn hàng</a>
            <a class="btn-secondary" href="./index.php?p=user-password">Đổi mật khẩu</a>
            <a class="btn-secondary" href="./index.php?p=logout">Đăng xuất</a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <script src="./assets/theme.js?v=20261001_1840"></script>
</body>
</html>
