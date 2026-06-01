<header class="happy-navbar sticky top-0 z-50 bg-white shadow-sm">
    <div class="container-fluid px-8">
        <div class="happy-navbar__row" style="justify-content: space-between;">
            {{-- logo --}}
            <a href="{{ url('/') }}" class="happy-navbar__brand"
                style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #111827;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2"
                    stroke="#10b981" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <circle cx="6" cy="19" r="2"></circle>
                    <circle cx="17" cy="19" r="2"></circle>
                    <path d="M17 17h-11v-14h-2"></path>
                    <path d="M6 5l14 1l-1 7h-13"></path>
                </svg>
                <span style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;">Happy</span>
            </a>

            <div class="l-nav-search hidden md:flex">
                <span class="happy-navbar__search-icon" aria-hidden="true" style="color: #9ca3af;">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                    </svg>
                </span>
                <input id="search-input" type="text" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="l-nav-search-input" />
            </div>

            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <nav class="happy-navbar__desktop-nav navbar-desktop-nav" aria-label="Điều hướng chính"
                    style="margin-right: 1rem;">
                    <a href="{{ url('/') }}" class="nav-link active" style="font-weight: 600; color: #10b981;">Trang
                        chủ</a>
                    <a href="/products" class="nav-link">Sản phẩm</a>
                    <a href="#" class="nav-link">Khuyến mãi</a>
                    <a href="#" class="nav-link">Liên hệ</a>
                </nav>

                <div class="happy-navbar__actions">
                    {{-- nút yêu thích --}}
                    <button id="wishlist-btn" type="button" class="happy-navbar__icon-btn"
                        aria-label="Sản phẩm yêu thích" style="position:relative;">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        <span id="wishlist-badge"
                            style="background:#ef4444;color:white;position:absolute;top:-4px;right:-6px;font-size:0.65rem;font-weight:700;min-width:17px;height:17px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 3px;">2</span>
                    </button>

                    {{-- dang nhap --}}
                    <button id="login-btn" type="button" class="happy-navbar__icon-btn" aria-label="Đăng nhập">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </button>

                    <button id="cart-btn" type="button" class="happy-navbar__icon-btn" aria-label="Giỏ hàng">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                        </svg>
                        <span id="cart-badge" style="background: #10b981; color: white;">3</span>
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
<div id="wishlist-overlay"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:998;transition:opacity 0.3s;"
    aria-hidden="true"></div>

{{-- Drawer panel --}}
<aside id="wishlist-drawer" role="dialog" aria-modal="true" aria-label="Danh sách yêu thích" style="position:fixed;top:0;right:0;height:100%;width:380px;max-width:95vw;
              background:#fff;z-index:999;box-shadow:-4px 0 24px rgba(0,0,0,0.12);
              display:flex;flex-direction:column;
              transform:translateX(100%);transition:transform 0.32s cubic-bezier(.4,0,.2,1);">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:1.25rem 1.5rem;border-bottom:1px solid #f0f0f0;">
        <div>
            <h2 style="font-size:1.15rem;font-weight:800;color:#111827;margin:0;">Sản phẩm yêu thích</h2>
            <p style="font-size:0.8rem;color:#6b7280;margin:0.1rem 0 0;">2 sản phẩm đã lưu</p>
        </div>
        <button id="wishlist-close" style="width:32px;height:32px;border:none;background:#f3f4f6;border-radius:50%;
                       cursor:pointer;display:flex;align-items:center;justify-content:center;
                       transition:background 0.2s;" onmouseover="this.style.background='#e5e7eb'"
            onmouseout="this.style.background='#f3f4f6'" aria-label="Đóng">
            <svg width="16" height="16" fill="none" stroke="#374151" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Danh sách sản phẩm yêu thích --}}
    <div style="flex:1;overflow-y:auto;padding:1rem 1.5rem;" id="wishlist-list">

        {{-- Item 1 --}}
        <div class="wl-item"
            style="display:flex;align-items:center;gap:1rem;padding:0.85rem 0;border-bottom:1px solid #f5f5f5;">
            <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" alt="Cà phê sữa đá"
                style="width:64px;height:64px;object-fit:cover;border-radius:8px;flex-shrink:0;background:#f3f4f6;">
            <div style="flex:1;min-width:0;">
                <p style="font-size:0.875rem;font-weight:700;color:#111827;margin:0 0 0.2rem;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Cà phê sữa đá</p>
                <div style="display:flex;align-items:center;gap:3px;margin-bottom:0.25rem;">
                    <span style="color:#f59e0b;font-size:0.75rem;">★★★★★</span>
                    <span style="font-size:0.72rem;color:#6b7280;">4.8</span>
                </div>
                <span style="font-size:0.9rem;font-weight:800;color:#10b981;">29.000đ</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
                <button title="Xóa khỏi yêu thích" style="width:28px;height:28px;border:none;background:#fff0f0;border-radius:50%;
                               cursor:pointer;display:flex;align-items:center;justify-content:center;
                               transition:background 0.2s;" onmouseover="this.style.background='#fee2e2'"
                    onmouseout="this.style.background='#fff0f0'">
                    <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <button title="Thêm vào giỏ" style="width:28px;height:28px;border:none;background:#ecfdf5;border-radius:50%;
                               cursor:pointer;display:flex;align-items:center;justify-content:center;
                               transition:background 0.2s;" onmouseover="this.style.background='#d1fae5'"
                    onmouseout="this.style.background='#ecfdf5'">
                    <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Item 2 --}}
        <div class="wl-item"
            style="display:flex;align-items:center;gap:1rem;padding:0.85rem 0;border-bottom:1px solid #f5f5f5;">
            <img src="{{ asset('images/products/matcha-latte.jpg') }}" alt="Matcha Latte"
                style="width:64px;height:64px;object-fit:cover;border-radius:8px;flex-shrink:0;background:#f3f4f6;">
            <div style="flex:1;min-width:0;">
                <p style="font-size:0.875rem;font-weight:700;color:#111827;margin:0 0 0.2rem;
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Matcha Latte</p>
                <div style="display:flex;align-items:center;gap:3px;margin-bottom:0.25rem;">
                    <span style="color:#f59e0b;font-size:0.75rem;">★★★★★</span>
                    <span style="font-size:0.72rem;color:#6b7280;">4.7</span>
                </div>
                <span style="font-size:0.9rem;font-weight:800;color:#10b981;">39.000đ</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;">
                <button title="Xóa khỏi yêu thích" style="width:28px;height:28px;border:none;background:#fff0f0;border-radius:50%;
                               cursor:pointer;display:flex;align-items:center;justify-content:center;
                               transition:background 0.2s;" onmouseover="this.style.background='#fee2e2'"
                    onmouseout="this.style.background='#fff0f0'">
                    <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <button title="Thêm vào giỏ" style="width:28px;height:28px;border:none;background:#ecfdf5;border-radius:50%;
                               cursor:pointer;display:flex;align-items:center;justify-content:center;
                               transition:background 0.2s;" onmouseover="this.style.background='#d1fae5'"
                    onmouseout="this.style.background='#ecfdf5'">
                    <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div style="padding:1rem 1.5rem;border-top:1px solid #f0f0f0;display:flex;gap:0.75rem;">
        <a href="/products" style="flex:1;padding:0.7rem;text-align:center;border:1.5px solid #10b981;color:#10b981;
                  border-radius:8px;font-weight:700;font-size:0.875rem;text-decoration:none;
                  transition:all 0.2s;" onmouseover="this.style.background='#f0fdf4'"
            onmouseout="this.style.background='transparent'">
            Tiếp tục mua
        </a>
        <button style="flex:1;padding:0.7rem;background:#10b981;color:white;border:none;
                       border-radius:8px;font-weight:700;font-size:0.875rem;cursor:pointer;
                       transition:background 0.2s;" onmouseover="this.style.background='#059669'"
            onmouseout="this.style.background='#10b981'">
            Thêm tất cả vào giỏ
        </button>
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