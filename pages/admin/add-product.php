<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';

require_admin_page();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thêm sản phẩm | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Thêm sản phẩm mới - Chỉ dành cho admin</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;"><img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;"><span>STK Gears</span></a>
    <nav class="nav">
      <a href="./index.php?p=admin-products">Sản phẩm</a>
      <a href="./index.php?p=admin-orders">Đơn hàng</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </nav>
  </header>
  <main class="page-wrap">
    <section class="panel">
      <div class="panel-header"><h2>Tạo sản phẩm mới</h2></div>
      <form id="createProductForm" class="checkout-form">
        <input name="name" placeholder="Tên sản phẩm" required maxlength="180">
        <input name="brand" placeholder="Thương hiệu" required maxlength="80" value="STK Gears">
        <select name="category" required>
          <option value="keyboard_mech">Bàn phím cơ</option>
          <option value="keyboard_hall">Bàn phím Hall Effect</option>
          <option value="mouse_wireless">Chuột không dây</option>
          <option value="mouse_wired">Chuột có dây</option>
          <option value="pad_mouse">Pad chuột</option>
          <option value="accessory_switch">Switch</option>
          <option value="accessory_keycap">Keycap</option>
        </select>
        <input name="price" type="number" min="1000" step="1000" placeholder="Giá bán (VND)" required>
        <input name="oldPrice" type="number" min="0" step="1000" placeholder="Giá gốc (nếu có)">
        <input name="stock" type="number" min="0" step="1" placeholder="Tồn kho" required>
        <input name="image" type="url" placeholder="Link ảnh chính (https://...)" required maxlength="500">
        <textarea name="gallery" placeholder="Ảnh phụ: mỗi dòng 1 link (https://...)"></textarea>
        <input name="colors" type="text" placeholder="Màu sắc (VD: Đen,Trắng,Xanh lá)">
        <textarea class="product-desc-input" name="description" placeholder="Mô tả sản phẩm"></textarea>
        <textarea class="product-spec-input" name="technicalSpecs" placeholder="Thông số kỹ thuật (mỗi dòng dạng: Thuộc tính: Mô tả)"></textarea>
        <p id="createProductError" class="inline-error hidden"></p>
        <button type="submit" class="btn-primary full">Tạo sản phẩm</button>
      </form>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <div id="toast" class="toast" aria-live="polite"></div>
  <script src="./assets/theme.js?v=20261001_1840"></script>
  <script src="./assets/admin-create-product.js"></script>
</body>
</html>



