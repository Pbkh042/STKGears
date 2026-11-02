<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sản phẩm | STK Gears</title>
  <meta name="description" content="Danh mục sản phẩm STK Gears: bàn phím cơ, hall effect, chuột, pad, switch, keycap.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261019_11">
</head>
<body>
  <div class="topbar">
    <p>Hotline tư vấn: <a href="tel:0123456789">0123 456 789</a></p>
  </div>

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
      <button id="menuBtn" class="ghost mobile-only" aria-label="Mở menu">Menu</button>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
      <a id="authLink" class="ghost" href="./index.php?p=login">Đăng nhập</a>
      <a id="adminPanelLink" class="ghost hidden" href="./index.php?p=admin-products">Quản trị</a>
      <button id="openCartBtn" class="cart-btn" aria-label="Mở giỏ hàng">
        Giỏ hàng <span id="cartCount">0</span>
      </button>
    </div>
  </header>

  <main class="page-wrap catalog-page">
    <section class="catalog" style="margin-top:0;">
      <div class="catalog-head">
        <h2>Danh mục sản phẩm</h2>
      </div>
      <div class="toolbar">
        <input id="searchInput" type="search" placeholder="Tìm sản phẩm...">
        <select id="categoryFilter" aria-label="Lọc theo danh mục">
          <option value="all">Tất cả danh mục</option>
          <option value="keyboard_mech">Bàn phím cơ</option>
          <option value="keyboard_hall">Bàn phím Hall Effect</option>
          <option value="mouse_wireless">Chuột không dây</option>
          <option value="mouse_wired">Chuột có dây</option>
          <option value="pad_mouse">Pad chuột</option>
          <option value="accessory_switch">Switch</option>
          <option value="accessory_keycap">Keycap</option>
        </select>
        <select id="sortFilter" aria-label="Sắp xếp">
          <option value="popular">Phổ biến</option>
          <option value="price-asc">Giá thấp đến cao</option>
          <option value="price-desc">Giá cao đến thấp</option>
        </select>
      </div>
      <div id="productGrid" class="product-grid catalog-grid"></div>
      <div id="productPagination" class="pagination"></div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <aside id="cartDrawer" class="cart-drawer" aria-hidden="true">
    <div class="cart-header">
      <h3>Giỏ hàng của bạn</h3>
      <button id="closeCartBtn" class="ghost">Đóng</button>
    </div>
    <div id="cartItems" class="cart-items"></div>
    <p id="cartInlineError" class="inline-error hidden"></p>
    <div class="cart-footer">
      <p>Tạm tính: <strong id="cartSubtotal">0đ</strong></p>
      <button id="checkoutBtn" class="btn-primary full">Thanh toán</button>
      <a id="cartHistoryLink" class="btn-secondary full" href="./index.php?p=user-history" style="display:block;text-align:center;margin-top:8px;">Lịch sử</a>
    </div>
  </aside>

  <div id="checkoutModal" class="modal" aria-hidden="true">
    <div class="modal-body">
      <div class="cart-header">
        <h3>Thông tin thanh toán</h3>
        <button id="closeCheckoutBtn" class="ghost">Đóng</button>
      </div>
      <form id="checkoutForm" class="checkout-form">
        <input name="name" placeholder="Họ và tên" required>
        <input name="phone" placeholder="Số điện thoại" required pattern="[0-9+ ]{8,15}">
        <input name="address" placeholder="Địa chỉ nhận hàng" required>
        <textarea name="note" placeholder="Ghi chú đơn hàng (tuỳ chọn)"></textarea>
        <p id="checkoutInlineError" class="inline-error hidden" role="alert"></p>
        <button type="submit" class="btn-primary full">Xác nhận đặt hàng</button>
      </form>
    </div>
  </div>

  <dialog id="orderSuccessDialog" class="order-success-dialog" aria-labelledby="orderSuccessTitle" aria-describedby="orderSuccessDescription">
    <button id="closeOrderSuccessBtn" class="order-success-close" type="button" aria-label="Đóng thông báo">×</button>
    <h2 id="orderSuccessTitle">Đặt hàng thành công!</h2>
    <p id="orderSuccessDescription">Cảm ơn bạn đã mua sắm tại STK Gears.<br>Đơn hàng của bạn đã được ghi nhận.</p>
    <div class="order-success-animation" aria-hidden="true">
      <span id="orderSuccessFallback" class="order-success-check">✓</span>
      <img id="orderSuccessAnimation" width="180" height="180" alt="" hidden>
    </div>
    <div class="order-success-actions">
      <a id="viewSuccessOrderLink" class="btn-primary" href="./index.php?p=user-history">Xem đơn hàng</a>
      <button id="continueShoppingBtn" class="btn-secondary" type="button" autofocus>Tiếp tục mua sắm</button>
    </div>
  </dialog>

  <div id="toast" class="toast" aria-live="polite"></div>
  <script src="./assets/theme.js?v=20261001_1840"></script>
  <script src="./assets/app.js?v=20261019_11"></script>
</body>
</html>

