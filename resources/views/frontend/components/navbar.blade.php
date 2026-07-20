
@php
    // Mặc định: danh sách rỗng & số giỏ = 0 (dành cho khách chưa đăng nhập)
    $favoriteProducts = collect();
    $cartCount = 0;

    // Chỉ truy vấn database nếu người dùng đã đăng nhập
    if (Auth::check()) {
        // Lấy danh sách sản phẩm yêu thích của user hiện tại
        // JOIN bảng products để lấy thông tin chi tiết (tên, ảnh, giá...)
        // LEFT JOIN subquery reviews để lấy điểm đánh giá trung bình
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

        // Tìm giỏ hàng của user hiện tại (mỗi user chỉ có 1 giỏ)
        $cart = \App\Models\Cart::query()->where('user_id', Auth::id())->first();

        // Nếu giỏ hàng tồn tại, đếm tổng số lượng sản phẩm (sum quantity)
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
    <!-- Modal thông báo cửa hàng đóng cửa/ngưng nhận đơn (Chỉ xuất hiện khi mới vào web) -->
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

{{-- ===== HEADER: Thanh navbar cố định trên cùng =====
sticky top-0: dính vào đầu trang khi cuộn
z-50: luôn hiển thị trên các phần tử khác
shadow-sm: đổ bóng nhẹ phía dưới
--}}
<header class="happy-navbar sticky top-0 z-50 bg-white shadow-sm" id="main-navbar">
    <div class="nav-container">
        <div class="happy-navbar__row">

            {{-- ===== LOGO: Biểu tượng thương hiệu =====
            SVG icon giỏ hàng màu xanh #10b981 (brand color)
            Nhấn vào sẽ về trang chủ
            --}}
            @php
                $shopLogo = \App\Models\Setting::getValue('store_logo', '/images/logo/black.png');
                $shopName = \App\Models\Setting::getValue('store_name', 'Happy Tea');
            @endphp
            <a href="{{ url('/') }}" class="happy-navbar__brand">
                <img src="{{ asset($shopLogo) }}"
                     alt="{{ $shopName }}"
                     class="h-10 w-auto max-w-[150px] object-contain flex-shrink-0 ">
            </a>

            {{-- ===== THANH TÌM KIẾM (Desktop) =====
            Chỉ hiển thị trên màn hình >= md (768px): class "hidden md:flex"
            Submit form GET đến /products?search=...
            request('search') giữ lại từ khóa đã tìm trong ô input
            --}}
            <form action="{{ url('/products') }}" method="GET" class="l-nav-search hidden md:flex">
                <button type="submit"
                    class="text-gray-400 mr-2 flex-shrink-0 bg-transparent border-none p-0 cursor-pointer"
                    aria-label="Tìm kiếm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                    </svg>
                </button>
                <input id="search-input" name="search" type="text" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="l-nav-search-input" value="{{ request('search') }}" />
            </form>

            <div class="happy-navbar__actions-wrapper">

                {{-- ===== MENU ĐIỀU HƯỚNG (Desktop) =====
                Chỉ hiển thị trên màn hình lớn
                nav-link--active: class highlight trang đang active (Trang chủ)
                --}}
                <nav class="happy-navbar__desktop-nav navbar-desktop-nav" aria-label="Điều hướng chính">
                    <a href="{{ url('/') }}" class="nav-link nav-link--active">Trang chủ</a>
                    <a href="/products" class="nav-link">Sản phẩm</a>
                    <a href="/orders" class="nav-link">Đơn hàng</a>
                    @php $navZaloUrl = \App\Models\Setting::getValue('store_zalo_url', '#'); @endphp
                    <a href="{{ $navZaloUrl }}" target="_blank" rel="noopener noreferrer" class="nav-link">Liên hệ</a>
                </nav>

                <div class="happy-navbar__actions">

                    {{-- ===== NÚT YÊU THÍCH =====
                    Nhấn vào mở wishlist drawer (xử lý trong navbar.js)
                    Badge hiển thị số lượng sản phẩm đã lưu yêu thích
                    --}}
                    <button id="wishlist-btn" type="button" class="happy-navbar__icon-btn"
                        aria-label="Sản phẩm yêu thích">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                        </svg>
                        {{-- Số lượng sản phẩm yêu thích (0 nếu chưa đăng nhập) --}}
                        <span id="wishlist-badge">{{ count($favoriteProducts) }}</span>
                    </button>

                    {{-- ===== NÚT ĐĂNG NHẬP (chỉ hiện khi chưa đăng nhập) =====
                    @guest: directive Blade, render khi user là khách (chưa auth)
                    Nhấn vào sẽ mở modal đăng nhập (xử lý trong main.js)
                    --}}
                    @guest
                        <button id="login-btn" type="button" class="happy-navbar__icon-btn" aria-label="Đăng nhập">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </button>
                    @endguest

                    {{-- ===== ICON TÀI KHOẢN (chỉ hiện khi đã đăng nhập) =====
                    @auth: directive Blade, render khi user đã đăng nhập
                    Nếu user có avatar: hiển thị ảnh avatar
                    Nếu chưa có avatar: hiển thị icon người dùng mặc định
                    Nhấn vào dẫn đến trang hồ sơ cá nhân
                    --}}
                    @auth
                        <a href="{{ route('profile') }}" class="happy-navbar__icon-btn" aria-label="Tài khoản">
                            @if(Auth::user()->avatar)
                                {{-- Ảnh avatar đã upload, CSS class .navbar-avatar định dạng tròn --}}
                                <img src="{{ asset('images/avatars/' . Auth::user()->avatar) }}" alt="Avatar"
                                    class="navbar-avatar">
                            @else
                                {{-- Icon người dùng mặc định khi chưa có avatar --}}
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            @endif
                        </a>
                    @endauth

                    {{-- ===== NÚT GIỎ HÀNG =====
                    Nhấn vào mở cart drawer (sidebar bên phải)
                    Badge hiển thị tổng số lượng sp trong giỏ
                    Badge ẩn đi khi giỏ = 0 (display: none qua PHP điều kiện)
                    --}}
                    <button id="cart-btn" type="button" class="happy-navbar__icon-btn" aria-label="Giỏ hàng">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                        </svg>
                        {{-- Badge: ẩn nếu giỏ rỗng ($cartCount = 0), hiện nếu có sp --}}
                        <span id="cart-badge"
                            class="{{ $cartCount > 0 ? '' : 'cart-badge--hidden' }}">{{ $cartCount }}</span>
                    </button>

                    {{-- ===== NÚT HAMBURGER (Mobile) =====
                    Chỉ hiển thị trên màn hình nhỏ (mobile)
                    ham-icon: icon 3 gạch (☰) - hiển thị khi menu đóng
                    close-icon: icon X - hiển thị khi menu đang mở
                    Toggle qua class "hidden" bởi navbar.js
                    --}}
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

        {{-- ===== THANH TÌM KIẾM (Mobile) =====
        Hiển thị phía dưới navbar trên màn hình nhỏ
        Cùng chức năng với thanh tìm kiếm desktop nhưng thiết kế khác
        --}}
        <div id="mobile-search-bar" class="happy-navbar__mobile-search">
            <form action="{{ url('/products') }}" method="GET" class="happy-navbar__search">
                <button type="submit" class="happy-navbar__search-icon bg-transparent border-none p-0 cursor-pointer"
                    aria-label="Tìm kiếm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path stroke-linecap="round" d="m21 21-4.35-4.35" />
                    </svg>
                </button>
                <input type="text" name="search" placeholder="Tìm kiếm trà sữa, cà phê..."
                    class="happy-navbar__search-input" value="{{ request('search') }}" autocomplete="off" />
            </form>
        </div>

        {{-- ===== MENU ĐIỀU HƯỚNG (Mobile) =====
        Ẩn mặc định, hiện ra khi nhấn nút hamburger
        Được toggle class "open" bởi navbar.js
        --}}
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

{{-- ===== WISHLIST DRAWER (Ngăn kéo sản phẩm yêu thích) =====
Là một sidebar trượt ra từ bên phải khi nhấn icon tim
wishlist-overlay: lớp phủ tối phía sau drawer
wishlist-drawer: panel chứa danh sách sp yêu thích
Mở/đóng được điều khiển bởi navbar.js
--}}
<div id="wishlist-overlay" aria-hidden="true"></div>

<aside id="wishlist-drawer" role="dialog" aria-modal="true" aria-label="Danh sách yêu thích">
    {{-- Header của drawer: tiêu đề + nút đóng --}}
    <div class="wl-drawer__header">
        <div>
            <h2 class="wl-drawer__title">Sản phẩm yêu thích</h2>
            <p class="wl-drawer__subtitle">{{ count($favoriteProducts) }} sản phẩm đã lưu</p>
        </div>
        <button id="wishlist-close" class="wl-drawer__close-btn" aria-label="Đóng">
            <svg width="16" height="16" fill="none" stroke="#374151" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Body: danh sách sản phẩm yêu thích --}}
    <div class="wl-drawer__body" id="wishlist-list">
        {{-- @forelse: lặp qua danh sách, nếu rỗng thì vào @empty --}}
        @forelse($favoriteProducts as $item)
            <div class="wl-item">
                {{-- Ảnh sản phẩm, nếu lỗi thì thay bằng ảnh placeholder --}}
                <img src="{{ asset('images/' . $item->image) }}" alt="{{ $item->name }}" class="wl-item__img"
                    onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                <div class="wl-item__info">
                    <p class="wl-item__name">{{ $item->name }}</p>
                    <div class="wl-item__rating">
                        {{-- Hiển thị số sao dựa trên avg_rating từ DB (làm tròn) --}}
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
                    {{-- Nút xóa: gọi JS removeFromWishlist(id) --}}
                    <button title="Xóa khỏi yêu thích" class="wl-item__remove-btn"
                        onclick="removeFromWishlist({{ $item->id }})">
                        <svg width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                    {{-- Nút thêm vào giỏ: gọi JS addToCart(id) --}}
                    <button title="Thêm vào giỏ" class="wl-item__cart-btn" onclick="addToCart({{ $item->id }})">
                        <svg width="13" height="13" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                        </svg>
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

{{-- ===== CART DRAWER (Ngăn kéo giỏ hàng) =====
Sidebar trượt ra từ bên phải khi nhấn icon giỏ hàng
cart-overlay: lớp phủ tối phía sau drawer
cart-drawer: panel chứa danh sách sp trong giỏ + tổng tiền
Nội dung giỏ hàng được load động qua AJAX (JS gọi API, không phải Blade)
Mở/đóng được điều khiển bởi navbar.js
--}}
<div id="cart-overlay" aria-hidden="true"></div>

<aside id="cart-drawer" role="dialog" aria-modal="true" aria-label="Giỏ hàng">

    {{-- Header: tiêu đề "Giỏ hàng" + nút đóng X --}}
    <div class="cart-drawer__header">
        <div>
            <h2 class="cart-drawer__title">Giỏ hàng của bạn</h2>
            {{-- Số lượng sản phẩm được cập nhật động bởi JS --}}
            <p class="cart-drawer__subtitle" id="cart-drawer-subtitle">0 sản phẩm</p>
        </div>
        <button id="cart-close" class="cart-drawer__close" aria-label="Đóng">
            <svg width="16" height="16" fill="none" stroke="#374151" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Thanh chọn tất cả (chỉ hiện khi có sản phẩm trong giỏ) --}}
    <div id="cart-select-all-bar" class="cart-select-all-bar">
        <label class="cart-select-all-label" for="cart-select-all-chk">
            <input type="checkbox" id="cart-select-all-chk" checked>
            <span>Chọn tất cả</span>
        </label>
        <span class="cart-select-all-hint" id="cart-selected-count-hint">0 đã chọn</span>
    </div>

    {{-- Body: danh sách sản phẩm trong giỏ (được inject bởi JS qua AJAX) --}}
    <div id="cart-list">
        {{-- Cart items được inject qua JS --}}
    </div>

    {{-- Footer: tổng tiền ĐÃ CHỌN + nút thanh toán động --}}
    <div class="cart-drawer__footer">
        <div class="cart-drawer__total-row">
            <span class="cart-drawer__total-label">Đã chọn:</span>
            {{-- Tổng tiền sản phẩm ĐÃ CHỌN, cập nhật động bởi JS --}}
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

{{-- ===== SCRIPTS =====
@push('scripts'): đẩy script vào stack 'scripts' được định nghĩa trong layout
navbar.js xử lý: hamburger toggle, wishlist drawer, cart drawer
--}}
@push('scripts')
    <script src="{{ asset('js/frontend/layout/navbar.js') }}"></script>
@endpush