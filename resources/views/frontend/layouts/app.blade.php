<!DOCTYPE html>
<html lang="vi">

<head>
    {{-- @yield('title'): Nơi các trang con sẽ truyền tiêu đề (title) vào. Nếu không truyền, mặc định sẽ là 'Happy Tea'
    --}}
    <title>@yield('title', 'Happy Tea')</title>

    {{-- @include: Nạp nội dung từ file resources/views/components/head.blade.php vào đây (thường chứa các thẻ meta,
    link CSS) --}}
    @include('frontend.components.head')
</head>

{{-- @yield('body_class'): Cho phép trang con tự thêm class CSS riêng vào thẻ body (vd: class 'home-page' cho trang chủ)
--}}

<body class="@yield('body_class')">

    {{-- Nạp thanh điều hướng phía trên (Header/Navbar) - hiển thị chung cho mọi trang --}}
    @include('frontend.components.navbar')

    {{-- Phần thân chính của trang web --}}
    <main>
        {{-- @yield('content'): Điểm nối vô cùng quan trọng. Nội dung chính của các trang con (home, products,
        profile...) sẽ được "nhét" vào đúng vị trí này --}}
        @yield('content')
    </main>

    {{-- Nạp chân trang (Footer) - hiển thị chung ở cuối mọi trang --}}
    @include('frontend.components.footer')

    {{-- ===== MODALS XÁC THỰC ===== --}}
    {{-- Các file này chứa mã HTML của các hộp thoại (popup) bị ẩn đi. Chúng chỉ hiện lên khi JavaScript kích hoạt. Nạp
    sẵn ở layout để trang nào cũng có thể gọi popup đăng nhập --}}
    @include('frontend.auth.login')
    @include('frontend.auth.register')
    @include('frontend.auth.forgot-password')
    @include('frontend.auth.verify-otp')



    {{-- ===== SCRIPTS ===== --}}
    <!-- Thư viện Javascript bên thứ 3 (Thanh cuộn mượt, Slider/Carousel) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>

    <!-- Code Javascript tự viết chung cho cả hệ thống (Giỏ hàng, Yêu thích...) -->
    <script src="{{ asset('js/frontend/layout/main.js') }}"></script>

    <!-- Các script plugin tĩnh được đặt trong thư mục public/js/vendors -->
    <script src="{{ asset('js/vendors/countdown.js') }}"></script>
    <script src="{{ asset('js/vendors/tns-slider.js') }}"></script>
    <script src="{{ asset('js/vendors/zoom.js') }}"></script>
    <script src="{{ asset('js/vendors/swiper.js') }}"></script>
    <script src="{{ asset('js/vendors/validation.js') }}"></script>

    {{-- @stack('scripts'): Khác với @yield, @stack cho phép nhiều trang con cùng "đẩy" (@push) các đoạn mã script bổ
    sung vào vị trí này. Rất hữu ích khi một trang cụ thể cần chạy một file JS riêng biệt --}}
    @stack('scripts')

    @if (Auth::check() && Auth::user()->is_active == 0)
        <link rel="stylesheet" href="{{ asset('css/frontend/users.css') }}">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        {{-- Đẩy dữ liệu an toàn từ PHP sang JS --}}
        @php
            $lockedUserData = [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'reason' => Auth::user()->lock_reason
            ];
        @endphp
        <script>window.lockedUserData = @json($lockedUserData);</script>
        
        {{-- Chạy logic giao diện --}}
        <script src="{{ asset('js/frontend/layout/locked-account.js') }}"></script>
    @endif

</body>

</html>