// Shared mobile navigation; reuses the page's existing account and cart state.
(() => {
  function initMobileUI() {
    const header = document.querySelector('.header');
    const nav = header?.querySelector('.nav');
    if (!header || !nav) return;
    const narrow = window.matchMedia('(max-width: 760px)');
    nav.id ||= 'primaryNavigation';
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'mobile-menu-toggle';
    toggle.setAttribute('aria-label', 'Mở menu điều hướng');
    toggle.setAttribute('aria-controls', nav.id);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
    header.appendChild(toggle);
    const setMenu = (open) => {
      nav.classList.toggle('is-mobile-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Đóng menu điều hướng' : 'Mở menu điều hướng');
    };
    toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('click', (event) => {
      if (!header.contains(event.target)) setMenu(false);
      if (event.target instanceof Element && event.target.closest('.nav a')) setMenu(false);
    });

    const icons = {
      home: '<path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/>',
      products: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
      cart: '<path d="M3 4h2l2.5 11h11L21 7H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
      account: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
      orders: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v4H9zM9 12h6M9 16h4"/>',
    };
    const icon = (name) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name]}</svg>`;
    const dock = document.createElement('nav');
    dock.className = 'mobile-dock';
    dock.setAttribute('aria-label', 'Điều hướng nhanh');
    const entries = [
      ['home', 'Trang chủ', './index.php?p=home'],
      ['products', 'Sản phẩm', './index.php?p=products'],
      ['cart', 'Giỏ hàng', './index.php?p=products&cart=1'],
      ['account', 'Tài khoản', './index.php?p=login'],
    ];
    for (const [name, label, href] of entries) {
      const link = document.createElement('a');
      link.href = href;
      link.dataset.mobileNav = name;
      link.innerHTML = `${icon(name)}<span>${label}</span>`;
      dock.appendChild(link);
    }
    document.body.appendChild(dock);
    const page = new URLSearchParams(location.search).get('p') || 'home';
    const auth = document.getElementById('authLink');
    const cartLink = dock.querySelector('[data-mobile-nav="cart"]');
    const accountLink = dock.querySelector('[data-mobile-nav="account"]');
    const productLink = dock.querySelector('[data-mobile-nav="products"]');
    let admin = false;
    const syncCartCount = () => {
      if (admin) return;
      const count = Number(document.getElementById('cartCount')?.textContent || 0);
      let badge = cartLink.querySelector('.mobile-cart-count');
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'mobile-cart-count';
        badge.setAttribute('aria-hidden', 'true');
        cartLink.appendChild(badge);
      }
      badge.hidden = count < 1;
      badge.textContent = count > 99 ? '99+' : String(count);
      cartLink.setAttribute('aria-label', count > 0 ? `Giỏ hàng, ${count} sản phẩm` : 'Giỏ hàng');
    };
    const syncAccount = () => {
      const href = auth?.getAttribute('href') || '';
      const signedIn = (auth?.textContent || '').trim().startsWith('Xin chào') || Boolean(nav.querySelector('a[href*="p=logout"]'));
      admin = page.startsWith('admin-') || href.includes('p=admin-') || Boolean(nav.querySelector('a[href*="p=admin-"]'));
      accountLink.href = signedIn || admin ? './index.php?p=profile' : './index.php?p=login';
      productLink.href = admin ? './index.php?p=admin-products' : './index.php?p=products';
      cartLink.href = admin ? './index.php?p=admin-orders' : './index.php?p=products&cart=1';
      cartLink.innerHTML = `${icon(admin ? 'orders' : 'cart')}<span>${admin ? 'Đơn hàng' : 'Giỏ hàng'}</span>`;
      if (admin) cartLink.removeAttribute('aria-label');
      else syncCartCount();
      const selected = page === 'home' ? 'home' : page.includes('order') && admin ? 'cart' : ['products', 'product', 'admin-products', 'admin-add-product', 'admin-edit-product'].includes(page) ? 'products' : 'account';
      for (const link of dock.children) {
        if (link.dataset.mobileNav === selected) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
      }
    };
    syncAccount();
    if (auth) new MutationObserver(syncAccount).observe(auth, { childList: true, subtree: true, attributes: true, attributeFilter: ['href'] });
    const cartCount = document.getElementById('cartCount');
    if (cartCount) new MutationObserver(syncCartCount).observe(cartCount, { childList: true, subtree: true, characterData: true });

    const drawer = document.getElementById('cartDrawer');
    const checkout = document.getElementById('checkoutModal');
    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'mobile-cart-backdrop';
    backdrop.setAttribute('aria-label', 'Đóng giỏ hàng');
    backdrop.tabIndex = -1;
    backdrop.hidden = true;
    document.body.appendChild(backdrop);
    backdrop.addEventListener('click', () => document.getElementById('closeCartBtn')?.click());
    let restoreFocus = null;
    let priorOverlay = null;
    const syncOverlays = () => {
      const cartOpen = Boolean(drawer?.classList.contains('open'));
      const checkoutOpen = Boolean(checkout?.classList.contains('open'));
      if (drawer) drawer.inert = !cartOpen;
      if (checkout) checkout.inert = !checkoutOpen;
      const active = narrow.matches ? (checkoutOpen ? checkout : cartOpen ? drawer : null) : null;
      document.body.classList.toggle('mobile-overlay-open', Boolean(active));
      backdrop.hidden = !narrow.matches || !cartOpen;
      if (drawer) {
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-label', 'Giỏ hàng của bạn');
        drawer.setAttribute('aria-modal', String(narrow.matches && cartOpen));
      }
      if (checkout) {
        checkout.setAttribute('role', 'dialog');
        checkout.setAttribute('aria-label', 'Thông tin thanh toán');
        checkout.setAttribute('aria-modal', String(checkoutOpen));
      }
      if (active && active !== priorOverlay) {
        if (!priorOverlay) restoreFocus = document.activeElement;
        active.querySelector('button, input, a[href]')?.focus();
      } else if (!active && priorOverlay && restoreFocus?.isConnected) restoreFocus.focus();
      priorOverlay = active;
    };
    for (const panel of [drawer, checkout]) if (panel) new MutationObserver(syncOverlays).observe(panel, { attributes: true, attributeFilter: ['class'] });
    syncOverlays();
    cartLink.addEventListener('click', (event) => {
      const opener = document.getElementById('openCartBtn');
      if (!admin && opener && drawer) { event.preventDefault(); opener.click(); }
    });
    if (new URLSearchParams(location.search).get('cart') === '1' && drawer) document.getElementById('openCartBtn')?.click();
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        if (priorOverlay === checkout) document.getElementById('closeCheckoutBtn')?.click();
        else if (priorOverlay === drawer) document.getElementById('closeCartBtn')?.click();
        if (toggle.getAttribute('aria-expanded') === 'true') { setMenu(false); toggle.focus(); }
      }
      if (event.key !== 'Tab' || !priorOverlay) return;
      const focusable = [...priorOverlay.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select, textarea')].filter((el) => el.getClientRects().length);
      const first = focusable[0], last = focusable.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    });
    narrow.addEventListener('change', () => { setMenu(false); syncOverlays(); });

    for (const table of document.querySelectorAll('.table')) {
      const headings = [...table.querySelectorAll('thead th')];
      if (!headings.length) continue;
      table.classList.add('responsive-table');
      table.setAttribute('role', 'table');
      for (const row of table.querySelectorAll('tbody tr')) {
        row.setAttribute('role', 'row');
        [...row.children].forEach((cell, i) => {
          cell.setAttribute('role', 'cell');
          if (!cell.hasAttribute('colspan')) cell.dataset.label = headings[i]?.textContent.trim() || '';
        });
      }
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initMobileUI, { once: true });
  else initMobileUI();
})();
