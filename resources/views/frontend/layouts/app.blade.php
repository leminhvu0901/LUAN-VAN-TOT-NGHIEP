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
    @include('frontend.auth.reset-password')

    {{-- Bong bóng nổi Giỏ hàng + Zalo — xếp ngay phía trên bong bóng chatbox bên dưới --}}
    @include('frontend.components.floating-bubbles')

    {{-- Chatbox trả lời nhanh (quick-reply, không AI) — hiển thị trên mọi trang frontend --}}
    @include('frontend.components.quick-chatbox')



    {{-- ===== SCRIPTS ===== --}}
    <!-- Thư viện Javascript bên thứ 3 (Thanh cuộn mượt, Slider/Carousel) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>
    {{-- SweetAlert2 — trước đây CHỈ nạp khi tài khoản bị khóa (is_active==0), nên window.FrontendAlert
    (main.js) và mọi chỗ khác lỡ gọi Swal trực tiếp trên toàn frontend đều lặng lẽ không hiện được gì
    cho >99% tài khoản bình thường. Nạp sẵn ở đây cho MỌI trang để dùng được toast thông báo thống nhất. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Code Javascript tự viết chung cho cả hệ thống (Giỏ hàng, Yêu thích, thông báo toast...) -->
    {{-- ?v=filemtime: ép trình duyệt tải lại JS mới mỗi khi file này thay đổi, tránh bị cache bản cũ trên web đã deploy --}}
    <script src="{{ asset('js/frontend/layout/main.js') }}?v={{ filemtime(public_path('js/frontend/layout/main.js')) }}"></script>

    {{-- Hiện toast cho flash message session('success') dùng CHUNG cho mọi trang - tránh phải tự thêm
    @if(session('success')) + gọi FrontendAlert riêng lẻ ở từng trang (dễ quên, dễ sót như đã xảy ra
    với trang đăng ký/xác nhận OTP). Chỉ áp dụng cho 'success': 'error' KHÔNG đưa vào đây vì
    orders/index.blade.php và checkout.blade.php đã tự hiển thị session('error') riêng bằng banner
    tĩnh, thêm toast nữa sẽ bị hiện trùng 2 lần. --}}
    @if(session('success'))
        <script>window.FrontendAlert.success(@json(session('success')), 3500);</script>
    @endif

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
        <script src="{{ asset('js/frontend/layout/locked-account.js') }}?v={{ filemtime(public_path('js/frontend/layout/locked-account.js')) }}"></script>
    @endif

</body>

</html>