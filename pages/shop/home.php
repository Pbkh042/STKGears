<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STK Gears | Trang chủ</title>
  <meta name="description" content="STK Gears - cửa hàng bàn phím cơ, hall effect, chuột và phụ kiện tại Ninh Bình.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body>
  <div class="topbar"><p>Hotline tư vấn: <a href="tel:0123456789">0123 456 789</a></p></div>

  <header class="header">
    <a class="brand" href="#home" style="display:flex;align-items:center;gap:10px;">
      <img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;">
      <span>STK Gears</span>
    </a>
    <nav class="nav">
      <a href="#home">Trang chủ</a>
      <a href="./index.php?p=products">Sản phẩm</a>
      <a href="./index.php?p=products" data-menu="catalog">Danh mục</a>
      <a href="./index.php?p=policy">Chính sách</a>
    </nav>
    <div class="actions">
      <button id="menuBtn" class="ghost mobile-only" aria-label="Mở menu">Menu</button>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
      <a id="authLink" class="ghost" href="./index.php?p=login">Đăng nhập</a>
      <a id="adminPanelLink" class="ghost hidden" href="./index.php?p=admin-products">Quản trị</a>
      <a class="cart-btn" href="./index.php?p=products">Giỏ hàng</a>
    </div>
  </header>

  <main id="home">
    <section class="hero">
      <div class="hero-copy">
        <p class="eyebrow">STK Gears</p>
        <h1>Thiết bị gõ phím và phụ kiện cho góc setup thực dụng.</h1>
        <p>STK Gears tập trung vào bàn phím cơ, bàn phím Hall Effect, chuột không dây, pad chuột, switch và keycap với thông tin rõ ràng, dễ chọn cho nhu cầu học tập, làm việc và giải trí.</p>
        <div class="hero-cta">
          <a href="./index.php?p=products" class="btn-primary">Xem sản phẩm</a>
          <a href="./index.php?p=policy" class="btn-secondary">Xem chính sách</a>
        </div>
      </div>
      <div class="hero-card" style="background-image:linear-gradient(rgba(11,26,58,.45),rgba(11,26,58,.45)),url('https://images.bauerhosting.com/affiliates/sites/8/2024/04/QWERTY-Feature.jpg?ar=16%3A9&fit=crop&crop=top&auto=format&w=1440&q=80');background-size:cover;background-position:center;">
        <p>STK Gears tại Ninh Bình</p>
        <h2>Tư vấn nhanh</h2>
        <span>Hỗ trợ chọn layout, switch và keycap theo gu cá nhân.</span>
      </div>
    </section>

    <section class="catalog">
      <div class="catalog-head">
        <h2>Về cửa hàng</h2>
        <p>Xây dựng để người mua mới cũng chọn được sản phẩm phù hợp ngay từ đầu.</p>
      </div>
      <div class="panel admin-panel">
        <p class="muted">STK Gears phân phối danh mục theo nhu cầu thực tế: bàn phím cơ, bàn phím Hall Effect, chuột có dây/không dây và nhóm phụ kiện gồm pad chuột, switch, keycap. Mỗi sản phẩm đều có thông tin mô tả, thông số kỹ thuật, tình trạng tồn kho và mức giá rõ ràng để người mua dễ so sánh.</p>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <div id="toast" class="toast" aria-live="polite"></div>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
  <script src="./assets/home.js?v=20261102_1"></script>
</body>
</html>


