<!-- Thanh Topbar -->
<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8"
    data-purpose="top-header">
    <!-- Nút Hamburger: Nút này dùng để mở Sidebar -->
    <button id="mobile-menu-btn" class="lg:hidden p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
        <i class="fa-solid fa-bars text-lg"></i>
    </button>

    <!-- Khu vực góc phải màn hình -->
    <div class="flex items-center gap-4 lg:gap-6 ml-auto">
        <!-- Khối Thông tin Người dùng -->
        <div class="flex items-center gap-2 cursor-pointer">

            <!-- Avatar -->
            @php
                $topbarAvatarUrl = Auth::user()->avatar
                    ? (avatar_url(Auth::user()->avatar))
                    : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=10b981&color=fff';
            @endphp
            <div class="w-8 h-8 rounded-full overflow-hidden border border-gray-200">
                <img alt="{{ Auth::user()->name }}" class="w-full h-full object-cover"
                    src="{{ $topbarAvatarUrl }}" />
            </div>

            <!-- Tên User đang đăng nhập: Lấy từ Session thông qua -->
            <span class="text-sm font-medium text-gray-700 hidden lg:block">{{ Auth::user()->name }}</span>
        </div>
    </div>
</header>