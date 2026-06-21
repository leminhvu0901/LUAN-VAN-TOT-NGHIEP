<!DOCTYPE html>
<html lang="vi">

<head>
    <title>@yield('title', 'Happy Tea')</title>
    @include('components.head')
</head>

<body class="@yield('body_class')">

    @include('components.navbar')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    {{-- Modals xác thực --}}
    @include('auth.login')
    @include('auth.register')
    @include('auth.forgot-password')
    @include('auth.verify-otp')

    {{-- Modal tài khoản (chỉ khi đã đăng nhập) --}}
    @auth
        @include('components.user-profile-modal')
    @endauth

    {{-- Scripts --}}
    <!-- Libs JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tiny-slider@2.9.4/dist/min/tiny-slider.js"></script>
    <script src="{{ asset('js/frontend/main.js') }}"></script>
    <script src="{{ asset('js/vendors/countdown.js') }}"></script>
    <script src="{{ asset('js/vendors/tns-slider.js') }}"></script>
    <script src="{{ asset('js/vendors/zoom.js') }}"></script>
    <script src="{{ asset('js/vendors/swiper.js') }}"></script>
    <script src="{{ asset('js/vendors/validation.js') }}"></script>

    @stack('scripts')

</body>

</html>