// Ghi chú: File script xử lý tương tác giao diện và gọi API.
const VND = new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" });
const CART_STORAGE_PREFIX = "stkGearsCart:user:";
const FILTER_STORAGE_KEY = "stkGearsFilters";
const LAST_ORDER_STORAGE_KEY = "stkGearsLastOrder";

let products = [];
let cart = [];
let isAdmin = false;
let isUserLoggedIn = false;
let currentUserId = null;
let currentPage = 1;
const PAGE_SIZE = 10;
let lastSearchTimer = null;
let orderSubmitting = false;

const dom = {
  productGrid: document.getElementById("productGrid"),
  searchInput: document.getElementById("searchInput"),
  categoryFilter: document.getElementById("categoryFilter"),
  sortFilter: document.getElementById("sortFilter"),
  cartDrawer: document.getElementById("cartDrawer"),
  openCartBtn: document.getElementById("openCartBtn"),
  closeCartBtn: document.getElementById("closeCartBtn"),
  cartItems: document.getElementById("cartItems"),
  cartCount: document.getElementById("cartCount"),
  cartSubtotal: document.getElementById("cartSubtotal"),
  cartHistoryLink: document.getElementById("cartHistoryLink"),
  checkoutBtn: document.getElementById("checkoutBtn"),
  checkoutModal: document.getElementById("checkoutModal"),
  closeCheckoutBtn: document.getElementById("closeCheckoutBtn"),
  checkoutForm: document.getElementById("checkoutForm"),
  authLink: document.getElementById("authLink"),
  adminPanelLink: document.getElementById("adminPanelLink"),
  toast: document.getElementById("toast"),
  menuBtn: document.getElementById("menuBtn"),
  nav: document.querySelector(".nav"),
  pagination: document.getElementById("productPagination")
};

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
// Hàm đọc giỏ hàng riêng của user đang đăng nhập từ localStorage.

function saveCart() {
  if (!currentUserId) return;
  localStorage.setItem(`${CART_STORAGE_PREFIX}${currentUserId}`, JSON.stringify(cart));
}
// Hàm lưu giỏ hàng vào khóa gắn với ID user hiện tại.

function setCartInlineError(message = "") {
  const el = document.getElementById("cartInlineError");
  if (!el) return;
  el.textContent = message;
  el.classList.toggle("hidden", message === "");
}
// Hàm hiển thị lỗi inline trong khu vực giỏ hàng.

function showToast(message) {
  if (!dom.toast) return;
  dom.toast.textContent = message;
  dom.toast.classList.add("show");
  window.setTimeout(() => dom.toast.classList.remove("show"), 2200);
}
// Hàm hiển thị toast cho các hành động ngắn hạn.

async function apiRequest(url, options = {}) {
  const response = await fetch(url, {
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {})
    },
    ...options
  });

  const payload = await response.json().catch(() => ({ ok: false, message: "Invalid server response." }));
  if (!response.ok || !payload.ok) {
    throw new Error(payload.message || "Request failed.");
  }
  return payload;
}
// Hàm gọi API chung với xử lý lỗi response thống nhất.

function getStatus(product) {
  if (Number(product.stock) <= 0) return { text: "❌ Hết hàng", className: "out" };
  if (Number(product.stock) <= 5) return { text: "⚠️ Sắp hết hàng", className: "low" };
  return { text: "✅ Còn hàng", className: "in" };
}
// Hàm tính trạng thái tồn kho để hiển thị màu/cảnh báo.

function categoryLabel(value) {
  const map = {
    keyboard_mech: "Bàn phím cơ",
    keyboard_hall: "Bàn phím Hall Effect",
    mouse_wireless: "Chuột không dây",
    mouse_wired: "Chuột có dây",
    pad_mouse: "Pad chuột",
    accessory_switch: "Switch",
    accessory_keycap: "Keycap"
  };
  return map[value] || value;
}
// Hàm chuyển mã danh mục sang nhãn thân thiện cho người dùng.

function currentFilters() {
  return {
    search: dom.searchInput?.value.trim() || "",
    category: dom.categoryFilter?.value || "all",
    sort: dom.sortFilter?.value || "popular"
  };
}
// Hàm đọc bộ lọc hiện tại để gọi API và giữ state sau refresh.

function saveFilterState() {
  const filters = currentFilters();
  localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(filters));
}
// Hàm lưu filter vào localStorage để giữ state sau refresh.

function restoreFilterState() {
  const params = new URLSearchParams(window.location.search);
  const fromUrl = params.get("category");
  if (fromUrl && dom.categoryFilter) {
    dom.categoryFilter.value = fromUrl;
  }

  try {
    const raw = localStorage.getItem(FILTER_STORAGE_KEY);
    if (!raw) return;
    const filters = JSON.parse(raw);
    if (!fromUrl) {
      if (dom.searchInput) dom.searchInput.value = filters.search || "";
      if (dom.categoryFilter) dom.categoryFilter.value = filters.category || "all";
    }
    if (dom.sortFilter) dom.sortFilter.value = filters.sort || "popular";
  } catch {
    // noop
  }
}
// Hàm khôi phục filter đã lưu để tránh mất trạng thái khi tải lại trang.

async function fetchProducts() {
  try {
    const filters = currentFilters();
    saveFilterState();

    const query = new URLSearchParams({
      search: filters.search,
      category: filters.category,
      sort: filters.sort,
      page: String(currentPage),
      pageSize: String(PAGE_SIZE)
    });

    const payload = await apiRequest(`./api/products/list.php?${query.toString()}`);
    products = Array.isArray(payload.products) ? payload.products : [];

    renderProducts();
    renderPagination(payload.pagination?.totalPages || 1);
    renderCart();
  } catch (error) {
    if (dom.productGrid) {
      dom.productGrid.innerHTML = "<p>Không tải được danh sách sản phẩm. Vui lòng thử lại sau.</p>";
    }
    showToast(error.message);
  }
}
// Hàm tải danh sách sản phẩm từ backend theo filter/pagination toàn DB.

function renderProducts() {
  if (!dom.productGrid) return;
  if (products.length === 0) {
    dom.productGrid.innerHTML = "<p>Không tìm thấy sản phẩm phù hợp.</p>";
    return;
  }

  dom.productGrid.innerHTML = products.map((product) => {
    const productId = Number(product.id);
    const detailUrl = `./index.php?p=product&id=${productId}`;
    const oldPrice = Number(product.oldPrice) > Number(product.price) ? `<del class="old">${VND.format(Number(product.oldPrice))}</del>` : "";
    return `
      <a class="product-card" href="${detailUrl}">
        ${discountBadge(product.price, product.oldPrice)}
        <img src="${escapeProductText(product.image)}" alt="" loading="lazy">
        <div class="product-content">
          <h3 class="product-title" title="${escapeProductText(product.name)}">${escapeProductText(product.name)}</h3>
          <p class="price-line">${oldPrice}<span class="current-price">${VND.format(Number(product.price))}</span></p>
        </div>
      </a>
    `;
  }).join("");
}
// Hàm render lưới sản phẩm theo dữ liệu backend.

function discountBadge(price, oldPrice) {
  const current = Number(price);
  const original = Number(oldPrice);
  if (!Number.isFinite(current) || !Number.isFinite(original) || current < 0 || original <= 0 || original <= current) return "";
  const percent = Math.round((original - current) / original * 100);
  return `<span class="discount-badge">Giảm ${percent > 0 ? percent : "&lt;1"}%</span>`;
}

function escapeProductText(value) {
  return String(value ?? "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[character]));
}

function renderPagination(totalPages) {
  if (!dom.pagination) return;
  if (totalPages <= 1) {
    dom.pagination.innerHTML = "";
    return;
  }

  const buttons = [];
  for (let page = 1; page <= totalPages; page += 1) {
    const activeClass = page === currentPage ? "active" : "";
    buttons.push(`<button class="pagination-btn ${activeClass}" data-page="${page}">${page}</button>`);
  }
  dom.pagination.innerHTML = buttons.join("");
}
// Hàm render danh sách nút phân trang cho grid sản phẩm.

function cartTotalQuantity() {
  return cart.reduce((sum, item) => sum + Number(item.quantity), 0);
}
// Hàm tính tổng số lượng sản phẩm trong giỏ.

function cartSubtotal() {
  return cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.quantity)), 0);
}
// Hàm tính tổng tiền tạm tính của giỏ hàng.

function syncCartSummary() {
  if (dom.cartCount) dom.cartCount.textContent = String(cartTotalQuantity());
  if (dom.cartSubtotal) dom.cartSubtotal.textContent = VND.format(cartSubtotal());
}
// Hàm đồng bộ số lượng và tổng tiền ra giao diện giỏ hàng.

function findProduct(productId) {
  return products.find((entry) => Number(entry.id) === Number(productId));
}
// Hàm tìm sản phẩm trong danh sách đã tải theo ID.

function renderCart() {
  if (!dom.cartItems) return;

  if (cart.length === 0) {
    dom.cartItems.innerHTML = "<p>Chưa có sản phẩm nào trong giỏ.</p>";
    syncCartSummary();
    return;
  }

  dom.cartItems.innerHTML = cart.map((item) => {
    const product = findProduct(item.id);
    const stock = product ? Number(product.stock) : Number(item.stock || 0);
    const canIncrease = Number(item.quantity) < stock;
    return `
      <article class="cart-item">
        <a href="./index.php?p=product&id=${item.id}"><img src="${item.image}" alt="${item.name}"></a>
        <div>
          <p><strong>${item.name}</strong></p>
          <p>${VND.format(Number(item.price))}</p>
          <div class="cart-controls">
            <button data-action="decrease" data-id="${item.id}">-</button>
            <span>${item.quantity}</span>
            <button data-action="increase" data-id="${item.id}" ${canIncrease ? "" : "disabled"}>+</button>
            <button data-action="remove" data-id="${item.id}">x</button>
          </div>
        </div>
      </article>
    `;
  }).join("");

  syncCartSummary();
}
// Hàm render danh sách item trong giỏ và các nút tăng/giảm/xóa.

function addToCart(productId) {
  const product = findProduct(productId);
  if (!product || Number(product.stock) <= 0) return;

  const existing = cart.find((item) => Number(item.id) === Number(product.id));
  if (existing) {
    if (Number(existing.quantity) >= Number(product.stock)) {
      setCartInlineError("Bạn đã đạt tối đa số lượng tồn kho.");
      return;
    }
    existing.quantity += 1;
  } else {
    cart.push({
      id: Number(product.id),
      name: String(product.name),
      sku: String(product.sku || ""),
      price: Number(product.price),
      image: String(product.image),
      quantity: 1,
      color: ""
    });
  }

  saveCart();
  setCartInlineError("");
  renderCart();
  showToast("Đã thêm vào giỏ hàng.");
}
// Hàm thêm sản phẩm vào giỏ, có chặn vượt tồn kho.

function updateCartItem(productId, action) {
  const item = cart.find((entry) => Number(entry.id) === Number(productId));
  const product = findProduct(productId);
  if (!item) return;

  if (action === "increase") {
    if (product && Number(item.quantity) >= Number(product.stock)) {
      setCartInlineError("Không thể tăng thêm vì đã chạm tồn kho.");
      return;
    }
    item.quantity += 1;
  }
  if (action === "decrease") item.quantity -= 1;
  if (action === "remove" || Number(item.quantity) <= 0) {
    cart = cart.filter((entry) => Number(entry.id) !== Number(productId));
  }

  saveCart();
  if (action !== "increase") setCartInlineError("");
  renderCart();
}
// Hàm cập nhật số lượng hoặc xóa item trong giỏ theo action.

function openCart() {
  if (!dom.cartDrawer) return;
  dom.cartDrawer.classList.add("open");
  dom.cartDrawer.setAttribute("aria-hidden", "false");
}
// Hàm mở panel giỏ hàng.

function closeCart() {
  if (!dom.cartDrawer) return;
  dom.cartDrawer.classList.remove("open");
  dom.cartDrawer.setAttribute("aria-hidden", "true");
}
// Hàm đóng panel giỏ hàng.

function openCheckout() {
  if (!dom.checkoutModal) return;
  if (isAdmin) {
    setCartInlineError("Tài khoản admin không thể đặt hàng.");
    showToast("Admin không thể đặt hàng.");
    return;
  }
  if (!isUserLoggedIn) {
    setCartInlineError("Bạn cần đăng nhập trước.");
    showToast("Bạn cần đăng nhập trước.");
    return;
  }
  if (cart.length === 0) {
    setCartInlineError("Giỏ hàng đang trống.");
    return;
  }
  setCartInlineError("");
  dom.checkoutModal.classList.add("open");
  dom.checkoutModal.setAttribute("aria-hidden", "false");
}
// Hàm mở modal thanh toán: chặn user chưa login và giỏ trống.

function closeCheckout() {
  if (!dom.checkoutModal) return;
  dom.checkoutModal.classList.remove("open");
  dom.checkoutModal.setAttribute("aria-hidden", "true");
}
// Hàm đóng modal thanh toán.

function showOrderSuccess(orderId) {
  const dialog = document.getElementById("orderSuccessDialog");
  if (!dialog) return;
  const image = document.getElementById("orderSuccessAnimation");
  const fallback = document.getElementById("orderSuccessFallback");
  const orderLink = document.getElementById("viewSuccessOrderLink");
  const id = Number(orderId);
  orderLink.href = Number.isSafeInteger(id) && id > 0
    ? `./index.php?p=user-order-detail&id=${id}`
    : "./index.php?p=user-history";
  image.hidden = true;
  fallback.hidden = false;
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    image.onload = () => {
      image.hidden = false;
      fallback.hidden = true;
    };
    image.onerror = () => {
      image.hidden = true;
      fallback.hidden = false;
    };
    image.src = `./assets/animations/success-confetti-fast.gif?play=${Date.now()}`;
  }
  document.body.classList.add("order-success-open");
  dialog.showModal();
}

async function submitOrder(event) {
  if (!dom.checkoutForm) return;
  event.preventDefault();
  if (orderSubmitting) return;

  if (isAdmin) {
    setCartInlineError("Tài khoản admin không thể đặt hàng.");
    return;
  }

  if (!isUserLoggedIn) {
    setCartInlineError("Bạn cần đăng nhập trước.");
    return;
  }

  const checkoutError = document.getElementById("checkoutInlineError");
  const submitButton = dom.checkoutForm.querySelector('button[type="submit"]');
  if (checkoutError) {
    checkoutError.textContent = "";
    checkoutError.classList.add("hidden");
  }
  orderSubmitting = true;
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = "Đang đặt hàng...";
  }
  const formData = new FormData(dom.checkoutForm);
  const payload = {
    customer: Object.fromEntries(formData.entries()),
    items: cart.map((item) => ({ id: item.id, quantity: item.quantity, color: item.color || "" }))
  };

  try {
    const data = await apiRequest("./api/orders/create.php", {
      method: "POST",
      body: JSON.stringify(payload)
    });

    cart = [];
    // A local storage error must not turn a successfully created order into a failure.
    try {
      saveCart();
      localStorage.setItem(LAST_ORDER_STORAGE_KEY, String(data.orderId || ""));
    } catch {
      // The server has already saved the order; keep the in-memory cart empty.
    }
    renderCart();
    closeCheckout();
    closeCart();
    dom.checkoutForm.reset();
    showOrderSuccess(data.orderId);
  } catch (error) {
    if (checkoutError) {
      checkoutError.textContent = error.message || "Đặt hàng thất bại.";
      checkoutError.classList.remove("hidden");
    }
  } finally {
    orderSubmitting = false;
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = "Xác nhận đặt hàng";
    }
  }
}
// Hàm gửi checkout lên backend để tạo đơn hàng thực.

async function fetchSessionStatus() {
  try {
    const [adminRes, userRes] = await Promise.all([
      apiRequest("./api/admin/me.php"),
      apiRequest("./api/user/me.php")
    ]);

    isAdmin = Boolean(adminRes.authenticated);
    isUserLoggedIn = Boolean(userRes.authenticated);
    const userId = Number(userRes.user?.id || 0);
    currentUserId = !isAdmin && isUserLoggedIn && userId > 0 ? userId : null;
    cart = loadCart();
    renderCart();

    if (isAdmin) {
      dom.adminPanelLink?.classList.add("hidden");
      dom.authLink?.classList.remove("hidden");
      dom.authLink?.setAttribute("href", "./index.php?p=admin-products");
      dom.authLink && (dom.authLink.textContent = `Xin chào, ${adminRes.user?.username || "admin"}`);
      dom.cartHistoryLink?.setAttribute("href", "./index.php?p=profile");
      if (dom.cartHistoryLink) dom.cartHistoryLink.textContent = "Hồ sơ";
      setCartInlineError("Tài khoản admin không thể đặt hàng.");
    } else {
      dom.adminPanelLink?.classList.add("hidden");
      if (isUserLoggedIn) {
        dom.authLink?.classList.remove("hidden");
        dom.authLink?.setAttribute("href", "./index.php?p=user-history");
        dom.authLink && (dom.authLink.textContent = `Xin chào, ${userRes.user?.username || "user"}`);
        dom.cartHistoryLink?.setAttribute("href", "./index.php?p=user-history");
        if (dom.cartHistoryLink) dom.cartHistoryLink.textContent = "Lịch sử";
      } else {
        dom.authLink?.classList.remove("hidden");
        dom.authLink?.setAttribute("href", "./index.php?p=login");
        dom.authLink && (dom.authLink.textContent = "Đăng nhập");
        dom.cartHistoryLink?.setAttribute("href", "./index.php?p=login");
        if (dom.cartHistoryLink) dom.cartHistoryLink.textContent = "Lịch sử";
      }
    }
  } catch {
    isAdmin = false;
    isUserLoggedIn = false;
    currentUserId = null;
    cart = [];
    renderCart();
  }
}
// Hàm đọc session user/admin để điều chỉnh menu và quyền checkout.

function bootEvents() {
  const successDialog = document.getElementById("orderSuccessDialog");
  const closeSuccess = () => successDialog?.close();
  document.getElementById("closeOrderSuccessBtn")?.addEventListener("click", closeSuccess);
  document.getElementById("continueShoppingBtn")?.addEventListener("click", closeSuccess);
  successDialog?.addEventListener("close", () => {
    document.body.classList.remove("order-success-open");
    const image = document.getElementById("orderSuccessAnimation");
    if (image) {
      image.onload = null;
      image.onerror = null;
      image.removeAttribute("src");
      image.hidden = true;
    }
    dom.openCartBtn?.focus();
  });
  dom.searchInput?.addEventListener("input", () => {
    if (lastSearchTimer) clearTimeout(lastSearchTimer);
    lastSearchTimer = setTimeout(() => {
      currentPage = 1;
      fetchProducts();
    }, 250);
  });
  dom.categoryFilter?.addEventListener("change", () => {
    currentPage = 1;
    fetchProducts();
  });
  dom.sortFilter?.addEventListener("change", () => {
    currentPage = 1;
    fetchProducts();
  });

  dom.pagination?.addEventListener("click", (event) => {
    const button = event.target.closest(".pagination-btn");
    if (!button) return;
    const page = Number(button.dataset.page || "1");
    if (!Number.isFinite(page) || page <= 0) return;
    currentPage = page;
    fetchProducts();
  });

  dom.cartItems?.addEventListener("click", (event) => {
    const button = event.target.closest("button");
    if (!button) return;
    updateCartItem(Number(button.dataset.id), button.dataset.action);
  });

  dom.openCartBtn?.addEventListener("click", openCart);
  dom.closeCartBtn?.addEventListener("click", closeCart);
  dom.checkoutBtn?.addEventListener("click", openCheckout);
  dom.closeCheckoutBtn?.addEventListener("click", closeCheckout);
  dom.checkoutForm?.addEventListener("submit", submitOrder);

  dom.menuBtn?.addEventListener("click", () => {
    dom.nav?.classList.toggle("open");
  });

  dom.checkoutModal?.addEventListener("click", (event) => {
    if (event.target === dom.checkoutModal) closeCheckout();
  });
}
// Hàm đăng ký toàn bộ sự kiện tương tác trên trang sản phẩm.

async function init() {
  restoreFilterState();
  renderCart();
  bootEvents();
  await fetchSessionStatus();
  const params = new URLSearchParams(window.location.search);
  if (params.get("checkout") === "1") {
    openCart();
    openCheckout();
    params.delete("checkout");
    window.history.replaceState(null, "", `${window.location.pathname}?${params.toString()}${window.location.hash}`);
  }
  await fetchProducts();
}
// Hàm khởi tạo màn hình: bind event, auth state, dữ liệu sản phẩm.

init();



