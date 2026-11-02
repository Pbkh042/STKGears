<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/product_service.php';

require_admin_page();

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('ID sản phẩm không hợp lệ.');
        }
        delete_product($id);
        $message = 'Đã xoá sản phẩm.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$total = count_products_admin($search);
$totalPages = max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $pageSize;
}

$products = list_products_admin($search, $pageSize, $offset);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản trị sản phẩm | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Admin Panel - STK Gears</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;"><img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;"><span>STK Gears</span></a>
    <nav class="nav">
      <a href="./index.php?p=home">Trang chủ</a>
      <a href="./index.php?p=admin-add-product">Thêm sản phẩm</a>
      <a href="./index.php?p=admin-orders">Đơn hàng</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </nav>
  </header>

  <main class="page-wrap stack">
    <section class="panel">
      <div class="panel-header"><h3>Danh sách sản phẩm</h3></div>
      <div class="admin-panel stack">
        <?php if ($message !== ''): ?><p style="color:#018965;"><?= esc($message) ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p style="color:#b51d45;"><?= esc($error) ?></p><?php endif; ?>

        <form method="get" class="toolbar" style="grid-template-columns:1fr auto;">
          <input type="hidden" name="p" value="admin-products">
          <input type="search" name="search" value="<?= esc($search) ?>" placeholder="Tìm theo SKU, tên, danh mục, thương hiệu...">
          <button type="submit" class="btn-primary">Tìm</button>
        </form>

        <div class="table-wrap">
          <table class="table admin-products-table">
            <colgroup>
              <col class="col-id">
              <col class="col-sku">
              <col class="col-name">
              <col class="col-cat">
              <col class="col-price">
              <col class="col-stock">
              <col class="col-actions">
            </colgroup>
            <thead>
              <tr>
                <th>ID</th>
                <th>ID Sản phẩm</th>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Tồn kho</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= esc((string)$row['sku']) ?></td>
                <td><a class="product-link" href="./index.php?p=product&id=<?= (int)$row['id'] ?>"><?= esc((string)$row['name']) ?></a></td>
                <td><?= esc(category_label((string)$row['category'])) ?></td>
                <td><?= vnd((int)$row['price']) ?></td>
                <td><?= (int)$row['stock'] ?></td>
                <td class="actions-cell">
                  <div class="product-actions-cell">
                    <a class="product-link admin-link" href="./index.php?p=admin-edit-product&id=<?= (int)$row['id'] ?>">Sửa</a>
                    <button type="button" class="danger-btn js-open-delete" data-id="<?= (int)$row['id'] ?>" data-name="<?= esc((string)$row['name']) ?>">Xoá</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($products) === 0): ?>
              <tr><td colspan="7" class="muted">Không có sản phẩm phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="pagination-btn <?= $i === $page ? 'active' : '' ?>" href="?p=admin-products&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <div id="deleteModal" class="modal" aria-hidden="true">
    <div class="modal-body">
      <div class="cart-header">
        <h3>Xác nhận xoá sản phẩm</h3>
        <button id="closeDeleteModalBtn" class="ghost" type="button">Đóng</button>
      </div>
      <div class="admin-panel stack">
        <p id="deleteModalText" class="muted"></p>
        <form id="deleteProductForm" method="post">
          <input type="hidden" name="id" id="deleteProductId" value="">
          <button type="submit" class="danger-btn">Xoá sản phẩm</button>
        </form>
      </div>
    </div>
  </div>
  <script src="./assets/theme.js?v=20261001_1840"></script>
  <script src="./assets/admin-products.js?v=20260516_1538"></script>
</body>
</html>


