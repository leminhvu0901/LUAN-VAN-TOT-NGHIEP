
<aside id="admin-sidebar" class="w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col h-full fixed lg:static inset-y-0 left-0 z-30 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out group/sidebar">
    
    {{-- PHẦN 1: LOGO --}}
    <div class="p-3 flex items-center justify-center border-b border-gray-100 h-[65px]">
        <img src="{{ asset('images/logo/black1.svg') }}" alt="Happy Tea Logo" class="h-10 w-auto group-[.is-collapsed]/sidebar:hidden">
        <span class="material-symbols-outlined text-[32px] text-green-600 hidden group-[.is-collapsed]/sidebar:block">local_cafe</span>
    </div>

    {{-- PHẦN 2: DANH SÁCH MENU (Có thanh cuộn dọc overflow-y-auto nếu menu quá dài) --}}
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-6 px-4 group-[.is-collapsed]/sidebar:px-2 flex flex-col gap-1">
        
        {{-- Menu Tổng quan --}}
        <a href="{{ route('admin.dashboard') }}" title="Tổng quan" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.dashboard') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('admin.dashboard') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}">grid_view</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Tổng quan</span>
        </a>

        {{-- Menu Đơn hàng --}}
        <a href="{{ route('admin.orders.index') }}" title="Đơn hàng" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.orders.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('admin.orders.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}">shopping_cart</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đơn hàng</span>
        </a>

        {{-- Menu Sản phẩm --}}
        <a href="#" title="Sản phẩm" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">inventory_2</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Sản phẩm</span>
        </a>

        {{-- Menu Quản lý Kho --}}
        <a href="{{ route('admin.materials.index') }}" title="Quản lý Kho" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.materials.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('admin.materials.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}">warehouse</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Quản lý Kho</span>
        </a>

        {{-- Menu Danh mục --}}
        <a href="#" title="Danh mục" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">category</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Danh mục</span>
        </a>

        {{-- Menu Khuyến mãi --}}
        <a href="#" title="Khuyến mãi" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">local_offer</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Khuyến mãi</span>
        </a>

        {{-- Menu Đánh giá --}}
        <a href="#" title="Đánh giá" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">reviews</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đánh giá</span>
        </a>

        {{-- Menu Khách hàng --}}
        <a href="#" title="Khách hàng" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">group</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Khách hàng</span>
        </a>

        {{-- Menu Banner --}}
        <a href="#" title="Banner" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">view_carousel</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Banner</span>
        </a>

        {{-- Menu Báo cáo --}}
        <a href="#" title="Báo cáo" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">bar_chart</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Báo cáo</span>
        </a>

        {{-- Menu Cài đặt --}}
        <a href="#" title="Cài đặt" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">settings</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Cài đặt</span>
        </a>

    </div>

    {{-- PHẦN 3: NÚT THU GỌN VÀ ĐĂNG XUẤT --}}
    <div class="p-4 border-t border-gray-100 flex flex-col gap-2 group-[.is-collapsed]/sidebar:px-2 group-[.is-collapsed]/sidebar:py-4">
        
        {{-- Nút Đóng Sidebar (Chỉ hiện trên Mobile) --}}
        <button id="mobile-close-btn" title="Đóng Sidebar" class="flex lg:hidden items-center gap-2 px-4 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <span class="material-symbols-outlined text-[20px]">close</span>
            <span class="whitespace-nowrap">Đóng menu</span>
        </button>

        {{-- Nút Thu gọn Sidebar (Chỉ hiện trên Desktop) --}}
        <button id="desktop-collapse-btn" title="Thu gọn Sidebar" class="hidden lg:flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <span class="material-symbols-outlined text-[20px] transition-transform duration-300" id="collapse-icon">menu_open</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Thu gọn</span>
        </button>

        <a href="{{ route('logout') }}" title="Đăng xuất" class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 bg-danger-light text-danger rounded-lg transition-colors hover:bg-red-200 font-medium w-full">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đăng xuất</span>
        </a>
    </div>
</aside>
