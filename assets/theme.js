// Ghi chú: File script xử lý tương tác giao diện và gọi API.
(function () {
  const KEY = "stkGearsTheme";
  const root = document.documentElement;
  let openDropdown = null;

  function setTheme(theme) {
    root.setAttribute("data-theme", theme);
    try { localStorage.setItem(KEY, theme); } catch {}
    const btn = document.getElementById("themeToggleBtn");
    if (btn) {
      btn.setAttribute("aria-label", theme === "dark" ? "Chuyển sang giao diện sáng" : "Chuyển sang giao diện tối");
      btn.textContent = theme === "dark" ? "☀" : "☾";
    }
  }
  // Hàm áp dụng theme hiện tại lên toàn trang và cập nhật icon nút.

  function initTheme() {
    let theme = "light";
    try {
      const saved = localStorage.getItem(KEY);
      if (saved === "dark" || saved === "light") theme = saved;
    } catch {}
    setTheme(theme);
  }
  // Hàm lấy theme đã lưu và khởi tạo giao diện ban đầu.

  function closeAllDropdowns() {
    document.querySelectorAll(".nav-dropdown.open").forEach((el) => el.classList.remove("open"));
    openDropdown = null;
  }
  // Hàm đóng tất cả dropdown đang mở.

  function initNavDropdown() {
    const productLinks = document.querySelectorAll('.nav a[data-menu="catalog"], .nav a[href*="p=products"][data-menu-fallback="true"]');
    if (productLinks.length === 0) return;

    productLinks.forEach((link) => {
      if (link.closest(".nav-dropdown")) return;

      const wrapper = document.createElement("div");
      wrapper.className = "nav-dropdown";

      const trigger = document.createElement("button");
      trigger.type = "button";
      trigger.className = "nav-dropdown-trigger";
      trigger.setAttribute("aria-expanded", "false");
      trigger.textContent = link.textContent || "Danh mục";

      const menu = document.createElement("div");
      menu.className = "nav-dropdown-menu mega-menu";
      menu.innerHTML = [
        '<div class="nav-dropdown-group">',
        '<p class="nav-dropdown-title">Bàn phím</p>',
        '<a href="./index.php?p=products&category=keyboard_mech">Bàn phím cơ</a>',
        '<a href="./index.php?p=products&category=keyboard_hall">Bàn phím Hall Effect</a>',
        '</div>',
        '<div class="nav-dropdown-group">',
        '<p class="nav-dropdown-title">Chuột</p>',
        '<a href="./index.php?p=products&category=mouse_wireless">Chuột không dây</a>',
        '<a href="./index.php?p=products&category=mouse_wired">Chuột có dây</a>',
        '</div>',
        '<div class="nav-dropdown-group">',
        '<p class="nav-dropdown-title">Accessory</p>',
        '<a href="./index.php?p=products&category=pad_mouse">Pad chuột</a>',
        '<a href="./index.php?p=products&category=accessory_switch">Switch</a>',
        '<a href="./index.php?p=products&category=accessory_keycap">Keycap</a>',
        '</div>'
      ].join("");

      link.replaceWith(wrapper);
      wrapper.appendChild(trigger);
      wrapper.appendChild(menu);

      let closeTimer = null;
      wrapper.addEventListener("mouseenter", function () {
        if (closeTimer) window.clearTimeout(closeTimer);
        closeAllDropdowns();
        wrapper.classList.add("open");
        trigger.setAttribute("aria-expanded", "true");
        openDropdown = wrapper;
      });
      wrapper.addEventListener("mouseleave", function () {
        closeTimer = window.setTimeout(function () {
          wrapper.classList.remove("open");
          trigger.setAttribute("aria-expanded", "false");
          if (openDropdown === wrapper) openDropdown = null;
        }, 220);
      });

      trigger.addEventListener("click", function (event) {
        event.stopPropagation();
        const isTouchLike = window.matchMedia("(hover: none)").matches;
        if (!isTouchLike) return;
        const willOpen = !wrapper.classList.contains("open");
        closeAllDropdowns();
        wrapper.classList.toggle("open", willOpen);
        trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
        openDropdown = willOpen ? wrapper : null;
      });
    });

    document.addEventListener("click", function (event) {
      if (!openDropdown) return;
      if (event.target instanceof Node && openDropdown.contains(event.target)) return;
      closeAllDropdowns();
    });
  }
  // Hàm gắn dropdown danh mục dưới link Sản phẩm cho toàn site.

  function initProfileDropdown() {
    const authLink = document.getElementById("authLink");
    if (!authLink || authLink.closest(".profile-dropdown")) return;

    const sync = function () {
      const text = (authLink.textContent || "").trim();
      const href = authLink.getAttribute("href") || "";
      const isUserLogged = text.startsWith("Xin chào");

      if (!isUserLogged) {
        const wrapped = authLink.closest(".profile-dropdown");
        if (wrapped) {
          wrapped.parentNode?.insertBefore(authLink, wrapped);
          wrapped.remove();
          authLink.classList.remove("nav-dropdown-trigger");
          authLink.classList.add("ghost");
        }
        return;
      }

      if (authLink.closest(".profile-dropdown")) {
        const trigger = authLink.closest(".profile-dropdown").querySelector(".nav-dropdown-trigger");
        if (trigger) trigger.textContent = text;
        return;
      }

      const wrapper = document.createElement("div");
      wrapper.className = "nav-dropdown profile-dropdown";

      const trigger = authLink;
      trigger.classList.remove("hidden");
      trigger.classList.add("nav-dropdown-trigger");
      trigger.classList.add("ghost");
      trigger.setAttribute("aria-expanded", "false");
      trigger.textContent = text;
      trigger.href = href || "#";

      const menu = document.createElement("div");
      menu.className = "nav-dropdown-menu";
      const isAdminProfile = href.includes("p=admin-products") || href.includes("p=admin-orders");
      menu.innerHTML = isAdminProfile
        ? [
          '<a href="./index.php?p=profile">Hồ sơ tài khoản</a>',
          '<a href="./index.php?p=admin-products">Quản trị sản phẩm</a>',
          '<a href="./index.php?p=admin-orders">Đơn hàng</a>',
          '<a href="./index.php?p=logout">Đăng xuất</a>'
        ].join("")
        : [
          '<a href="./index.php?p=profile">Hồ sơ tài khoản</a>',
          '<a href="./index.php?p=user-history">Lịch sử đơn hàng</a>',
          '<a href="./index.php?p=user-password">Đổi mật khẩu</a>',
          '<a href="./index.php?p=logout">Đăng xuất</a>'
        ].join("");

      authLink.parentNode?.insertBefore(wrapper, authLink);
      wrapper.appendChild(trigger);
      wrapper.appendChild(menu);

      let closeTimer = null;
      wrapper.addEventListener("mouseenter", function () {
        if (closeTimer) window.clearTimeout(closeTimer);
        closeAllDropdowns();
        wrapper.classList.add("open");
        trigger.setAttribute("aria-expanded", "true");
        openDropdown = wrapper;
      });
      wrapper.addEventListener("mouseleave", function () {
        closeTimer = window.setTimeout(function () {
          wrapper.classList.remove("open");
          trigger.setAttribute("aria-expanded", "false");
          if (openDropdown === wrapper) openDropdown = null;
        }, 220);
      });

      trigger.addEventListener("click", function (event) {
        event.stopPropagation();
        const isTouchLike = window.matchMedia("(hover: none)").matches;
        if (!isTouchLike) return;
        const willOpen = !wrapper.classList.contains("open");
        closeAllDropdowns();
        wrapper.classList.toggle("open", willOpen);
        trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
        openDropdown = willOpen ? wrapper : null;
      });
    };

    sync();
    // Tránh vòng lặp observer gây đơ UI: đồng bộ theo các mốc thời gian ngắn sau khi auth script cập nhật header.
    window.setTimeout(sync, 250);
    window.setTimeout(sync, 900);
    window.setTimeout(sync, 1800);
  }
  // Hàm tạo submenu hồ sơ khi user đã đăng nhập.

  function initBackToTopButton() {
    const button = document.createElement("button");
    button.type = "button";
    button.id = "backToTopBtn";
    button.className = "back-to-top";
    button.setAttribute("aria-label", "Lên đầu trang");
    button.textContent = "↑";
    document.body.appendChild(button);

    const toggleVisibility = function () {
      if (window.scrollY > 280) {
        button.classList.add("show");
      } else {
        button.classList.remove("show");
      }
    };

    window.addEventListener("scroll", toggleVisibility, { passive: true });
    toggleVisibility();

    button.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }
  // Hàm thêm nút quay lên đầu trang ở góc phải dưới.

  document.addEventListener("DOMContentLoaded", function () {
    initTheme();
    initNavDropdown();
    initProfileDropdown();
    initBackToTopButton();

    const btn = document.getElementById("themeToggleBtn");
    if (btn) {
      btn.addEventListener("click", function () {
        const next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
        setTheme(next);
      });
    }
  });
})();



