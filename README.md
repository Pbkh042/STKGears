> Ghi chú: Tài liệu mô tả nhanh module/chức năng.

# STK Gears - Báo cáo sản phẩm Dự án 2

STK Gears là website TMĐT mini, xây dựng theo hướng tập trung vào luồng mua hàng thực tế: đăng ký/đăng nhập, tìm sản phẩm, thêm giỏ, checkout, theo dõi lịch sử đơn và quản trị đơn hàng.

## 1) Cấu trúc thư mục

- `api/`: endpoint JSON cho auth, products, orders, admin actions
- `assets/`: CSS/JS frontend
- `config/`: cấu hình DB + auth/session
- `core/`: helper dùng chung (view, product validation)
- `docs/`: tài liệu kỹ thuật + checklist QA
- `pages/`: nhóm giao diện theo module (`admin`, `auth`, `shop`, `user`)`r`n- `services/`: business logic (`auth_service`, `product_service`, `order_service`)
- `sql/`: schema + seed dữ liệu MySQL/phpMyAdmin

## 2) Chức năng đã triển khai

- User:
  - Đăng ký/đăng nhập
  - Xem danh sách sản phẩm (search toàn DB, lọc danh mục, phân trang 10/sp trang)
  - Xem chi tiết sản phẩm (ảnh, mô tả, thông số, SKU, tồn kho)
  - Thêm giỏ hàng, checkout có validate, tạo đơn thật vào DB
  - Xem lịch sử đơn và xem chi tiết từng đơn
- Admin:
  - Đăng nhập chung, hệ thống tự xác định quyền admin
  - CRUD sản phẩm (brand, mô tả, thông số, tồn kho, ảnh chính + ảnh phụ, màu)
  - Dashboard sản phẩm có live search + phân trang
  - Dashboard đơn hàng có live search + phân trang 10 đơn/trang
  - Trang chi tiết đơn để cập nhật trạng thái / loại bỏ đơn

## 3) Quy tắc bảo mật và validate

- Dùng prepared statements cho toàn bộ truy vấn chính.
- `session_regenerate_id(true)` sau login.
- Guard quyền truy cập theo role (`require_user_page`, `require_admin_page`).
- Chặn checkout khi chưa đăng nhập user.
- Chặn đặt vượt tồn kho (backend transaction + lock hàng).
- Username user/admin có unique index.

## 4) Setup MySQL (phpMyAdmin)

1. Tạo DB hoặc import trực tiếp file `sql/stkgear.sql`.
2. Collation dùng `utf8_unicode_ci` như yêu cầu.
3. File SQL có sẵn seed admin/user và sản phẩm mẫu.

## 5) Cấu hình kết nối DB

Sửa `config/app.php`:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'stkgear';
const DB_USER = 'root';
const DB_PASS = '';
```

## 6) Chạy local

```powershell
cd "C:\Users\ADMIN\Desktop\STKGear"
php -S 127.0.0.1:8080
```

Truy cập: `http://127.0.0.1:8080`

## 7) Tài khoản mẫu

- User: `customer` / `customer123`
- Admin: `admin` / `admin123`

## 8) Các trang chính

- `index.html`: trang chủ
- `products.php`: danh sách sản phẩm + giỏ + checkout
- `product.php`: chi tiết sản phẩm
- `policy.php`: chính sách bảo hành
- `login.php`: user/admin login + user signup
- `user-history.php`: lịch sử đơn user
- `admin-products.php`: quản trị sản phẩm
- `admin-orders.php`: quản trị đơn hàng



