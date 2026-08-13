{{-- Thanh điều hướng dưới cùng --}}
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
