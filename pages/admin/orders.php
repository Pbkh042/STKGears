<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/order_service.php';

require_admin_page();

$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$total = count_orders_admin($search);
$totalPages = max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $pageSize;
}
$orders = list_orders_admin($search, $pageSize, $offset);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đơn hàng | Quản trị STK Gear</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body>
  <div class="topbar"><p>Quản trị đơn hàng khách</p></div>
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
      <div class="panel-header"><h3>Danh sách đơn hàng khách</h3></div>
      <div class="admin-panel stack">
        <p><strong>Tổng số đơn hợp lệ:</strong> <?= $total ?></p>
        <form method="get" class="toolbar" style="grid-template-columns:1fr auto;">
          <input type="hidden" name="p" value="admin-orders">
          <input type="search" name="search" value="<?= esc($search) ?>" placeholder="Tìm theo ID đơn, tên khách, SĐT, địa chỉ...">
          <button class="btn-primary" type="submit">Tìm</button>
        </form>

        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Liên hệ</th>
                <th>Địa chỉ</th>
                <th>Tổng</th>
                <th>Trạng thái</th>
                <th>Tạo lúc</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= esc((string)$o['order_code']) ?></td>
                <td><?= esc((string)$o['customer_name']) ?></td>
                <td><?= esc((string)$o['customer_phone']) ?></td>
                <td><?= esc((string)$o['customer_address']) ?></td>
                <td><?= vnd((int)$o['subtotal']) ?></td>
                <td><?= esc((string)$o['status']) ?></td>
                <td><?= esc((string)$o['created_at']) ?></td>
                <td><a class="product-link admin-link" href="./index.php?p=admin-order-detail&id=<?= (int)$o['id'] ?>">Chi tiết</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($orders) === 0): ?>
              <tr><td colspan="9" class="muted">Chưa có đơn phù hợp.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a class="pagination-btn <?= $i === $page ? 'active' : '' ?>" href="?p=admin-orders&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
</body>
</html>



