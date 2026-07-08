{{-- Thẻ <aside> định nghĩa khu vực thanh bên (Sidebar). 
     Sử dụng các class của Tailwind CSS để:
     - w-64: Chiều rộng cố định 16rem (256px)
     - fixed lg:static: Mặc định cố định (nổi lên trên) ở màn hình nhỏ, nhưng trở thành phần tử tĩnh (nằm đúng vị trí) trên màn hình lớn.
     - transform -translate-x-full lg:translate-x-0: Ẩn sidebar bằng cách dịch sang trái 100% trên mobile, và hiện lại trên màn hình lớn (lg). 
--}}
<aside id="admin-sidebar" class="w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col h-full fixed lg:static inset-y-0 left-0 z-30 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    
    {{-- PHẦN 1: LOGO (Chỉ hiện ở màn hình máy tính lg:flex, ẩn ở mobile) --}}
    <div class="h-16 flex items-center px-6 border-b border-gray-100 hidden lg:flex">
        <a href="{{ url('/') }}" class="flex items-center gap-2 text-primary font-bold text-xl">
            {{-- Icon lấy từ bộ thư viện Material Symbols Outlined --}}
            <span class="material-symbols-outlined text-primary text-2xl">local_cafe</span>
            <span>Happy Tea</span>
        </a>
    </div>

    {{-- PHẦN 2: DANH SÁCH MENU (Có thanh cuộn dọc overflow-y-auto nếu menu quá dài) --}}
    <div class="flex-1 overflow-y-auto py-6 px-4 flex flex-col gap-1">
        
        {{-- Menu Tổng quan (Chưa gắn link) --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">grid_view</span>
            <span>Tổng quan</span>
        </a>

        {{-- Menu Đơn hàng (Đang được kích hoạt bằng class 'bg-sidebar-active' và chữ 'text-sidebar-active-text') --}}
        <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 bg-sidebar-active text-sidebar-active-text rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px]">shopping_cart</span>
            <span>Đơn hàng</span>
        </a>

        {{-- Menu Sản phẩm --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">inventory_2</span>
            <span>Sản phẩm</span>
        </a>

        {{-- Menu Danh mục --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">category</span>
            <span>Danh mục</span>
        </a>

        {{-- Menu Khuyến mãi --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">local_offer</span>
            <span>Khuyến mãi</span>
        </a>

        {{-- Menu Đánh giá --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">reviews</span>
            <span>Đánh giá</span>
        </a>

        {{-- Menu Khách hàng --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">group</span>
            <span>Khách hàng</span>
        </a>

        {{-- Menu Banner --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">view_carousel</span>
            <span>Banner</span>
        </a>

        {{-- Menu Báo cáo --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">bar_chart</span>
            <span>Báo cáo</span>
        </a>

        {{-- Menu Cài đặt --}}
        <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors group font-medium">
            <span class="material-symbols-outlined text-[22px] text-gray-400 group-hover:text-gray-600">settings</span>
            <span>Cài đặt</span>
        </a>

    </div>

    {{-- PHẦN 3: NÚT ĐĂNG XUẤT (Nằm cố định ở cuối thanh Sidebar) --}}
    <div class="p-4 border-t border-gray-100">
        <a href="#" class="flex items-center justify-center gap-2 px-4 py-2.5 bg-danger-light text-danger rounded-lg transition-colors hover:bg-red-200 font-medium w-full">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>
