// Ghi chú: File script xử lý tương tác giao diện và gọi API.
const menuBtn = document.getElementById("menuBtn");
const nav = document.querySelector(".nav");
const authLink = document.getElementById("authLink");
const adminPanelLink = document.getElementById("adminPanelLink");

async function apiRequest(url) {
  const response = await fetch(url, { credentials: "same-origin" });
  const payload = await response.json().catch(() => ({ ok: false }));
  if (!response.ok || !payload.ok) {
    throw new Error(payload.message || "Lỗi hệ thống.");
  }
  return payload;
}
// Hàm gọi API và chuẩn hóa lỗi để phần còn lại xử lý thống nhất.

async function bindAuthLinks() {
  try {
    const [adminRes, userRes] = await Promise.all([
      apiRequest("./api/admin/me.php"),
      apiRequest("./api/user/me.php")
    ]);

    if (adminRes.authenticated) {
      authLink?.classList.remove("hidden");
      authLink?.setAttribute("href", "./index.php?p=admin-products");
      if (authLink) authLink.textContent = `Xin chào, ${adminRes.user?.username || "admin"}`;
      adminPanelLink?.classList.add("hidden");
      return;
    }

    if (userRes.authenticated) {
      authLink?.classList.remove("hidden");
      authLink?.setAttribute("href", "./index.php?p=user-history");
      if (authLink) authLink.textContent = `Xin chào, ${userRes.user?.username || "user"}`;
      adminPanelLink?.classList.add("hidden");
      return;
    }
  } catch {
    // Bỏ qua lỗi auth ở trang chủ.
  }
  if (authLink) authLink.textContent = "Đăng nhập";
  authLink?.setAttribute("href", "./index.php?p=login");
  authLink?.classList.remove("hidden");
  adminPanelLink?.classList.add("hidden");
}
// Hàm đồng bộ nút đăng nhập/quản trị theo trạng thái admin hiện tại.

menuBtn?.addEventListener("click", () => nav?.classList.toggle("open"));
bindAuthLinks();



