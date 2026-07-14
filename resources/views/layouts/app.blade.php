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
    @include('auth.login')
    @include('auth.register')
    @include('auth.forgot-password')
    @include('auth.verify-otp')



    {{-- ===== SCRIPTS ===== --}}
    <!-- Thư viện Javascript bên thứ 3 (Thanh cuộn mượt, Slider/Carousel) -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>

    <!-- Code Javascript tự viết chung cho cả hệ thống (Giỏ hàng, Yêu thích...) -->
    <script src="{{ asset('js/frontend/main.js') }}"></script>

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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '<span style="font-size: 18px; color: #1f2937; font-weight: bold;">Tài khoản bị khóa</span>',
                    html: '<p style="color:#6b7280; font-size: 13px; line-height: 1.4; margin-top: 5px;">Tài khoản của bạn đã bị khóa tạm thời. Bạn không thể thao tác trên hệ thống.<br><br>Vui lòng liên hệ Admin để được hỗ trợ.</p>',
                    icon: 'warning',
                    iconColor: '#ef4444',
                    width: '320px',
                    padding: '1.25rem',
                    showCancelButton: true,
                    confirmButtonText: 'Liên hệ Zalo',
                    cancelButtonText: 'Đăng xuất',
                    confirmButtonColor: '#2563eb', // Blue
                    cancelButtonColor: '#9ca3af', // Gray
                    reverseButtons: true, // Đảo ngược vị trí nút để nút chính (Liên hệ) nằm bên phải
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCloseButton: false,
                    backdrop: `rgba(15, 23, 42, 0.8)`
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open('https://zalo.me/0388359330', '_blank');
                        setTimeout(() => {
                            window.location.href = '/logout';
                        }, 500);
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = '/logout';
                    }
                });
            });
        </script>
    @endif

</body>

</html>