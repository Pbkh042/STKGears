<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chính sách bảo hành - STK Gear</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body>
  <div class="topbar"><p>Chính sách bảo hành - STK Gear</p></div>
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
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
      <a id="authLink" class="ghost" href="./index.php?p=login">Đăng nhập</a>
      <a id="adminPanelLink" class="ghost hidden" href="./index.php?p=admin-products">Quản trị</a>
    </div>
  </header>

  <main class="page-wrap stack">
    <section class="panel"><div class="admin-panel stack">
      <h2>A. Địa điểm bảo hành</h2>
      <ul>
        <li>Toàn bộ sản phẩm được phân phối và bán lẻ bởi STKGear đều được bảo hành tại địa chỉ của STKGear.</li>
        <li>Đối với bàn phím không có catch ball (tháo lắp nhanh), STKGear sẽ bóc seal 1 góc và dán tem ở mép phím.</li>
        <li>Đối với bàn phím có catch ball (tháo lắp nhanh), STKGear sẽ bóc seal hoàn toàn và dán tem ở mặt dưới mạch PCB để đảm bảo đúng sản phẩm giao hàng.</li>
        <li>Đối với đơn mua hàng từ xa, quý khách vui lòng liên hệ Fanpage/Zalo để được hỗ trợ xác định tình trạng sản phẩm.</li>
        <li>Quy trình bảo hành từ xa: đóng gói kỹ sản phẩm, ghi rõ tên/SĐT/địa chỉ bên ngoài kiện hàng, gửi về địa chỉ của STKGear.</li>
        <li>Chi phí chiều gửi đi do khách hàng thanh toán, chiều gửi lại sau bảo hành do STKGear thanh toán.</li>
      </ul>

      <h2>B. Trường hợp không bảo hành</h2>
      <ul>
        <li>Sản phẩm không còn tem niêm phong/tem QC, có dấu hiệu tự ý tháo rời can thiệp kết cấu.</li>
        <li>Dùng nguồn sạc điện trực tiếp không đúng khuyến nghị cho thiết bị không dây gây hư hỏng.</li>
        <li>Hư hỏng do rơi vỡ, va chạm, vào nước, ẩm thấp, chập cháy.</li>
        <li>Hư hỏng do thiên tai, động vật, côn trùng hoặc do con người gây ra.</li>
        <li>Rỉ sét do thời tiết, độ ẩm và thói quen người dùng.</li>
        <li>Hết thời hạn bảo hành tính từ ngày nhận hàng.</li>
        <li>Khách hàng tự ý can thiệp firmware sản phẩm.</li>
      </ul>

      <h2>C. Các trường hợp khác</h2>
      <ul>
        <li>Bảo hành theo tiêu chuẩn nhà sản xuất.</li>
        <li>Bàn phím cơ được bảo hành các lỗi do nhà sản xuất: hỏng mạch, chết LED, lỗi kết nối.</li>
        <li>Các sản phẩm không sử dụng điện như Keycap, Switch, Keypuller, chổi, dây cáp, kê tay, Deskmat... không bảo hành.</li>
      </ul>
    </div></section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
  <script src="./assets/home.js"></script>
</body>
</html>


