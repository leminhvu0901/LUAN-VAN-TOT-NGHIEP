@extends('backend.layouts.app')

@section('title', 'Danh sách Đơn hàng - Admin')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

        {{-- Phần tiêu đề trang và nút chức năng xuất báo cáo --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Danh sách Đơn hàng</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi và quản lý các đơn đặt hàng từ hệ thống Happy Tea.</p>
            </div>
        </div>
        {{-- Khu vực các thẻ thống kê nhanh hiển thị ở đầu trang --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 sm:gap-4 shrink-0">
            @include('backend.orders.partials.stats')
        </div>

        {{-- Form tìm kiếm và lọc dữ liệu --}}
        <div class="bg-white p-3 sm:p-4 rounded-xl organic-shadow border border-gray-100">
            <form id="search-form" action="{{ route('admin.orders.index') }}" method="GET"
                class="flex flex-col xl:flex-row gap-3 items-stretch xl:items-center w-full">
                
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 items-stretch sm:items-center flex-1 w-full xl:w-auto">
                    
                    <div class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:flex-1 xl:max-w-[280px] relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[20px]">search</span>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Mã đơn, Tên, SĐT...">
                    </div>

                    <select name="status" class="px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>

                    <select name="sort" class="px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none w-full sm:w-auto shrink-0 transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Cũ nhất</option>
                    </select>

                    <span class="hidden 2xl:block text-gray-300 mx-1 shrink-0">|</span>

                    <div class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-auto shrink-0 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[18px]">calendar_today</span>
                        <input type="date" name="date_from" id="date-from-input" value="{{ request('date_from') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Từ ngày">
                    </div>

                    <span class="hidden sm:block text-gray-400 font-medium shrink-0">-</span>

                    <div class="flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 w-full sm:w-auto shrink-0 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[18px]">calendar_today</span>
                        <input type="date" name="date_to" id="date-to-input" value="{{ request('date_to') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Đến ngày">
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full xl:w-auto shrink-0">
                    <a href="{{ route('admin.orders.index') }}"
                        class="flex-1 xl:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow">
                        <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
                        Xóa lọc
                    </a>
                </div>
            </form>
        </div>

        {{-- Khu vực chứa bảng dữ liệu --}}
        <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col flex-1 min-h-[500px]">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative">
                {{-- Biểu tượng Loading hiển thị lên khi đang gửi request AJAX --}}
                <div id="table-loader" class="absolute inset-0 bg-white/60 z-20 hidden items-center justify-center">
                    <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div class="flex-1 overflow-x-auto custom-scrollbar relative">
                    @include('backend.orders.partials.table')
                </div>
            </div>
        </div>

       

    </div>



    @push('scripts')
        <script src="{{ asset('js/backend/orders-index.js') }}"></script>
    @endpush

@endsection