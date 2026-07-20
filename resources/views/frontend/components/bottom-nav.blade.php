{{-- ===== THANH ĐIỀU HƯỚNG DƯỚI CÙNG (Mobile Bottom Navigation) =====
Chỉ hiển thị trên mobile — dùng chung cho Trang chủ, Sản phẩm, Đơn hàng, Tài khoản.
Tab đang active được tính theo route hiện tại (không hardcode cứng theo từng trang) để tránh
lệch nhau giữa các trang dùng chung thanh này.

QUAN TRỌNG: dùng class CSS thuần (định nghĩa trong users.css, "l-bottom-nav*"), KHÔNG dùng class
Tailwind — vì Tailwind CDN chỉ được nạp có điều kiện cho 4 route profile/orders/checkout/review
(xem frontend/components/head.blade.php), còn Trang chủ/Sản phẩm không hề nạp Tailwind. Dùng class
Tailwind ở đây sẽ vỡ layout trên 2 trang đó (đã xảy ra thực tế, đây là bản sửa lại). --}}
@php
    $bottomNavIsHome = request()->is('/');
    $bottomNavIsProducts = request()->routeIs('products');
    $bottomNavIsOrders = request()->routeIs('orders');
    $bottomNavIsProfile = request()->routeIs('profile');
@endphp
<nav class="l-bottom-nav">
    <a href="{{ url('/') }}" class="l-bottom-nav__item {{ $bottomNavIsHome ? 'is-active' : '' }}">
        <span class="material-symbols-outlined {{ $bottomNavIsHome ? 'material-filled' : '' }}">home</span>
        <span class="l-bottom-nav__label">Trang chủ</span>
    </a>
    <a href="{{ route('products') }}" class="l-bottom-nav__item {{ $bottomNavIsProducts ? 'is-active' : '' }}">
        <span class="material-symbols-outlined {{ $bottomNavIsProducts ? 'material-filled' : '' }}">eco</span>
        <span class="l-bottom-nav__label">Sản phẩm</span>
    </a>
    <a href="{{ route('orders') }}" class="l-bottom-nav__item {{ $bottomNavIsOrders ? 'is-active' : '' }}">
        <span class="material-symbols-outlined {{ $bottomNavIsOrders ? 'material-filled' : '' }}">receipt_long</span>
        <span class="l-bottom-nav__label">Đơn hàng</span>
    </a>
    <a href="{{ route('profile') }}" class="l-bottom-nav__item {{ $bottomNavIsProfile ? 'is-active' : '' }}">
        <span class="material-symbols-outlined {{ $bottomNavIsProfile ? 'material-filled' : '' }}">person</span>
        <span class="l-bottom-nav__label">Tài khoản</span>
    </a>
</nav>
