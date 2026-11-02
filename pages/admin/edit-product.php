<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../services/product_service.php';

require_admin_page();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: ./index.php?p=admin-products');
    exit;
}

$product = find_product_by_id($productId);
if (!$product) {
    header('Location: ./index.php?p=admin-products');
    exit;
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        update_product($productId, $_POST);
        $product = find_product_by_id($productId);
        $message = 'Đã cập nhật sản phẩm.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$galleryText = is_array($product['gallery']) ? implode("\n", $product['gallery']) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sửa sản phẩm |STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Chỉnh sửa sản phẩm - STK Gears</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;"><img src="./assets/s.png" alt="STJ Logo" style="width:28px;height:28px;object-fit:contain;"><span>STK Gears</span></a>
    <nav class="nav">
      <a href="./index.php?p=admin-products">Sản phẩm</a>
      <a href="./index.php?p=admin-orders">Đơn hàng</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </nav>
  </header>

  <main class="page-wrap">
    <section class="panel">
      <div class="panel-header"><h2>Sửa sản phẩm #<?= (int)$product['id'] ?></h2></div>
      <form method="post" class="checkout-form">
        <input name="name" value="<?= esc((string)$product['name']) ?>" required>
        <input name="brand" value="<?= esc((string)$product['brand']) ?>" required>
        <select name="category" required>
          <option value="keyboard_mech" <?= $product['category'] === 'keyboard_mech' ? 'selected' : '' ?>>Bàn phím cơ</option>
          <option value="keyboard_hall" <?= $product['category'] === 'keyboard_hall' ? 'selected' : '' ?>>Bàn phím Hall Effect</option>
          <option value="mouse_wireless" <?= $product['category'] === 'mouse_wireless' ? 'selected' : '' ?>>Chuột không dây</option>
          <option value="mouse_wired" <?= $product['category'] === 'mouse_wired' ? 'selected' : '' ?>>Chuột có dây</option>
          <option value="pad_mouse" <?= $product['category'] === 'pad_mouse' ? 'selected' : '' ?>>Pad chuột</option>
          <option value="accessory_switch" <?= $product['category'] === 'accessory_switch' ? 'selected' : '' ?>>Switch</option>
          <option value="accessory_keycap" <?= $product['category'] === 'accessory_keycap' ? 'selected' : '' ?>>Keycap</option>
        </select>
        <input name="price" type="number" min="1000" step="1000" value="<?= (int)$product['price'] ?>" required>
        <input name="old_price" type="number" min="0" step="1000" value="<?= $product['oldPrice'] !== null ? (int)$product['oldPrice'] : '' ?>" placeholder="Giá cũ (nếu có)">
        <input name="stock" type="number" min="0" step="1" value="<?= (int)$product['stock'] ?>" required>
        <input name="image_url" type="url" value="<?= esc((string)$product['image']) ?>" required>
        <textarea name="gallery" placeholder="Ảnh phụ: mỗi dòng 1 link (https://...)"><?= esc($galleryText) ?></textarea>
        <input name="colors" type="text" value="<?= esc((string)$product['colors']) ?>" placeholder="Màu sắc (VD: Đen,Trắng)">
        <textarea class="product-desc-input" name="description" placeholder="Mô tả sản phẩm"><?= esc((string)$product['description']) ?></textarea>
        <textarea class="product-spec-input" name="technical_specs" placeholder="Thông số kỹ thuật (mỗi dòng dạng: Thuộc tính: Mô tả)"><?= esc((string)$product['technicalSpecs']) ?></textarea>
        <button type="submit" class="btn-primary full">Lưu thay đổi</button>
      </form>
      <?php if ($message !== ''): ?><p class="admin-panel" style="color:#018965;"><?= esc($message) ?></p><?php endif; ?>
      <?php if ($error !== ''): ?><p class="admin-panel" style="color:#b51d45;"><?= esc($error) ?></p><?php endif; ?>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <script src="./assets/theme.js?v=20261001_1840"></script>
</body>
</html>



