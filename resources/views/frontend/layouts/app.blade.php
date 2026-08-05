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

    {{-- Bong bóng nổi Giỏ hàng + Zalo --}}
    @include('frontend.components.floating-bubbles')



    {{-- ===== SCRIPTS ===== --}}
    <!-- Thư viện Javascript bên thứ 3 (Thanh cuộn mượt, Slider/Carousel) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>
    {{-- SweetAlert2 — trước đây CHỈ nạp khi tài khoản bị khóa (is_active==0), nên window.FrontendAlert
    (main.js) và mọi chỗ khác lỡ gọi Swal trực tiếp trên toàn frontend đều lặng lẽ không hiện được gì
    cho >99% tài khoản bình thường. Nạp sẵn ở đây cho MỌI trang để dùng được toast thông báo thống nhất. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Hiện toast cho flash message session('success') dùng CHUNG cho mọi trang - tránh phải tự thêm
    @if(session('success')) + gọi FrontendAlert riêng lẻ ở từng trang (dễ quên, dễ sót như đã xảy ra
    với trang đăng ký/xác nhận OTP). Chỉ áp dụng cho 'success': 'error' KHÔNG đưa vào đây vì
    orders/index.blade.php và checkout.blade.php đã tự hiển thị session('error') riêng bằng banner
    tĩnh, thêm toast nữa sẽ bị hiện trùng 2 lần. --}}
    @if(session('success'))
        <script>window.FrontendAlert.success(@json(session('success')), 3500);</script>
    @endif

    {{-- CÁC SCRIPT VENDORS / PLUGINS DÙNG CHUNG TOÀN FRONTEND --}}
    <script>
    // 1. Bộ đếm ngược thời gian (Countdown Timer)
    document.querySelectorAll('[data-countdown]').forEach(function (element) {
        var finalDate = element.getAttribute('data-countdown');
        function updateCountdown() {
            var now = new Date().getTime();
            var distance = new Date(finalDate) - now;
            if (distance <= 0) {
                clearInterval(interval);
                element.innerHTML = 'Hết thời gian';
                return;
            }
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            element.innerHTML =
                '<span class="countdown-section"><span class="countdown-amount hover-up">' + days + '</span><span class="countdown-period"> ngày </span></span>' +
                '<span class="countdown-section"><span class="countdown-amount hover-up">' + hours + '</span><span class="countdown-period"> giờ </span></span>' +
                '<span class="countdown-section"><span class="countdown-amount hover-up">' + minutes + '</span><span class="countdown-period"> phút </span></span>' +
                '<span class="countdown-section"><span class="countdown-amount hover-up">' + seconds + '</span><span class="countdown-period"> giây </span></span>';
        }
        updateCountdown();
        var interval = setInterval(updateCountdown, 1000);
    });

    // 2. Tiny Slider (TNS) sản phẩm & modal
    if (typeof tns === 'function') {
        if (document.querySelectorAll('.productModal').length > 0) {
            tns({
                container: '#productModal', items: 1, startIndex: 0,
                navContainer: '#productModalThumbnails', navAsThumbnails: true,
                autoplay: false, autoplayTimeout: 1500, swipeAngle: false, speed: 1500, controls: false, autoplayButtonOutput: false, loop: false
            });
        }
        if (document.querySelectorAll('.product').length > 1) {
            tns({
                container: '#product', items: 1, startIndex: 0,
                navContainer: '#productThumbnails', navAsThumbnails: true,
                autoplay: false, autoplayTimeout: 1500, swipeAngle: false, speed: 1500, controls: false, autoplayButtonOutput: false
            });
        }
    }

    // 3. Phóng to ảnh chi tiết sản phẩm khi hover
    function zoom(e) {
        var zoomer = e.currentTarget;
        var offsetX = e.offsetX ? e.offsetX : e.touches[0].pageX;
        var offsetY = e.offsetY ? e.offsetY : e.touches[0].pageY;
        var x = (offsetX / zoomer.offsetWidth) * 100;
        var y = (offsetY / zoomer.offsetHeight) * 100;
        zoomer.style.backgroundPosition = x + '% ' + y + '%';
    }

    // 4. Khởi tạo Swiper Carousel tự động
    function initializeSwiperCarousels() {
        if (typeof Swiper !== 'function') return;
        const swiperContainers = document.querySelectorAll('.swiper-container');
        swiperContainers.forEach((swiperContainer) => {
            const speed = swiperContainer.getAttribute('data-speed') || 400;
            const spaceBetween = swiperContainer.getAttribute('data-space-between') || 100;
            const paginationEnabled = swiperContainer.getAttribute('data-pagination') === 'true';
            const navigationEnabled = swiperContainer.getAttribute('data-navigation') === 'true';
            const autoplayEnabled = swiperContainer.getAttribute('data-autoplay') === 'true';
            const autoplayDelay = swiperContainer.getAttribute('data-autoplay-delay') || 3000;
            const paginationType = swiperContainer.getAttribute('data-pagination-type') || 'bullets';
            const effect = swiperContainer.getAttribute('data-effect') || 'slide';

            const breakpointsData = swiperContainer.getAttribute('data-breakpoints');
            let breakpoints = {};
            if (breakpointsData) {
                try { breakpoints = JSON.parse(breakpointsData); } catch (error) {}
            }

            const swiperOptions = { speed: parseInt(speed), spaceBetween: parseInt(spaceBetween), breakpoints: breakpoints, effect: effect };
            if (effect === 'fade') { swiperOptions.fadeEffect = { crossFade: true }; }
            if (paginationEnabled) {
                const paginationEl = swiperContainer.querySelector('.swiper-pagination');
                if (paginationEl) {
                    swiperOptions.pagination = { el: paginationEl, type: paginationType, dynamicBullets: true, clickable: true };
                }
            }
            if (navigationEnabled) {
                swiperOptions.navigation = { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' };
            }
            if (autoplayEnabled) {
                swiperOptions.autoplay = { delay: parseInt(autoplayDelay) };
            }
            new Swiper(swiperContainer, swiperOptions);
        });
    }
    document.addEventListener('DOMContentLoaded', initializeSwiperCarousels);

    // 5. Client Form Validation
    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>


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
    @endif

</body>

</html>