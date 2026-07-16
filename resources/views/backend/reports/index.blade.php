@extends('backend.layouts.app')

@section('title', 'Báo cáo & Thống kê')

@section('content')
    <div class="reports-page p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- 1. Tiêu đề trang & Xuất báo cáo -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
            <div class="w-full">
                <h2 class="text-[22px] sm:text-3xl font-bold text-gray-900 tracking-tight">Báo cáo & Thống kê</h2>
                <p class="text-gray-500 text-sm mt-1 break-words">Theo dõi hoạt động kinh doanh, doanh thu, đơn hàng và tồn kho của cửa hàng.</p>
            </div>

            <div class="flex items-center w-full sm:w-auto justify-end">
                <button type="button" id="export-btn"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold text-sm hover:bg-emerald-700 transition-all border border-emerald-600 shadow-sm min-h-[44px]"
                    title="Xuất báo cáo PDF/Excel">
                    <span class="material-symbols-outlined text-[20px] shrink-0">download</span>
                    <span class="whitespace-nowrap">Xuất báo cáo</span>
                </button>
            </div>
        </div>

        <!-- 2. Bộ lọc thời gian -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm">
            <form id="filter-form" action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">

                    <!-- Thời gian Preset -->
                    <div class="flex flex-col gap-2 relative">
                        <label for="preset-select" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mốc thời gian</label>
                        
                        <!-- Select thật ẩn đi để submit form và giữ nguyên JS selector -->
                        <select name="preset" id="preset-select" class="hidden">
                            <option value="today" {{ $preset == 'today' ? 'selected' : '' }}>Hôm nay</option>
                            <option value="7_days" {{ $preset == '7_days' ? 'selected' : '' }}>7 ngày gần nhất</option>
                            <option value="30_days" {{ $preset == '30_days' ? 'selected' : '' }}>30 ngày gần nhất</option>
                            <option value="this_month" {{ $preset == 'this_month' ? 'selected' : '' }}>Tháng này</option>
                            <option value="last_month" {{ $preset == 'last_month' ? 'selected' : '' }}>Tháng trước</option>
                            <option value="this_year" {{ $preset == 'this_year' ? 'selected' : '' }}>Năm nay</option>
                            <option value="custom" {{ $preset == 'custom' ? 'selected' : '' }}>Khoảng ngày tùy chọn</option>
                        </select>

                        <!-- Custom Dropdown Wrapper -->
                        <div class="relative w-full reports-dropdown-wrapper" id="custom-preset-dropdown">
                            <button type="button" class="w-full flex items-center justify-between border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700 font-medium text-sm shadow-sm hover:border-gray-300 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all cursor-pointer h-11 md:h-10 reports-dropdown-btn">
                                <span class="selected-label">Hôm nay</span>
                                <span class="material-symbols-outlined text-[20px] text-gray-500 dropdown-arrow transition-transform duration-200">expand_more</span>
                            </button>
                            
                            <!-- Custom Options Menu -->
                            <div class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-[240px] overflow-y-auto hidden reports-dropdown-menu">
                                <div class="py-1">
                                    <button type="button" data-value="today" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>Hôm nay</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="7_days" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>7 ngày gần nhất</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="30_days" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>30 ngày gần nhất</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="this_month" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>Tháng này</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="last_month" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>Tháng trước</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="this_year" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>Năm nay</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                    <button type="button" data-value="custom" class="dropdown-item w-full text-left px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-slate-50 transition-colors flex items-center justify-between min-h-[42px]">
                                        <span>Khoảng ngày tùy chọn</span>
                                        <span class="material-symbols-outlined text-[18px] text-emerald-600 hidden select-check">check</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date From (Ẩn/Hiện tùy vào preset) -->
                    <div class="flex flex-col gap-2 {{ $preset == 'custom' ? '' : 'hidden' }}" id="date-from-container">
                        <label for="date_from" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Từ ngày</label>
                        <div class="relative w-full shadow-sm rounded-xl">
                            <input type="text" name="date_from" id="date_from"
                                value="{{ request('date_from', $start->toDateString()) }}"
                                class="w-full border border-gray-200 rounded-xl pl-3 pr-10 py-2.5 outline-none text-gray-700 text-base md:text-sm font-medium bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all cursor-pointer h-11 md:h-10"
                                placeholder="Chọn ngày bắt đầu">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Date To (Ẩn/Hiện tùy vào preset) -->
                    <div class="flex flex-col gap-2 {{ $preset == 'custom' ? '' : 'hidden' }}" id="date-to-container">
                        <label for="date_to" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Đến ngày</label>
                        <div class="relative w-full shadow-sm rounded-xl">
                            <input type="text" name="date_to" id="date_to"
                                value="{{ request('date_to', $end->toDateString()) }}"
                                class="w-full border border-gray-200 rounded-xl pl-3 pr-10 py-2.5 outline-none text-gray-700 text-base md:text-sm font-medium bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all cursor-pointer"
                                placeholder="Chọn ngày kết thúc">
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Nút hành động -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full">
                        <button type="submit"
                            class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-emerald-100 hover:shadow-emerald-200 border border-emerald-600 min-h-[44px] h-11 sm:h-10">
                            <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                            <span>Áp dụng</span>
                        </button>

                        <a href="{{ route('admin.reports.index', ['preset' => '30_days']) }}" id="btn-clear-filter"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-50 text-slate-600 border border-slate-200/85 font-semibold text-sm rounded-xl hover:bg-slate-100 hover:text-slate-800 transition-colors shadow-sm min-h-[44px] h-11 sm:h-10"
                            style="display: {{ $preset != '30_days' ? 'flex' : 'none' }};">
                            <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                            <span>Xóa lọc</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- 3. Khu vực Nội dung báo cáo (Chứa file partials/content.blade.php) -->
        <div id="reports-content-container" class="relative">
            <div id="reports-loader"
                class="absolute inset-0 bg-white/60 z-20 hidden items-center justify-center rounded-2xl transition-all duration-300">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-gray-500 text-sm font-medium">Đang cập nhật báo cáo...</span>
                </div>
            </div>

            <div id="reports-content-wrapper">
                @include('backend.reports.partials.content')
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.reportsConfig = {
            indexUrl: "{{ route('admin.reports.index') }}",
            revenueChartData: {!! json_encode($revenueChartData) !!},
            orderStatusChartData: {!! json_encode($orderStatusChartData) !!}
        };
    </script>
    <script src="{{ asset('js/backend/reports/index.js') }}"></script>
@endpush