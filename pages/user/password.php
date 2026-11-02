<?php
declare(strict_types=1);
// Ghi chú: Trang cho user đổi mật khẩu tài khoản của chính mình.

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../core/view.php';

require_user_page();
$username = current_user_name();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đổi mật khẩu | STK Gears</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/styles.css?v=20261102_mobile1">
</head>
<body>
  <div class="topbar"><p>Xin chào, <?= esc($username) ?> | Đổi mật khẩu</p></div>
  <header class="header">
    <a class="brand" href="./index.php?p=home" style="display:flex;align-items:center;gap:10px;">
      <img src="./assets/s.png" alt="STK Logo" style="width:28px;height:28px;object-fit:contain;">
      <span>STK Gears</span>
    </a>
    <nav class="nav">
      <a href="./index.php?p=home">Trang chủ</a>
      <a href="./index.php?p=products">Sản phẩm</a>
      <a href="./index.php?p=products" data-menu="catalog">Danh mục</a>
      <a href="./index.php?p=user-history">Lịch sử</a>
      <a href="./index.php?p=logout">Đăng xuất</a>
    </nav>
  </header>

  <main class="page-wrap stack password-page">
    <section class="panel">
      <div class="panel-header"><h3>Đổi mật khẩu</h3></div>
      <div class="admin-panel">
        <form id="changePasswordForm" class="stack password-form">
          <input name="currentPassword" type="password" placeholder="Mật khẩu hiện tại" autocomplete="current-password" required>
          <input name="newPassword" type="password" placeholder="Mật khẩu mới (ít nhất 8 ký tự)" autocomplete="new-password" required minlength="8">
          <input name="confirmPassword" type="password" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password" required minlength="8">
          <p id="passwordInlineError" class="inline-error hidden" role="alert"></p>
          <button type="submit" class="btn-primary">Lưu mật khẩu mới</button>
        </form>
      </div>
    </section>
  </main>
  <?php require __DIR__ . '/../../core/footer.php'; ?>

  <div id="toast" class="toast" aria-live="polite"></div>
  <script src="./assets/theme.js?v=20261102_mobile1"></script>
  <script src="./assets/mobile.js?v=20261102_mobile1"></script>
  <script>
    (function () {
      const form = document.getElementById('changePasswordForm');
      const inlineError = document.getElementById('passwordInlineError');
      const toast = document.getElementById('toast');
      const submitButton = form?.querySelector('button[type="submit"]');
      let submitting = false;

      function showToast(message, ok) {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show ' + (ok ? 'success' : 'error');
        setTimeout(function () { toast.className = 'toast'; }, 2600);
      }

      form?.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (submitting) return;
        inlineError?.classList.add('hidden');
        if (inlineError) inlineError.textContent = '';

        const fd = new FormData(form);
        const payload = {
          currentPassword: String(fd.get('currentPassword') || ''),
          newPassword: String(fd.get('newPassword') || ''),
          confirmPassword: String(fd.get('confirmPassword') || ''),
        };

        submitting = true;
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = 'Đang lưu...';
        }
        try {
        const res = await fetch('./api/user/change_password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(payload)
        });
        const data = await res.json().catch(function () { return {}; });

        if (!res.ok || !data.ok) {
          if (inlineError) {
            inlineError.textContent = data.message || 'Không thể đổi mật khẩu. Vui lòng thử lại sau.';
            inlineError.classList.remove('hidden');
          }
          return;
        }

        form.reset();
        showToast(data.message || 'Đổi mật khẩu thành công.', true);
        } catch {
          if (inlineError) {
            inlineError.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
            inlineError.classList.remove('hidden');
          }
        } finally {
          submitting = false;
          if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Lưu mật khẩu mới';
          }
        }
      });
    })();
  </script>
</body>
</html>
