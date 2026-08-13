<aside id="admin-sidebar"
    class="w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col h-full fixed lg:static inset-y-0 left-0 z-30 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out group/sidebar">

    {{-- Logo --}}
    @php
        $shopLogo = \App\Models\Setting::getValue('store_logo', '/images/logo/black.png');
        $shopName = \App\Models\Setting::getValue('store_name', 'Happy Tea');
    @endphp
    <div class="flex items-center pl-8 group-[.is-collapsed]/sidebar:px-0 group-[.is-collapsed]/sidebar:justify-center border-b border-gray-100 h-[65px]">
        <a href="{{ route('staff.delivery.dashboard') }}" class="flex items-center min-w-0">
            <img src="{{ asset($shopLogo) }}"
                 alt="{{ $shopName }}"
                 class="h-10 w-auto max-w-[160px] object-contain flex-shrink-0 group-[.is-collapsed]/sidebar:h-8 group-[.is-collapsed]/sidebar:max-w-[40px]">
        </a>
    </div>

    {{-- Danh sách menu --}}
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-6 px-4 group-[.is-collapsed]/sidebar:px-2 flex flex-col gap-1">

        <a href="{{ route('staff.delivery.dashboard') }}" title="Tổng quan"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('staff.delivery.dashboard') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('staff.delivery.dashboard') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}">grid_view</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Tổng quan</span>
        </a>

        <a href="{{ route('staff.delivery.orders.index', ['tab' => 'assigned']) }}" title="Đơn được giao"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('staff.delivery.orders.index') && request('tab', 'assigned') === 'assigned' ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">inventory</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đơn được giao</span>
        </a>

        <a href="{{ route('staff.delivery.orders.index', ['tab' => 'shipping']) }}" title="Đang giao"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request('tab') === 'shipping' ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">local_shipping</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đang giao</span>
        </a>

        <a href="{{ route('staff.delivery.orders.index', ['tab' => 'history']) }}" title="Lịch sử giao hàng"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request('tab') === 'history' ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover/link:text-gray-600">history</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Lịch sử giao hàng</span>
        </a>

        <a href="{{ route('staff.delivery.profile.edit') }}" title="Hồ sơ cá nhân"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-3 group-[.is-collapsed]/sidebar:gap-0 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 {{ request()->routeIs('staff.delivery.profile.*') ? 'bg-sidebar-active text-sidebar-active-text' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors group/link font-medium">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('staff.delivery.profile.*') ? 'text-sidebar-active-text' : 'text-gray-400 group-hover/link:text-gray-600' }}">person</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Hồ sơ cá nhân</span>
        </a>

    </div>

    {{-- Nút thu gọn và đăng xuất --}}
    <div class="p-4 border-t border-gray-100 flex flex-col gap-2 group-[.is-collapsed]/sidebar:px-2 group-[.is-collapsed]/sidebar:py-4">
        <button id="mobile-close-btn" title="Đóng Sidebar"
            class="flex lg:hidden items-center gap-2 px-4 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <span class="material-symbols-outlined text-[20px]">close</span>
            <span class="whitespace-nowrap">Đóng menu</span>
        </button>

        <button id="desktop-collapse-btn" title="Thu gọn Sidebar"
            class="hidden lg:flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors font-medium w-full">
            <span class="material-symbols-outlined text-[20px] transition-transform duration-300" id="collapse-icon">menu_open</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Thu gọn</span>
        </button>

        <a href="{{ route('logout') }}" title="Đăng xuất"
            class="flex items-center group-[.is-collapsed]/sidebar:justify-center gap-2 px-4 group-[.is-collapsed]/sidebar:px-0 py-2.5 bg-danger-light text-danger rounded-lg transition-colors hover:bg-red-200 font-medium w-full">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="group-[.is-collapsed]/sidebar:hidden whitespace-nowrap">Đăng xuất</span>
        </a>
    </div>
</aside>
