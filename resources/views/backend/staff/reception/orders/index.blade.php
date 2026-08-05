@extends('backend.layouts.app')

@section('title', 'Danh sách Đơn hàng - Nhân viên')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 orders-page staff-orders-page">

        {{-- Phần tiêu đề trang --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Danh sách Đơn hàng</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi và cập nhật trạng thái đơn hàng trên hệ thống Happy Tea.</p>
            </div>
        </div>

        {{-- Khu vực các thẻ thống kê nhanh --}}
        <div id="stats-container" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 shrink-0">
            @include('backend.staff.reception.orders.partials.stats')
        </div>

        {{-- Form tìm kiếm và lọc dữ liệu --}}
        <div class="bg-white p-3 sm:p-4 rounded-xl organic-shadow border border-gray-100 flex flex-col gap-4 relative z-20">
            <div class="flex items-center justify-between xl:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('search-form')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span> <span class="hidden sm:inline">Bộ lọc</span>
                </button>
            </div>

            <form id="search-form" action="{{ route('staff.reception.orders.index') }}" method="GET"
                class="hidden xl:flex flex-col w-full transition-all orders-page-filter-form">
                
                <div class="flex flex-wrap items-center gap-3 w-full">
                    <div class="orders-page-search w-full sm:w-[calc(50%-0.375rem)] lg:w-auto lg:flex-1 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[20px] shrink-0">search</span>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Mã đơn, Tên, SĐT...">
                    </div>

                    <select name="status" id="status-select" class="orders-page-status w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>

                    <select name="sort" id="sort-select" class="orders-page-sort w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Cũ nhất</option>
                    </select>

                    <div class="orders-page-date w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] shrink-0">calendar_today</span>
                        <input type="text" name="date_from" id="date-from-input" value="{{ request('date_from') }}"
                            class="orders-date-picker bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Từ ngày" placeholder="Từ ngày">
                    </div>

                    <div class="orders-page-date w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 flex items-center gap-2 px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <span class="material-symbols-outlined text-gray-400 text-[18px] shrink-0">calendar_today</span>
                        <input type="text" name="date_to" id="date-to-input" value="{{ request('date_to') }}"
                            class="orders-date-picker bg-transparent border-none focus:ring-0 text-sm font-medium w-full outline-none text-gray-700" title="Đến ngày" placeholder="Đến ngày">
                    </div>

                    <div class="orders-page-actions w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                        <button type="submit" class="flex-1 lg:flex-none px-5 py-1.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors organic-shadow">
                            Lọc
                        </button>
                        <a href="{{ route('staff.reception.orders.index') }}"
                            class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-1.5 sm:py-2 bg-gray-100 text-gray-600 border border-gray-200 font-medium text-sm rounded-lg hover:bg-gray-200 transition-colors organic-shadow">
                            <span class="material-symbols-outlined text-[20px] shrink-0">filter_alt_off</span>
                            <span class="whitespace-nowrap font-medium">Xóa lọc</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Khu vực chứa bảng dữ liệu --}}
        <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                @include('backend.staff.reception.orders.partials.table')
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/backend/staff/reception/orders/index.js') }}?v={{ time() }}"></script>
    @endpush
@endsection
