// Ghi chú: File script xử lý tương tác giao diện và gọi API.
const addButton = document.getElementById("addToCartBtn");
const buyNowButton = document.getElementById("buyNowBtn");
let purchasePending = false;
const toastEl = document.getElementById("toast");
const mainPreviewImage = document.getElementById("mainPreviewImage");
const thumbButtons = document.querySelectorAll(".thumb-btn");
const detailTabButtons = document.querySelectorAll("[data-detail-tab]");
const colorOptions = document.getElementById("colorOptions");
const qtyMinusBtn = document.getElementById("qtyMinusBtn");
const qtyPlusBtn = document.getElementById("qtyPlusBtn");
const qtyValue = document.getElementById("qtyValue");
const CART_STORAGE_PREFIX = "stkGearsCart:user:";

let isUserLoggedIn = false;
let isAdminLoggedIn = false;
let currentUserId = null;
let selectedColor = "";
let selectedQty = 1;

function renderColorOptions() {
  if (!colorOptions) return;
  const raw = String(colorOptions.dataset.colors || "");
  const colors = raw.split(",").map((s) => s.trim()).filter(Boolean);
  if (colors.length === 0) return;

  selectedColor = colors[0];
  colorOptions.innerHTML = colors.map((c, idx) => `<button type="button" class="color-chip ${idx === 0 ? "active" : ""}" data-color="${c}">${c}</button>`).join("");
}
// Hàm render danh sách màu để chọn biến thể trước khi thêm giỏ.

function bindColorEvents() {
  colorOptions?.addEventListener("click", (event) => {
    const chip = event.target.closest(".color-chip");
    if (!chip) return;
    selectedColor = String(chip.dataset.color || "");
    colorOptions.querySelectorAll(".color-chip").forEach((el) => el.classList.remove("active"));
    chip.classList.add("active");
  });
}
// Hàm xử lý chọn màu hiện tại.

function syncQtyView() {
  if (qtyValue) qtyValue.textContent = String(selectedQty);
}
// Hàm đồng bộ số lượng đang chọn ra giao diện.

function bindQtyEvents() {
  if (!addButton) return;
  const stock = Number(addButton.dataset.stock || "0");
  qtyMinusBtn?.addEventListener("click", () => {
    selectedQty = Math.max(1, selectedQty - 1);
    syncQtyView();
  });
  qtyPlusBtn?.addEventListener("click", () => {
    selectedQty = Math.min(Math.max(stock, 1), selectedQty + 1);
    syncQtyView();
  });
}
// Hàm xử lý tăng/giảm số lượng, giới hạn theo tồn kho.

function loadCart() {
  if (!currentUserId) return [];
  try {
    const raw = localStorage.getItem(`${CART_STORAGE_PREFIX}${currentUserId}`);
    const storedCart = raw ? JSON.parse(raw) : [];
    return Array.isArray(storedCart) ? storedCart : [];
  } catch {
    return [];
  }
}
// Hàm đọc giỏ hàng thuộc về user đang đăng nhập.

function saveCart(cart) {
  if (!currentUserId) return;
  localStorage.setItem(`${CART_STORAGE_PREFIX}${currentUserId}`, JSON.stringify(cart));
}
// Hàm lưu giỏ hàng vào khóa riêng theo ID user.

function setInlineError(message = "") {
  const el = document.getElementById("productInlineError");
  if (!el) return;
  el.textContent = message;
  el.classList.toggle("hidden", message === "");
}
// Hàm hiển thị lỗi inline ở trang chi tiết sản phẩm.

function showToast(message) {
  if (!toastEl) return;
  toastEl.textContent = message;
  toastEl.classList.add("show");
  window.setTimeout(() => toastEl.classList.remove("show"), 1800);
}
// Hàm hiển thị toast ngắn cho thao tác thêm giỏ thành công.

async function fetchUserState() {
  try {
    const response = await fetch("./api/auth/session.php", { credentials: "same-origin" });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error("Cannot load session");
    const { user: userData, admin: adminData } = data;
    isUserLoggedIn = Boolean(userData.authenticated);
    isAdminLoggedIn = Boolean(adminData.authenticated);
    const userId = Number(userData.user?.id || 0);
    currentUserId = isUserLoggedIn && !isAdminLoggedIn && userId > 0 ? userId : null;
  } catch {
    isUserLoggedIn = false;
    isAdminLoggedIn = false;
    currentUserId = null;
  }
}
// Hàm kiểm tra user đã đăng nhập hay chưa để chặn mua khi chưa login.

function applyBuyPermissionUI() {
  if (!addButton) return;
  if (isAdminLoggedIn) {
    addButton.disabled = true;
    if (buyNowButton) buyNowButton.disabled = true;
    setInlineError("Tài khoản admin không thể đặt hàng.");
  }
}
// Hàm khóa thao tác đặt hàng với tài khoản admin ngay trên UI.

async function purchaseProduct(buyNow = false) {
    if (!addButton || addButton.disabled || purchasePending) return;
    purchasePending = true;
    addButton.disabled = true;
    if (buyNowButton) buyNowButton.disabled = true;
    try {
    await fetchUserState();
    if (isAdminLoggedIn) {
      setInlineError("Tài khoản admin không thể đặt hàng.");
      showToast("Admin không thể đặt hàng.");
      return;
    }
    if (!isUserLoggedIn) {
      setInlineError("Bạn cần đăng nhập trước.");
      showToast("Bạn cần đăng nhập trước.");
      return;
    }

    const product = {
      id: Number(addButton.dataset.id),
      name: String(addButton.dataset.name),
      price: Number(addButton.dataset.price),
      image: String(addButton.dataset.image),
      stock: Number(addButton.dataset.stock)
    };

    if (product.stock <= 0) {
      setInlineError("Sản phẩm đã hết hàng.");
      return;
    }

    const cart = loadCart();
    const existing = cart.find((item) => Number(item.id) === product.id);
    if (existing) {
      if (Number(existing.quantity) + selectedQty > product.stock) {
        setInlineError("Số lượng vượt quá tồn kho.");
        return;
      }
      existing.quantity = Number(existing.quantity) + selectedQty;
      existing.stock = product.stock;
      if (selectedColor) {
        existing.color = selectedColor;
      }
    } else {
      cart.push({
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        stock: product.stock,
        quantity: selectedQty,
        color: selectedColor
      });
    }

    saveCart(cart);
    setInlineError("");
    if (buyNow) {
      window.location.assign("./index.php?p=products&checkout=1");
      return;
    }
    showToast("Đã thêm vào giỏ hàng.");
    } catch {
      setInlineError("Không thể lưu giỏ hàng. Vui lòng thử lại.");
    } finally {
      purchasePending = false;
      const disabled = isAdminLoggedIn || Number(addButton.dataset.stock) <= 0;
      addButton.disabled = disabled;
      if (buyNowButton) buyNowButton.disabled = disabled;
    }
}

addButton?.addEventListener("click", () => purchaseProduct());
buyNowButton?.addEventListener("click", () => purchaseProduct(true));

thumbButtons.forEach((button) => {
  button.addEventListener("click", () => {
    if (!mainPreviewImage) return;
    thumbButtons.forEach((b) => b.classList.remove("active"));
    button.classList.add("active");
    const image = String(button.dataset.image || "");
    if (image) mainPreviewImage.src = image;
  });
});

detailTabButtons.forEach((button) => {
  button.addEventListener("click", () => {
    detailTabButtons.forEach((b) => b.classList.remove("active"));
    button.classList.add("active");
    const tab = String(button.dataset.detailTab || "");
    const desc = document.getElementById("detail-desc");
    const spec = document.getElementById("detail-spec");
    if (!desc || !spec) return;
    desc.classList.toggle("active", tab === "desc");
    spec.classList.toggle("active", tab === "spec");
  });
});

renderColorOptions();
bindColorEvents();
bindQtyEvents();
syncQtyView();
fetchUserState().then(applyBuyPermissionUI);



