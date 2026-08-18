
@php
    // Mặc định: danh sách rỗng & số giỏ = 0 (dành cho khách chưa đăng nhập)
    $favoriteProducts = collect();
    $cartCount = 0;

    // Chỉ truy vấn database nếu người dùng đã đăng nhập
    if (Auth::check()) {
        // Lấy danh sách sản phẩm yêu thích của người dùng
        $favoriteProducts = \App\Models\Favorite::query()
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->leftJoin(
                \Illuminate\Support\Facades\DB::raw(
                    '(SELECT product_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(id) as review_count
                                  FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'
                ),
                'products.id',
                '=',
                'r.product_id'
            )
            ->where('favorites.user_id', Auth::id())
            ->select(
                'products.*',
                'favorites.id as favorite_id',
                \Illuminate\Support\Facades\DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(r.review_count, 0) as review_count')
            )
            ->get();

        // Tìm giỏ hàng của user hiện tại
        $cart = \App\Models\Cart::query()->where('user_id', Auth::id())->first();

        // Nếu giỏ hàng tồn tại, đếm tổng số lượng sản phẩm
        if ($cart) {
            $cartCount = \App\Models\CartItem::query()->where('cart_id', $cart->id)->sum('quantity');
        }
    }
@endphp

@php
    $navReceiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
    $navOpen = \App\Models\Setting::getValue('store_open_time', '08:00');
    $navClose = \App\Models\Setting::getValue('store_close_time', '22:00');
    $navNowStr = now()->format('H:i');
    
    $navIsClosed = !$navReceiveEnabled;
    $navClosedReason = '';
    if ($navIsClosed) {
        $navClosedReason = 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng mới. Bạn vẫn có thể xem sản phẩm nhưng tính năng đặt hàng tạm thời bị ngắt.';
    } else {
        $navIsOpen = false;
        if ($navOpen < $navClose) {
            $navIsOpen = ($navNowStr >= $navOpen && $navNowStr <= $navClose);
        } else {
            $navIsOpen = ($navNowStr >= $navOpen || $navNowStr <= $navClose);
        }
        if (!$navIsOpen) {
            $navIsClosed = true;
            $navClosedReason = "Cửa hàng hiện đã đóng cửa (Giờ phục vụ hàng ngày: {$navOpen} - {$navClose}). Tính năng đặt hàng tạm thời bị ngắt.";
        }
    }
@endphp

@if($navIsClosed)
    <!-- Modal thông báo cửa hàng đóng cửa/ngưng nhận đơn -->
    <div id="store-closed-modal" class="store-closed-modal-overlay">
        <!-- Backdrop -->
        <div class="store-closed-modal-backdrop"></div>
        <!-- Modal Content -->
        <div class="store-closed-modal-content" id="store-closed-content">
            <div class="store-closed-modal-flex">
                <!-- Icon container with soft pulse animation -->
                <div class="store-closed-icon-wrapper">
                    <span class="material-symbols-outlined store-closed-icon">schedule</span>
                </div>
                <h3 class="store-closed-title">Thông báo nhận đơn</h3>
                <p class="store-closed-desc">
                    {{ $navClosedReason }}
                </p>
                <button type="button" id="close-store-modal-btn" class="store-closed-btn">
                    Tôi đã hiểu
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Thanh navbar --}}
<header class="happy-navbar sticky top-0 z-50 bg-white shadow-sm" id="main-navbar">
    <div class="nav-container">
        <div class="happy-navbar__row">

            {{-- Logo --}}
            @php
                $shopLogo = \App\Models\Setting::getValue('store_logo', '/images/logo/black.png');
                $shopName = \App\Models\Setting::getValue('store_name', 'Happy Tea');
            @endphp
            <a href="{{ url('/') }}" class="happy-navbar__brand">
                <img src="{{ asset($shopLogo) }}"
                     alt="{{ $shopName }}"
                     class="h-10 w-auto max-w-[150px] object-contain flex-shrink-0 ">
            </a>

            {{-- Thanh tìm kiếm --}}
            <form action="{{ url('/products') }}" method="GET" class="l-nav-search hidden md:flex">
                <button type="submit"
                    class="happy-navbar__search-icon--desktop mr-2 flex-shrink-0 bg-transparent border-none p-0 cursor-pointer"
                    aria-label="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass text-gray-500 text-sm"></i>
                </button>
                <input id="search-input" name="search" type="text" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="l-nav-search-input" value="{{ request('search') }}" />
            </form>

            <div class="happy-navbar__actions-wrapper">

                {{-- Menu ĐIỀU HƯỚNG ===== Chỉ hiển thị trên màn hình --}}
                <nav class="happy-navbar__desktop-nav navbar-desktop-nav" aria-label="Điều hướng chính">
                    <a href="{{ url('/') }}" class="nav-link nav-link--active">Trang chủ</a>
                    <a href="/products" class="nav-link">Sản phẩm</a>
                    <a href="/orders" class="nav-link">Đơn hàng</a>
                    @php $navZaloUrl = \App\Models\Setting::getValue('store_zalo_url', '#'); @endphp
                    <a href="{{ $navZaloUrl }}" target="_blank" rel="noopener noreferrer" class="nav-link">Liên hệ</a>
                </nav>

                <div class="happy-navbar__actions">

                    {{-- Nút yêu thích --}}
                    <button id="wishlist-btn" type="button" class="happy-navbar__icon-btn"
                        aria-label="Sản phẩm yêu thích">
                        <i class="fa-solid fa-heart"></i>
                        {{-- Số lượng sản phẩm yêu thích --}}
                        <span id="wishlist-badge">{{ count($favoriteProducts) }}</span>
                    </button>

                    
                    @guest
                        <button id="login-btn" type="button" class="happy-navbar__icon-btn" aria-label="Đăng nhập">
                            <i class="fa-solid fa-user"></i>
                        </button>
                    @endguest

                    {{-- Icon tài khoản --}}
                    @auth
                        <a href="{{ route('profile') }}" class="happy-navbar__icon-btn" aria-label="Tài khoản">
                            @if(Auth::user()->avatar)
                                {{-- Avatar --}}
                                <img src="{{ avatar_url(Auth::user()->avatar) }}" alt="Avatar"
                                    class="navbar-avatar">
                            @else
                                {{-- Icon người dùng mặc định khi chưa có avatar --}}
                                <i class="fa-solid fa-user"></i>
                            @endif
                        </a>
                    @endauth

                    {{-- Nút giỏ hàng --}}
                    <button id="cart-btn" type="button" class="happy-navbar__icon-btn" aria-label="Giỏ hàng">
                        <i class="fa-solid fa-cart-shopping"></i>
                        {{-- Badge: ẩn nếu giỏ rỗng, hiện nếu có sp --}}
                        <span id="cart-badge"
                            class="{{ $cartCount > 0 ? '' : 'cart-badge--hidden' }}">{{ $cartCount }}</span>
                    </button>

                    {{-- Nút menu mobile --}}
                    <button id="hamburger" type="button" class="happy-navbar__icon-btn navbar-hamburger"
                        aria-label="Mở menu">
                        <i id="ham-icon" class="fa-solid fa-bars text-lg"></i>
                        <i id="close-icon" class="fa-solid fa-xmark text-lg hidden"></i>
                    </button>

                </div>
            </div>
        </div>

        {{-- Thanh tìm kiếm --}}
        <div id="mobile-search-bar" class="happy-navbar__mobile-search">
            <form action="{{ url('/products') }}" method="GET" class="happy-navbar__search">
                <button type="submit" class="happy-navbar__search-icon bg-transparent border-none p-0 cursor-pointer"
                    aria-label="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass text-gray-500 text-sm"></i>
                </button>
                <input type="text" name="search" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="happy-navbar__search-input" value="{{ request('search') }}" autocomplete="off" />
            </form>
        </div>

        {{-- Menu ĐIỀU HƯỚNG ===== Ẩn mặc định, hiện ra khi --}}
        <div id="mobile-menu" class="happy-navbar__mobile-menu">
            <nav class="happy-navbar__mobile-nav" aria-label="Điều hướng di động">
                <a href="{{ url('/') }}" class="happy-navbar__mobile-link is-active">Trang chủ</a>
                <a href="/products" class="happy-navbar__mobile-link">Sản phẩm</a>
                <a href="{{ route('orders') }}" class="happy-navbar__mobile-link">Đơn hàng</a>
                <a href="/#footer-custom" class="happy-navbar__mobile-link">Liên hệ</a>
            </nav>
        </div>
    </div>
</header>

{{-- Ngăn kéo sản phẩm yêu thích --}}
<div id="wishlist-overlay" aria-hidden="true"></div>

<aside id="wishlist-drawer" role="dialog" aria-modal="true" aria-label="Danh sách yêu thích">
    {{-- Header --}}
    <div class="wl-drawer__header">
        <div>
            <h2 class="wl-drawer__title">Sản phẩm yêu thích</h2>
            <p class="wl-drawer__subtitle">{{ count($favoriteProducts) }} sản phẩm đã lưu</p>
        </div>
        <button id="wishlist-close" class="wl-drawer__close-btn" aria-label="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Body: danh sách sản phẩm yêu thích --}}
    <div class="wl-drawer__body" id="wishlist-list">
        {{-- @forelse: lặp qua danh sách, nếu rỗng thì vào @empty --}}
        @forelse($favoriteProducts as $item)
            <div class="wl-item">
                {{-- Ảnh sản phẩm, nếu lỗi thì thay bằng ảnh placeholder --}}
                <img src="{{ upload_url($item->image) }}" alt="{{ $item->name }}" class="wl-item__img"
                    onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                <div class="wl-item__info">
                    <p class="wl-item__name">{{ $item->name }}</p>
                    <div class="wl-item__rating">
                        {{-- Hiển thị số sao dựa trên avg_rating từ db --}}
                        <span class="wl-item__stars">
                            @for ($s = 1; $s <= 5; $s++)
                                @if ($s <= round($item->avg_rating))
                                    <span class="wl-item__star--active">★</span>
                                @else
                                    <span class="wl-item__star--inactive">★</span>
                                @endif
                            @endfor
                        </span>
                        <span class="wl-item__rating-value">
                            {{ $item->avg_rating > 0 ? number_format($item->avg_rating, 1) : 'Chưa có' }}
                        </span>
                    </div>
                    {{-- Định dạng giá: 45000 -> 45.000đ --}}
                    <span class="wl-item__price">{{ number_format($item->base_price, 0, ',', '.') }}đ</span>
                </div>
                <div class="wl-item__actions">
                    {{-- Nút xóa: gọi JS removeFromWishlist --}}
                    <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn"
                        onclick="removeFromWishlist({{ $item->id }})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    {{-- Nút thêm vào giỏ: gọi JS addToCart --}}
                    <button title="Thêm vào giỏ" class="wl-item__cart-btn" onclick="addToCart({{ $item->id }})">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </button>
                </div>
            </div>
        @empty
            {{-- Trường hợp chưa lưu sản phẩm nào --}}
            <div class="wl-empty">
                <p>Bạn chưa lưu sản phẩm nào.</p>
            </div>
        @endforelse
    </div>

    {{-- Footer: nút thêm tất cả vào giỏ hàng --}}
    <div class="wl-drawer__footer">
        <button class="wl-drawer__add-all-btn" onclick="addAllToCart()">Thêm tất cả vào giỏ</button>
    </div>
</aside>

{{-- Ngăn kéo giỏ hàng --}}
<div id="cart-overlay" aria-hidden="true"></div>

<aside id="cart-drawer" role="dialog" aria-modal="true" aria-label="Giỏ hàng">

    {{-- Header --}}
    <div class="cart-drawer__header">
        <div>
            <h2 class="cart-drawer__title">Giỏ hàng của bạn</h2>
            {{-- Số lượng sản phẩm được cập nhật động bởi JS --}}
            <p class="cart-drawer__subtitle" id="cart-drawer-subtitle">0 sản phẩm</p>
        </div>
        <button id="cart-close" class="cart-drawer__close" aria-label="Đóng">
            <i class="fa-solid fa-xmark text-gray-700 text-base"></i>
        </button>
    </div>

    {{-- Thanh chọn tất cả + xóa hàng loạt --}}
    <div id="cart-select-all-bar" class="cart-select-all-bar">
        <label class="cart-select-all-label" for="cart-select-all-chk">
            <input type="checkbox" id="cart-select-all-chk" checked>
            <span>Chọn tất cả</span>
        </label>
        <div class="cart-select-all-actions">
            <span class="cart-select-all-hint" id="cart-selected-count-hint">0 đã chọn</span>
            <button type="button" id="cart-remove-selected-btn" class="cart-bulk-remove-btn" onclick="removeSelectedFromCart()">Xóa đã chọn</button>
            <button type="button" id="cart-clear-all-btn" class="cart-bulk-remove-btn cart-bulk-remove-btn--danger" onclick="clearCart()">Xóa tất cả</button>
        </div>
    </div>

    {{-- Body: danh sách sản phẩm trong giỏ --}}
    <div id="cart-list">
        {{-- Cart items được inject qua JS --}}
    </div>

    {{-- Footer: tổng tiền đã CHỌN + nút thanh toán động --}}
    <div class="cart-drawer__footer">
        <div class="cart-drawer__total-row">
            <span class="cart-drawer__total-label">Đã chọn:</span>
            {{-- Tổng tiền sản phẩm đã CHỌN, cập nhật động bởi JS --}}
            <span id="cart-drawer-total" class="cart-drawer__total-amount">0đ</span>
        </div>
        {{-- Nút thanh toán: disabled khi không chọn sản phẩm nào --}}
        <button type="button" id="cart-checkout-btn"
            class="cart-drawer__checkout-btn"
            onclick="cartProceedToCheckout()"
            disabled>
            Thanh toán ngay (<span id="cart-selected-item-count">0</span> sản phẩm)
        </button>
    </div>
</aside>

@push('scripts')
<script>
// Quản lý ngăn kéo Yêu thích, Giỏ hàng, Menu Mobile và Popup đóng cửa
(function () {
    // Hàm rút gọn thay cho document.getElementById, chỉ dùng nội bộ trong file này
    const $ = (id) => document.getElementById(id);

    // Mở/Đóng Ngăn kéo Yêu thích
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

    // Đóng popup danh sách yêu thích
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

    // Mở/Đóng Ngăn kéo Giỏ hàng
    const openCart = () => {
        const drawer = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;
        if (typeof window.loadCart === 'function') window.loadCart();
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)';
            overlay.style.opacity = '1';
        });
        document.body.style.overflow = 'hidden';
    };

    // Đóng popup giỏ hàng
    const closeCart = () => {
        const drawer = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;
        drawer.style.transform = 'translateX(100%)';
        overlay.style.opacity = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 320);
        document.body.style.overflow = '';
    };

    $('cart-btn')?.addEventListener('click', openCart);
    $('cart-close')?.addEventListener('click', closeCart);
    $('cart-overlay')?.addEventListener('click', closeCart);

    // Toggle Hamburger Menu Mobile
    $('hamburger')?.addEventListener('click', () => {
        const menu = $('mobile-menu');
        const search = $('mobile-search-bar');
        const ham = $('ham-icon');
        const close = $('close-icon');
        if (!menu || !ham || !close) return;
        const isOpen = menu.classList.toggle('open');
        if (search) search.classList.toggle('open', isOpen);
        ham.classList.toggle('hidden', isOpen);
        close.classList.toggle('hidden', !isOpen);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeWishlist(); closeCart(); }
    });

    // Thông báo cửa hàng đóng cửa khi quá giờ làm việc
    const storeClosedModal = $('store-closed-modal');
    if (storeClosedModal) {
        const lastShownAt = sessionStorage.getItem('store_closed_popup_shown_at');
        const now = Date.now();
        if (!lastShownAt || (now - parseInt(lastShownAt)) > 10000) {
            const content = $('store-closed-content');
            sessionStorage.setItem('store_closed_popup_shown_at', now.toString());
            storeClosedModal.style.display = 'flex';
            setTimeout(() => {
                storeClosedModal.style.opacity = '1';
                content.style.transform = 'scale(1)';
                content.style.opacity = '1';
            }, 50);

            $('close-store-modal-btn')?.addEventListener('click', function () {
                storeClosedModal.style.opacity = '0';
                content.style.transform = 'scale(0.9)';
                content.style.opacity = '0';
                setTimeout(() => {
                    storeClosedModal.style.display = 'none';
                }, 300);
            });
        }
    }
})();
</script>
@endpush