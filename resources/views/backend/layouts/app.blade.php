<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Cấu hình Title động: Sẽ lấy giá trị từ trang con nếu có khai báo @section('title'), nếu không có sẽ lấy chuỗi
    mặc định 'Admin Dashboard...' --}}
    <title>@yield('title', 'Admin Dashboard - Happy Tea')</title>

    {{-- Kết nối Google Fonts để lấy font chữ Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/backend/tailwind-config.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/backend/admin.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    @stack('styles')
</head>

{{-- Thẻ body khóa cuộn ngoài cùng bằng lớp overflow-hidden --}}

<body class="flex h-screen overflow-hidden antialiased">

    {{-- 1. NHÚNG SIDEBAR: --}}
    @include('backend.components.sidebar')

    {{-- 2. KHU VỰC NỘI DUNG CHÍNH --}}
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">
        @include('backend.components.topbar')

        <div id="main-content-area" class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <div id="main-content-inner" class="max-w-7xl mx-auto min-h-full flex flex-col">
                @yield('content')
            </div>
        </div>
    </main>

    {{-- 3. MÀN ĐEN CHE PHỦ (Overlay) --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden lg:hidden transition-opacity">
    </div>

    {{-- File Javascript xử lý hiệu ứng đóng/mở Sidebar --}}
    <script src="{{ asset('js/backend/layout.js') }}"></script>

    {{-- Thư viện bên thứ 3 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    {{-- Global Flash Messages Script --}}
    @include('backend.partials.flash_messages')

    {{-- Khe hở @stack('scripts'): Cho phép các trang con đẩy thêm mã JS của riêng chúng vào đây --}}
    @stack('scripts')
</body>

</html>