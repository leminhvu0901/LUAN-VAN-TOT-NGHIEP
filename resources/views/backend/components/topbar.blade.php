<!-- Thanh Topbar (Header trên cùng của trang Admin) -->
<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8"
    data-purpose="top-header">
    <!-- Nút Hamburger (3 gạch ngang): Nút này dùng để mở Sidebar. Chỉ hiện trên điện thoại (lg:hidden), trên PC sẽ bị ẩn đi -->
    <button id="mobile-menu-btn" class="lg:hidden p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
        <span class="material-symbols-outlined">menu</span>
    </button>

    <!-- Khu vực góc phải màn hình (Đẩy sát sang phải nhờ class 'ml-auto') -->
    <div class="flex items-center gap-4 lg:gap-6 ml-auto">
        <!-- Khối Thông tin Người dùng (Avatar + Tên) -->
        <div class="flex items-center gap-2 cursor-pointer">

            <!-- Avatar: dùng ảnh đã tải lên nếu có, nếu chưa thì fallback sang ui-avatars.com theo chữ cái đầu tên -->
            @php
                $topbarAvatarUrl = Auth::user()->avatar
                    ? (str_starts_with(Auth::user()->avatar, 'http') ? Auth::user()->avatar : asset('images/avatars/' . Auth::user()->avatar))
                    : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=10b981&color=fff';
            @endphp
            <div class="w-8 h-8 rounded-full overflow-hidden border border-gray-200">
                <img alt="{{ Auth::user()->name }}" class="w-full h-full object-cover"
                    src="{{ $topbarAvatarUrl }}" />
            </div>

            <!-- Tên User đang đăng nhập: Lấy từ Session thông qua Auth::user()->name. (Ẩn trên điện thoại, chỉ hiện trên PC) -->
            <span class="text-sm font-medium text-gray-700 hidden lg:block">{{ Auth::user()->name }}</span>
        </div>
    </div>
</header>