<header class="happy-navbar sticky top-0 z-50 bg-white shadow-sm">
  <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
    <div class="happy-navbar__row">
      <a href="{{ url('/') }}" class="happy-navbar__brand">
        <span class="happy-navbar__logo-wrap">
          <svg class="happy-navbar__logo-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4"/>
          </svg>
        </span>
        <span class="happy-navbar__brand-text">Happy</span>
      </a>

      <div class="happy-navbar__search navbar-search">
        <span class="happy-navbar__search-icon" aria-hidden="true">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
          </svg>
        </span>
        <input id="search-input" type="text" placeholder="Tìm kiếm trà sữa, cà phê..." class="happy-navbar__search-input" />
      </div>

      <nav class="happy-navbar__desktop-nav navbar-desktop-nav" aria-label="Điều hướng chính">
        <a href="{{ url('/') }}" class="nav-link active">Trang chủ</a>

        <div class="relative" id="products-menu">
          <button id="products-btn" class="nav-link happy-navbar__menu-btn" type="button" aria-expanded="false">
            Sản phẩm
            <svg class="h-4 w-4 transition-transform duration-200" id="products-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="products-dropdown" class="dropdown-menu hidden happy-navbar__dropdown">
            <a href="/products?category=ca-phe" class="happy-navbar__dropdown-item">☕ Cà phê</a>
            <a href="/products?category=tra-sua" class="happy-navbar__dropdown-item">🧋 Trà sữa</a>
            <a href="/products?category=tra-trai-cay" class="happy-navbar__dropdown-item">🍋 Trà trái cây</a>
          </div>
        </div>

        <a href="#" class="nav-link">Khuyến mãi</a>
        <a href="#" class="nav-link">Liên hệ</a>
      </nav>

      <div class="happy-navbar__actions">
        <button id="mobile-search-btn" type="button" class="happy-navbar__icon-btn navbar-mobile-search-btn" aria-label="Tìm kiếm">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
          </svg>
        </button>

        <div class="relative" id="account-menu">
          <button id="account-btn" type="button" class="happy-navbar__icon-btn" aria-label="Tài khoản">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </button>
          <div id="account-dropdown" class="dropdown-menu hidden happy-navbar__dropdown happy-navbar__dropdown--right">
            <a href="{{ route('login') }}" class="happy-navbar__dropdown-item">Đăng nhập</a>
            <a href="{{ route('register') }}" class="happy-navbar__dropdown-item">Đăng ký</a>
          </div>
        </div>

        <button id="cart-btn" type="button" class="happy-navbar__icon-btn" aria-label="Giỏ hàng">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4"/>
          </svg>
          <span id="cart-badge">3</span>
        </button>

        <button id="hamburger" type="button" class="happy-navbar__icon-btn navbar-hamburger" aria-label="Mở menu">
          <svg id="ham-icon" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg id="close-icon" class="h-5 w-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <div id="mobile-search-bar" class="happy-navbar__mobile-search">
      <div class="happy-navbar__search">
        <span class="happy-navbar__search-icon" aria-hidden="true">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
          </svg>
        </span>
        <input type="text" placeholder="Tìm kiếm trà sữa, cà phê..." class="happy-navbar__search-input" />
      </div>
    </div>

    <div id="mobile-menu" class="happy-navbar__mobile-menu">
      <nav class="happy-navbar__mobile-nav" aria-label="Điều hướng di động">
        <a href="{{ url('/') }}" class="happy-navbar__mobile-link is-active">🏠 Trang chủ</a>
        <a href="/products?category=" class="happy-navbar__mobile-link">🛍️ Sản phẩm</a>
        <a href="#" class="happy-navbar__mobile-link">🏷️ Khuyến mãi</a>
        <a href="#" class="happy-navbar__mobile-link">📞 Liên hệ</a>
      </nav>
    </div>
  </div>
</header>

@push('scripts')
<script>
  (function () {
    const $ = (id) => document.getElementById(id);

    const closeAll = () => {
      ['products-dropdown', 'account-dropdown'].forEach((id) => {
        const el = $(id);
        if (!el) return;
        el.classList.remove('open');
        el.classList.add('hidden');
      });
      const chev = $('products-chevron');
      if (chev) chev.style.transform = '';
      const btn = $('products-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    const toggleDropdown = (btn, dropdown, chevron) => {
      if (!btn || !dropdown) return;
      const willOpen = dropdown.classList.contains('hidden');
      closeAll();
      if (willOpen) {
        dropdown.classList.remove('hidden');
        dropdown.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
      }
    };

    $('products-btn')?.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleDropdown($('products-btn'), $('products-dropdown'), $('products-chevron'));
    });

    $('account-btn')?.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleDropdown($('account-btn'), $('account-dropdown'));
    });

    document.addEventListener('click', closeAll);
    ['products-dropdown', 'account-dropdown'].forEach((id) => {
      $(id)?.addEventListener('click', (e) => e.stopPropagation());
    });

    $('hamburger')?.addEventListener('click', () => {
      const menu = $('mobile-menu');
      const ham = $('ham-icon');
      const close = $('close-icon');
      if (!menu || !ham || !close) return;
      const isOpen = menu.classList.toggle('open');
      ham.classList.toggle('hidden', isOpen);
      close.classList.toggle('hidden', !isOpen);
    });

    $('mobile-search-btn')?.addEventListener('click', () => {
      const bar = $('mobile-search-bar');
      if (!bar) return;
      bar.classList.toggle('open');
      const input = bar.querySelector('input');
      if (bar.classList.contains('open') && input) input.focus();
    });

    $('cart-btn')?.addEventListener('click', () => {
      const badge = $('cart-badge');
      if (!badge) return;
      badge.classList.remove('badge-pop');
      void badge.offsetWidth;
      badge.classList.add('badge-pop');
    });
  })();
</script>
@endpush
