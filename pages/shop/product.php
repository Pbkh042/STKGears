<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';
require_once __DIR__ . '/../../core/product.php';
require_once __DIR__ . '/../../services/product_service.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $productId > 0 ? find_product_by_id($productId) : null;
$relatedProducts = $product ? list_related_products((string)$product['category'], (int)$product['id']) : [];
if ($relatedProducts) {
    $seen = [];
    $relatedProducts = array_values(array_filter($relatedProducts, static function (array $item) use (&$seen): bool {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0 || isset($seen[$id])) {
            return false;
        }
        $seen[$id] = true;
        return true;
    }));
}

$gallery = [];
if ($product) {
    $gallery = is_array($product['gallery']) ? $product['gallery'] : [];
    array_unshift($gallery, (string)$product['image']);
    $gallery = array_values(array_unique(array_filter($gallery)));
}

$specRows = [];
if ($product) {
    $rawSpecs = (string)($product['technicalSpecs'] ?? '');
    $lines = preg_split('/\r\n|\r|\n/u', $rawSpecs) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (mb_strpos($line, ':') !== false) {
            [$k, $v] = array_pad(explode(':', $line, 2), 2, '');
            $k = trim((string)$k);
            $v = trim((string)$v);
            if ($k !== '' && $v !== '') {
                $specRows[] = ['key' => $k, 'value' => $v];
                continue;
            }
        }
        $specRows[] = ['key' => 'Thông tin', 'value' => $line];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $product ? esc((string)$product['name']) . ' | STK Gears' : 'Không tìm thấy sản phẩm | STK Gears' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_9">
</head>
<body>
  <div class="topbar"><p>Hotline tư vấn: <a href="tel:0123456789">0123 456 789</a></p></div>
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
      <?php if (is_admin_logged_in()): ?>
        <a id="authLink" class="ghost" href="./index.php?p=admin-products">Xin chào, <?= esc(current_admin_name()) ?></a>
      <?php elseif (is_user_logged_in()): ?>
        <a id="authLink" class="ghost" href="./index.php?p=user-history">Xin chào, <?= esc(current_user_name()) ?></a>
      <?php else: ?>
        <a id="authLink" class="ghost" href="./index.php?p=login">Đăng nhập</a>
      <?php endif; ?>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </div>
  </header>

  <main class="page-wrap stack">
    <?php if (!$product): ?>
      <section class="panel">
        <div class="admin-panel">
          <h2>Không tìm thấy sản phẩm</h2>
          <p class="muted">Sản phẩm không tồn tại hoặc đã bị xoá.</p>
          <a class="btn-secondary" href="./index.php?p=products">Quay lại trang sản phẩm</a>
        </div>
      </section>
    <?php else: ?>
      <section class="panel">
        <div class="admin-grid" style="align-items:start;">
          <div class="admin-panel stack">
            <div class="product-preview">
              <?= discount_badge((int)$product['price'], isset($product['oldPrice']) ? (int)$product['oldPrice'] : null) ?>
              <img id="mainPreviewImage" src="<?= esc((string)$gallery[0]) ?>" alt="<?= esc((string)$product['name']) ?>" style="width:100%;border-radius:14px;object-fit:cover;aspect-ratio:4/3;">
            </div>
            <?php if (count($gallery) > 1): ?>
            <div class="product-gallery">
              <?php foreach ($gallery as $idx => $img): ?>
                <button type="button" class="thumb-btn<?= $idx === 0 ? ' active' : '' ?>" data-image="<?= esc((string)$img) ?>">
                  <img src="<?= esc((string)$img) ?>" alt="Ảnh sản phẩm <?= $idx + 1 ?>">
                </button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="admin-panel stack">
            <h2 style="margin:0;"><?= esc((string)$product['name']) ?></h2>
            <p class="muted">Thương hiệu: <strong><?= esc((string)$product['brand']) ?></strong></p>
            <p class="muted">Danh mục: <?= esc(category_label((string)$product['category'])) ?></p>
            <p style="font-size:22px;font-weight:700;margin:0;">
              <?= vnd((int)$product['price']) ?>
              <?php if (!empty($product['oldPrice'])): ?>
                <span class="old" style="display:block"><?= vnd((int)$product['oldPrice']) ?></span>
              <?php endif; ?>
            </p>

            <?php if ((int)$product['stock'] <= 0): ?>
              <p class="status out">❌ Hết hàng</p>
            <?php elseif ((int)$product['stock'] <= 5): ?>
              <p class="status low">⚠️ Sắp hết hàng</p>
            <?php else: ?>
              <p class="status in">✅ Còn hàng</p>
            <?php endif; ?>

            <?php if (!empty($product['colors'])): ?>
              <div class="stack" style="gap:8px;">
                <p class="muted" style="margin:0;">Màu sắc:</p>
                <div id="colorOptions" class="color-list" data-colors="<?= esc((string)$product['colors']) ?>"></div>
              </div>
            <?php endif; ?>

            <div class="qty-row">
              <p class="muted" style="margin:0;">Số lượng:</p>
              <div class="qty-box">
                <button id="qtyMinusBtn" type="button">-</button>
                <span id="qtyValue">1</span>
                <button id="qtyPlusBtn" type="button">+</button>
              </div>
            </div>

            <div class="product-purchase-actions">
            <button id="addToCartBtn" type="button" class="btn-secondary" data-id="<?= (int)$product['id'] ?>" data-name="<?= esc((string)$product['name']) ?>" data-price="<?= (int)$product['price'] ?>" data-image="<?= esc((string)$product['image']) ?>" data-stock="<?= (int)$product['stock'] ?>" <?= ((int)$product['stock'] <= 0 || is_admin_logged_in()) ? 'disabled' : '' ?>>
              <?= ((int)$product['stock'] <= 0) ? 'Hết hàng' : 'Thêm vào giỏ' ?>
            </button>
            <button id="buyNowBtn" type="button" class="btn-primary" <?= ((int)$product['stock'] <= 0 || is_admin_logged_in()) ? 'disabled' : '' ?>>Mua ngay</button>
            </div>
            <p id="productInlineError" class="inline-error hidden" role="alert"></p>
            <?php if (is_admin_logged_in()): ?>
              <a class="product-link admin-link" href="./index.php?p=admin-edit-product&id=<?= (int)$product['id'] ?>">Sửa sản phẩm này</a>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="admin-panel">
          <div class="login-tabs">
            <button type="button" class="tab-btn active" data-detail-tab="desc">Mô tả</button>
            <button type="button" class="tab-btn" data-detail-tab="spec">Thông số kỹ thuật</button>
          </div>

          <div id="detail-desc" class="detail-tab active">
            <p><?= nl2br(esc((string)($product['description'] ?: 'Sản phẩm đang được cập nhật mô tả chi tiết.'))) ?></p>
          </div>

          <div id="detail-spec" class="detail-tab">
            <?php if (count($specRows) > 0): ?>
              <div class="table-wrap" style="padding:0;">
                <table class="table spec-table">
                  <tbody>
                  <?php foreach ($specRows as $row): ?>
                    <tr>
                      <th><?= esc((string)$row['key']) ?></th>
                      <td><?= esc((string)$row['value']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p>Sản phẩm đang được cập nhật thông số kỹ thuật.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <?php if (count($relatedProducts) > 0): ?>
      <section class="panel">
        <div class="panel-header"><h3>Sản phẩm liên quan</h3></div>
        <div class="product-grid" style="margin-top:0;padding:12px;">
          <?php foreach ($relatedProducts as $item): ?>
            <a class="product-card" href="./index.php?p=product&id=<?= (int)$item['id'] ?>">
              <?= discount_badge((int)$item['price'], isset($item['oldPrice']) ? (int)$item['oldPrice'] : null) ?>
              <img src="<?= esc((string)$item['image']) ?>" alt="" loading="lazy">
              <div class="product-content">
                <h4 class="product-title" title="<?= esc((string)$item['name']) ?>"><?= esc((string)$item['name']) ?></h4>
                <p class="price-line">
                  <?php if ((int)($item['oldPrice'] ?? 0) > (int)$item['price']): ?>
                    <del class="old"><?= vnd((int)$item['oldPrice']) ?></del>
                  <?php endif; ?>
                  <span class="current-price"><?= vnd((int)$item['price']) ?></span>
                </p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <div id="toast" class="toast" aria-live="polite"></div>
  <script src="./assets/theme.js?v=20261001_1840"></script>
  <script src="./assets/product.js?v=20261019"></script>
</body>
</html>


