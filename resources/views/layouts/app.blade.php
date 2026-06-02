<!DOCTYPE html>
<html lang="vi">
<head>
    @include('partials.head')
</head>
<body>

    @include('components.navbar')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    {{-- Thêm các modal dùng chung --}}
    @include('partials.modal-product')
    @include('auth.login')
    @include('auth.register')
    @include('auth.forgot-password')

    @include('partials.scripts')
</body>
</html>
