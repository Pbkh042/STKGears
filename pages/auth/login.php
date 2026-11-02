<?php
declare(strict_types=1);
// Ghi chú: File xử lý logic/trang tương ứng của module.
require_once __DIR__ . '/../../config/auth.php';

$tab = (string)($_GET['tab'] ?? 'login');
$initialTab = $tab === 'signup' ? 'signup' : 'login';
$firebaseLogin = data_backend() === 'firebase';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body class="auth-page">
  <div class="topbar"><p>STK Gears - Đăng nhập hệ thống</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;"><img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;"><span>STK Gears</span></a>
    <nav class="nav">
      <a href="./index.php?p=home">Trang chủ</a>
      <a href="./index.php?p=products">Sản phẩm</a>
      <a href="./index.php?p=products" data-menu="catalog">Danh mục</a>
      <a href="./index.php?p=policy">Chính sách</a>
    </nav>
    <div class="actions">
      <a class="ghost" href="./index.php?p=login">Đăng nhập</a>
      <button id="themeToggleBtn" class="theme-toggle" type="button" aria-label="Chuyển giao diện">☾</button>
    </div>
  </header>

  <main class="auth-page-wrap">
    <section id="authCard" class="auth-card <?= $initialTab === 'signup' ? 'is-signup' : '' ?>" aria-label="Đăng nhập hoặc đăng ký">
      <div class="auth-form-viewport">
        <div class="auth-forms-track">
          <form id="unifiedLoginForm" class="auth-form" novalidate>
            <p class="auth-eyebrow">STK GEARS</p>
            <h1>Chào mừng trở lại</h1>
            <p class="auth-copy">Đăng nhập để tiếp tục. Hệ thống sẽ tự đưa bạn đến đúng giao diện theo quyền tài khoản.</p>
            <label class="sr-only" for="loginUsername"><?= $firebaseLogin ? 'Email' : 'Tên đăng nhập' ?></label>
            <input id="loginUsername" name="username" type="<?= $firebaseLogin ? 'email' : 'text' ?>" autocomplete="username" placeholder="<?= $firebaseLogin ? 'Email của bạn' : 'Tên đăng nhập' ?>" required>
            <label class="sr-only" for="loginPassword">Mật khẩu</label>
            <div class="auth-password-field">
              <input id="loginPassword" name="password" type="password" autocomplete="current-password" placeholder="Mật khẩu" required>
              <button type="button" class="password-toggle" data-password-target="loginPassword" aria-label="Hiện mật khẩu" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
              </button>
            </div>
            <p id="loginError" class="inline-error hidden" aria-live="polite"></p>
            <button type="submit" class="btn-primary full">Đăng nhập</button>
          </form>

          <form id="userSignupForm" class="auth-form" novalidate>
            <p class="auth-eyebrow">TÀI KHOẢN MỚI</p>
            <h1>Tạo tài khoản</h1>
            <p class="auth-copy">Tài khoản đăng ký mới có quyền mua hàng và quản lý đơn hàng cá nhân.</p>
            <label class="sr-only" for="signupUsername"><?= $firebaseLogin ? 'Email đăng ký' : 'Tên đăng nhập mới' ?></label>
            <?php if ($firebaseLogin): ?>
            <input id="signupUsername" name="username" type="email" autocomplete="username" placeholder="Email đăng ký" required maxlength="254">
            <?php else: ?>
            <input id="signupUsername" name="username" autocomplete="username" placeholder="Tên đăng nhập mới" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.]+">
            <?php endif; ?>
            <label class="sr-only" for="signupPassword">Mật khẩu mới</label>
            <div class="auth-password-field">
              <input id="signupPassword" name="password" type="password" autocomplete="new-password" placeholder="Mật khẩu mới (ít nhất 8 ký tự)" required minlength="8">
              <button type="button" class="password-toggle" data-password-target="signupPassword" aria-label="Hiện mật khẩu" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
              </button>
            </div>
            <label class="sr-only" for="signupConfirmPassword">Nhập lại mật khẩu</label>
            <div class="auth-password-field">
              <input id="signupConfirmPassword" name="confirmPassword" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required minlength="8">
              <button type="button" class="password-toggle" data-password-target="signupConfirmPassword" aria-label="Hiện mật khẩu" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/><path class="eye-slash" d="M4 4 20 20"/></svg>
              </button>
            </div>
            <p id="signupError" class="inline-error hidden" aria-live="polite"></p>
            <button type="submit" class="btn-primary full">Tạo tài khoản</button>
          </form>
        </div>
      </div>

      <aside class="auth-side" aria-live="polite">
        <div class="auth-side-content auth-side-login">
          <p class="auth-side-kicker">THÀNH VIÊN MỚI</p>
          <h2>Chưa có tài khoản?</h2>
          <p>Tạo tài khoản để lưu đơn hàng và mua sắm nhanh hơn.</p>
          <button type="button" class="auth-toggle-btn" data-auth-mode="signup">Đăng ký</button>
        </div>
        <div class="auth-side-content auth-side-signup">
          <p class="auth-side-kicker">ĐÃ LÀ THÀNH VIÊN</p>
          <h2>Chào mừng trở lại</h2>
          <p>Đăng nhập bằng tài khoản hiện có để tiếp tục mua sắm.</p>
          <button type="button" class="auth-toggle-btn" data-auth-mode="login">Đăng nhập</button>
        </div>
      </aside>
      <p id="loginMessage" class="auth-message" aria-live="polite"></p>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <script>
    window.__INITIAL_TAB__ = <?= json_encode($initialTab, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
  <script src="./assets/login.js?v=20261001_1855"></script>
</body>
</html>


