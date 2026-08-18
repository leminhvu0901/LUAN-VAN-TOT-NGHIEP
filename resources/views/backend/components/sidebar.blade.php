<aside id="admin-sidebar"
    class="w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col h-full fixed lg:static inset-y-0 left-0 z-30 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out group/sidebar">

    {{-- Logo --}}
    @php
        $shopLogo = \App\Models\Setting::getValue('store_logo', '/images/logo/black.png');
        $shopName = \App\Models\Setting::getValue('store_name', 'Happy Tea');
    @endphp
    <div class="flex items-center pl-8 group-[.is-collapsed]/sidebar:px-0 group-[.is-collapsed]/sidebar:justify-center border-b border-gray-100 h-[65px]">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center min-w-0">
            <img src="{{ asset($shopLogo) }}"
                 alt="{{ $shopName }}"
                 class="h-10 w-auto max-w-[160px] object-contain flex-shrink-0 group-[.is-collapsed]/sidebar:h-8 group-[.is-collapsed]/sidebar:max-w-[40px]">
        </a>
    </div>

    {{-- Danh sách menu --}}
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-6 px-4 group-[.is-collapsed]/sidebar:px-2 flex flex-col gap-1">

        {{-- Menu Tổng quan --}}
        <a href="{{ route('admin.dashboard') }}" title="Tổng quan"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.dashboard') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-table-cells-large text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Tổng quan</span>
        </a>

        {{-- Menu Đơn hàng --}}
        <a href="{{ route('admin.orders.index') }}" title="Đơn hàng"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.orders.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-cart-shopping text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.orders.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đơn hàng</span>
        </a>

        {{-- Menu Sản phẩm --}}
        <a href="{{ route('admin.products.index') }}" title="Sản phẩm"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.products.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-box-archive text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.products.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Sản phẩm</span>
        </a>

        {{-- Menu Quản lý Kho --}}
        <a href="{{ route('admin.materials.index') }}" title="Quản lý Kho"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.materials.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-warehouse text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.materials.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Quản lý Kho</span>
        </a>

        {{-- Menu Danh mục --}}
        <a href="{{ route('admin.categories.index') }}" title="Danh mục"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.categories.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-shapes text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.categories.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Danh mục</span>
        </a>

        {{-- Menu Khuyến mãi --}}
        <a href="{{ route('admin.promotions.index') }}" title="Khuyến mãi"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.promotions.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-tag text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.promotions.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Khuyến mãi</span>
        </a>

        {{-- Menu Đánh giá --}}
        <a href="{{ route('admin.reviews.index') }}" title="Đánh giá"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.reviews.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-star text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.reviews.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đánh giá</span>
        </a>

        {{-- Menu Khách hàng --}}
        <a href="{{ route('admin.customers.index') }}" title="Khách hàng"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.customers.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-user-group text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.customers.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Khách hàng</span>
        </a>

        {{-- Menu Nhân viên --}}
        <a href="{{ route('admin.staff_accounts.index') }}" title="Nhân viên"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.staff_accounts.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-id-badge text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.staff_accounts.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Nhân viên</span>
        </a>

        {{-- Menu Thống kê giao hàng --}}
        <a href="{{ route('admin.delivery_statistics.index') }}" title="Thống kê giao hàng"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.delivery_statistics.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-truck-fast text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.delivery_statistics.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Thống kê giao hàng</span>
        </a>

        {{-- Menu Banner --}}
        <a href="{{ route('admin.banners.index') }}" title="Banner"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.banners.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-images text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.banners.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Banner</span>
        </a>

        {{-- Menu Báo cáo --}}
        <a href="{{ route('admin.reports.index') }}" title="Báo cáo"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.reports.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-chart-column text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.reports.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Báo cáo</span>
        </a>

        {{-- Menu Cài đặt --}}
        <a href="{{ route('admin.settings.index') }}" title="Cài đặt"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('admin.settings.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <i class="fa-solid fa-gear text-[18px] w-6 text-center shrink-0 {{ request()->routeIs('admin.settings.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Cài đặt</span>
        </a>

    </div>

    {{-- Nút thu gọn và đăng xuất --}}
    <div
        class="p-4 border-t border-gray-100 flex flex-col gap-2 group-[.is-collapsed]/sidebar:px-2 group-[.is-collapsed]/sidebar:py-4">

        {{-- Nút Đóng Sidebar --}}
        <button id="mobile-close-btn" title="Đóng Sidebar"
            class="flex lg:hidden items-center gap-2 px-4 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <i class="fa-solid fa-xmark text-[18px]"></i>
            <span class="whitespace-nowrap">Đóng menu</span>
        </button>

        {{-- Nút Thu gọn Sidebar --}}
        <button id="desktop-collapse-btn" title="Thu gọn Sidebar"
            class="hidden lg:flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <i class="fa-solid fa-angles-left text-[18px] transition-transform duration-300"
                id="collapse-icon"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Thu gọn</span>
        </button>

        <a href="{{ route('logout') }}" title="Đăng xuất"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 bg-danger-light text-danger rounded-lg transition-colors hover:bg-red-200 font-medium w-full">
            <i class="fa-solid fa-right-from-bracket text-[18px]"></i>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đăng xuất</span>
        </a>
    </div>
</aside>
