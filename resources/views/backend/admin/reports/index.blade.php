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
                    title="Xuất báo cáo ra file Excel (.xlsx)">
                    <span class="material-symbols-outlined text-[20px] shrink-0">table_view</span>
                    <span class="whitespace-nowrap">Xuất Excel</span>
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
                        
                        <select name="preset" id="preset-select"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-gray-700 font-medium text-sm shadow-sm hover:border-gray-300 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all cursor-pointer h-11 md:h-10">
                            <option value="today" {{ $preset == 'today' ? 'selected' : '' }}>Hôm nay</option>
                            <option value="7_days" {{ $preset == '7_days' ? 'selected' : '' }}>7 ngày gần nhất</option>
                            <option value="30_days" {{ $preset == '30_days' ? 'selected' : '' }}>30 ngày gần nhất</option>
                            <option value="this_month" {{ $preset == 'this_month' ? 'selected' : '' }}>Tháng này</option>
                            <option value="last_month" {{ $preset == 'last_month' ? 'selected' : '' }}>Tháng trước</option>
                            <option value="this_year" {{ $preset == 'this_year' ? 'selected' : '' }}>Năm nay</option>
                            <option value="custom" {{ $preset == 'custom' ? 'selected' : '' }}>Khoảng ngày tùy chọn</option>
                        </select>
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
                @include('backend.admin.reports.partials.content')
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.reportsConfig = {
            indexUrl: "{{ route('admin.reports.index') }}",
            exportUrl: "{{ route('admin.reports.export') }}",
            revenueChartData: {!! json_encode($revenueChartData) !!},
            orderStatusChartData: {!! json_encode($orderStatusChartData) !!},
            channelRevenueChartData: {!! json_encode($channelRevenueChartData) !!},
            channelOrdersChartData: {!! json_encode($channelOrdersChartData) !!}
        };
    </script>
    <script src="{{ asset('js/backend/admin/reports/index.js') }}"></script>
@endpush