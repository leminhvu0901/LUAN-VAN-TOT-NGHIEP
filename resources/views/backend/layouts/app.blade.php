<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- Cấu hình Title động: Sẽ lấy giá trị từ trang con nếu có khai báo @section('title'), nếu không có sẽ lấy chuỗi mặc định 'Admin Dashboard...' --}}
    <title>@yield('title', 'Admin Dashboard - Happy Tea')</title>
    
    {{-- Kết nối Google Fonts để lấy font chữ Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Thư viện Material Icons (Bộ icon chính của web) --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    {{-- Nhúng bộ thư viện Tailwind CSS qua CDN (Dùng để viết CSS nhanh qua class) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- File cấu hình màu sắc riêng (Màu primary, các màu sidebar) của Tailwind --}}
    <script src="{{ asset('js/backend/tailwind-config.js') }}"></script>
    
    {{-- File CSS chung cho giao diện Admin --}}
    <link rel="stylesheet" href="{{ asset('css/backend/admin.css') }}">
    
    {{-- Khe hở @stack('styles'): Cho phép các trang con đẩy thêm mã CSS của riêng chúng vào đây bằng lệnh @push('styles') --}}
    @stack('styles')
</head>

{{-- Thẻ body khóa cuộn ngoài cùng bằng lớp overflow-hidden --}}
<body class="flex h-screen overflow-hidden antialiased">
    
    {{-- 1. NHÚNG SIDEBAR: Lấy code từ file backend/components/sidebar.blade.php gắn vào vị trí này --}}
    @include('backend.components.sidebar')

    {{-- 2. KHU VỰC NỘI DUNG CHÍNH (Phần không gian còn lại bên phải Sidebar) --}}
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">
        
        {{-- Header ẩn/hiện trên Điện thoại (Mobile Header) --}}
        {{-- Chỉ hiển thị (flex) trên màn hình nhỏ, và bị ẩn (lg:hidden) trên màn hình lớn --}}
        <header class="lg:hidden flex items-center justify-between p-4 bg-white border-b border-gray-200">
            <div class="flex items-center gap-2 text-primary font-bold text-xl">
                <span class="material-symbols-outlined">local_cafe</span>
                Happy Tea
            </div>
            {{-- Nút bấm Hamburger để mở Sidebar trên mobile --}}
            <button id="mobile-menu-btn" class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </header>
        
        {{-- Khu vực chứa nội dung động (Có thanh cuộn overflow-y-auto độc lập) --}}
        <div id="main-content-area" class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <div id="main-content-inner" class="max-w-7xl mx-auto h-full flex flex-col">
                {{-- Lỗ hổng @yield('content'): Nơi mà toàn bộ nội dung của trang con (như show.blade.php hay index.blade.php) sẽ được đổ vào --}}
                @yield('content')
            </div>
        </div>
    </main>

    {{-- 3. MÀN ĐEN CHE PHỦ (Overlay) --}}
    {{-- Xuất hiện khi mở Sidebar trên Mobile, nhấp vào màn đen này sẽ tự động đóng Sidebar --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden lg:hidden transition-opacity"></div>

    {{-- File Javascript xử lý hiệu ứng đóng/mở Sidebar --}}
    <script src="{{ asset('js/backend/layout.js') }}"></script>
    
    {{-- Khe hở @stack('scripts'): Cho phép các trang con đẩy thêm mã JS của riêng chúng vào đây --}}
    @stack('scripts')
</body>
</html>
