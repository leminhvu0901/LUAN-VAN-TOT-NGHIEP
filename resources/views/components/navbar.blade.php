<header class="happy-navbar sticky top-0 z-50 bg-white shadow-sm" id="main-navbar">
    <div class="container-fluid px-8">
        <div class="happy-navbar__row">
            {{-- logo --}}
            <a href="{{ url('/') }}" class="happy-navbar__brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2"
                    stroke="#10b981" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <circle cx="6" cy="19" r="2"></circle>
                    <circle cx="17" cy="19" r="2"></circle>
                    <path d="M17 17h-11v-14h-2"></path>
                    <path d="M6 5l14 1l-1 7h-13"></path>
                </svg>
                <span class="happy-navbar__brand-text"><span class="brand-happy">Happy</span></span>
            </a>

            <div class="l-nav-search hidden md:flex">
                <span class="happy-navbar__search-icon happy-navbar__search-icon--desktop" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                    </svg>
                </span>
                <input id="search-input" type="text" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="l-nav-search-input" />
            </div>

            <div class="happy-navbar__actions-wrapper">
                <nav class="happy-navbar__desktop-nav navbar-desktop-nav" aria-label="Điều hướng chính">
                    <a href="{{ url('/') }}" class="nav-link nav-link--active">Trang chủ</a>
                    <a href="/products" class="nav-link">Sản phẩm</a>
                    <a href="#" class="nav-link">Khuyến mãi</a>
                    <a href="#" class="nav-link">Liên hệ</a>
                </nav>

                <div class="happy-navbar__actions">
                    {{-- nút yêu thích --}}
                    <button id="wishlist-btn" type="button" class="happy-navbar__icon-btn"
                        aria-label="Sản phẩm yêu thích">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        <span id="wishlist-badge">2</span>
                    </button>

                    {{-- dang nhap --}}
                    <button id="login-btn" type="button" class="happy-navbar__icon-btn" aria-label="Đăng nhập">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </button>

                    <button id="cart-btn" type="button" class="happy-navbar__icon-btn" aria-label="Giỏ hàng">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                        </svg>
                        <span id="cart-badge">3</span>
                    </button>

                    <button id="hamburger" type="button" class="happy-navbar__icon-btn navbar-hamburger"
                        aria-label="Mở menu">
                        <svg id="ham-icon" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg id="close-icon" class="h-6 w-6 hidden" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-search-bar" class="happy-navbar__mobile-search">
            <div class="happy-navbar__search">
                <span class="happy-navbar__search-icon" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                    </svg>
                </span>
                <input type="text" placeholder="Tìm kiếm trà sữa, cà phê..." class="happy-navbar__search-input" />
            </div>
        </div>

        <div id="mobile-menu" class="happy-navbar__mobile-menu">
            <nav class="happy-navbar__mobile-nav" aria-label="Điều hướng di động">
                <a href="{{ url('/') }}" class="happy-navbar__mobile-link is-active"> Trang chủ</a>
                <a href="/products?category=" class="happy-navbar__mobile-link"> Sản phẩm</a>
                <a href="#" class="happy-navbar__mobile-link"> Khuyến mãi</a>
                <a href="#" class="happy-navbar__mobile-link"> Liên hệ</a>
            </nav>
        </div>
    </div>
</header>

{{-- ===== WISHLIST DRAWER ===== --}}
{{-- Overlay --}}
<div id="wishlist-overlay" aria-hidden="true"></div>

{{-- Drawer panel --}}
<aside id="wishlist-drawer" role="dialog" aria-modal="true" aria-label="Danh sách yêu thích">

    {{-- Header --}}
    <div class="wl-drawer__header">
        <div>
            <h2 class="wl-drawer__title">Sản phẩm yêu thích</h2>
            <p class="wl-drawer__subtitle">2 sản phẩm đã lưu</p>
        </div>
        <button id="wishlist-close" class="wl-drawer__close-btn" aria-label="Đóng">
            <svg width="16" height="16" fill="none" stroke="#374151" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Danh sách sản phẩm yêu thích --}}
    <div class="wl-drawer__body" id="wishlist-list">

        {{-- Item 1 --}}
        <div class="wl-item">
            <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" alt="Cà phê sữa đá"
                class="wl-item__img">
            <div class="wl-item__info">
                <p class="wl-item__name">Cà phê sữa đá</p>
                <div class="wl-item__rating">
                    <span class="wl-item__stars">★★★★★</span>
                    <span class="wl-item__rating-value">4.8</span>
                </div>
                <span class="wl-item__price">29.000đ</span>
            </div>
            <div class="wl-item__actions">
                <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn">
                    <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <button title="Thêm vào giỏ" class="wl-item__cart-btn">
                    <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Item 2 --}}
        <div class="wl-item">
            <img src="{{ asset('images/products/matcha-latte.jpg') }}" alt="Matcha Latte"
                class="wl-item__img">
            <div class="wl-item__info">
                <p class="wl-item__name">Matcha Latte</p>
                <div class="wl-item__rating">
                    <span class="wl-item__stars">★★★★★</span>
                    <span class="wl-item__rating-value">4.7</span>
                </div>
                <span class="wl-item__price">39.000đ</span>
            </div>
            <div class="wl-item__actions">
                <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn">
                    <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <button title="Thêm vào giỏ" class="wl-item__cart-btn">
                    <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="wl-drawer__footer">
        <a href="/products" class="wl-drawer__continue-btn">Tiếp tục mua</a>
        <button class="wl-drawer__add-all-btn">Thêm tất cả vào giỏ</button>
    </div>
</aside>

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

            /* ---- Wishlist drawer ---- */
            const openWishlist = () => {
                const drawer = $('wishlist-drawer');
                const overlay = $('wishlist-overlay');
                if (!drawer || !overlay) return;
                overlay.style.display = 'block';
                requestAnimationFrame(() => {
                    drawer.style.transform = 'translateX(0)';
                    overlay.style.opacity = '1';
                });
                document.body.style.overflow = 'hidden';
            };
            const closeWishlist = () => {
                const drawer = $('wishlist-drawer');
                const overlay = $('wishlist-overlay');
                if (!drawer || !overlay) return;
                drawer.style.transform = 'translateX(100%)';
                overlay.style.opacity = '0';
                setTimeout(() => { overlay.style.display = 'none'; }, 320);
                document.body.style.overflow = '';
            };
            $('wishlist-btn')?.addEventListener('click', openWishlist);
            $('wishlist-close')?.addEventListener('click', closeWishlist);
            $('wishlist-overlay')?.addEventListener('click', closeWishlist);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeWishlist(); });

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