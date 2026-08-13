@extends('backend.layouts.app')

@section('title', 'Báo cáo & Thống kê')

@section('content')
    <div class="reports-page p-4 sm:p-6 space-y-4 sm:space-y-6">

        <!-- Tiêu đề trang & Xuất báo cáo -->
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

        <!-- Bộ lọc thời gian -->
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

                    <!-- Date From -->
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

                    <!-- Date To -->
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

        <!-- Khu vực Nội dung báo cáo -->
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

        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('filter-form');
            const presetSelect = document.getElementById('preset-select');
            const dateFromContainer = document.getElementById('date-from-container');
            const dateToContainer = document.getElementById('date-to-container');
            const exportBtn = document.getElementById('export-btn');

            let revenueChart = null;
            let statusChart = null;
            let channelChart = null;
            let channelOrdersChart = null;

            // Khởi tạo lịch chọn khoảng ngày cho bộ lọc báo cáo
            function initFlatpickr() {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#date_from', {
                        locale: 'vn',
                        dateFormat: 'Y-m-d',
                        allowInput: false,
                        disableMobile: true
                    });
                    flatpickr('#date_to', {
                        locale: 'vn',
                        dateFormat: 'Y-m-d',
                        allowInput: false,
                        disableMobile: true
                    });
                }
            }

            initFlatpickr();

            // Vẽ biểu đồ doanh thu theo ngày
            function initRevenueChart(data) {
                const ctx = document.getElementById('revenue-chart');
                if (!ctx) return;

                if (revenueChart) {
                    revenueChart.destroy();
                }

                const isMobile = window.innerWidth < 640;

                revenueChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Doanh thu (VNĐ)',
                                data: data.revenue,
                                type: 'line',
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: isMobile ? 2 : 3,
                                pointBackgroundColor: '#10b981',
                                pointHoverRadius: 6,
                                tension: 0.35,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Số đơn hàng',
                                data: data.orders,
                                type: 'bar',
                                backgroundColor: 'rgba(59, 130, 246, 0.4)',
                                hoverBackgroundColor: 'rgba(59, 130, 246, 0.6)',
                                borderRadius: 4,
                                barPercentage: 0.55,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: isMobile ? 'bottom' : 'top',
                                labels: {
                                    font: { family: 'Inter', size: isMobile ? 10 : 12, weight: '500' },
                                    color: '#475569',
                                    boxWidth: isMobile ? 8 : 12,
                                    padding: isMobile ? 6 : 10
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                                bodyFont: { family: 'Inter', size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.dataset.yAxisID === 'y') {
                                            label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                                        } else {
                                            label += context.raw + ' đơn';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11 },
                                    color: '#64748b'
                                }
                            },
                            y: {
                                position: 'left',
                                type: 'linear',
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11 },
                                    color: '#64748b',
                                    callback: function (value) {
                                        if (value >= 1000000) {
                                            return (value / 1000000) + 'M';
                                        }
                                        if (value >= 1000) {
                                            return (value / 1000) + 'k';
                                        }
                                        return value;
                                    }
                                }
                            },
                            y1: {
                                position: 'right',
                                type: 'linear',
                                grid: { display: false },
                                ticks: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11 },
                                    color: '#64748b',
                                    stepSize: 1,
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            // Vẽ biểu đồ tròn tỷ lệ đơn hàng theo trạng thái
            function initStatusChart(data) {
                const ctx = document.getElementById('status-chart');
                if (!ctx) return;

                if (statusChart) {
                    statusChart.destroy();
                }

                const total = data.counts.reduce((a, b) => a + b, 0);
                const isMobile = window.innerWidth < 640;

                statusChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.counts,
                            backgroundColor: [
                                '#f59e0b',
                                '#3b82f6',
                                '#06b6d4',
                                '#10b981',
                                '#ef4444'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: isMobile ? 'bottom' : 'right',
                                labels: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11, weight: '500' },
                                    color: '#475569',
                                    boxWidth: isMobile ? 8 : 12,
                                    padding: isMobile ? 6 : 8
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                                bodyFont: { family: 'Inter', size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        const val = context.raw;
                                        const pct = total > 0 ? roundTo((val / total) * 100, 1) : 0;
                                        return ` ${context.label}: ${val} đơn (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: isMobile ? '55%' : '65%'
                    }
                });
            }

            // Làm tròn số cho nhãn hiển thị trên biểu đồ
            function roundTo(num, decimals) {
                return +(Math.round(num + "e+" + decimals)  + "e-" + decimals);
            }

            // Vẽ biểu đồ doanh thu chia theo kênh bán
            function initChannelChart(data) {
                const ctx = document.getElementById('channel-chart');
                if (!ctx) return;

                if (channelChart) {
                    channelChart.destroy();
                }

                const total = data.amounts.reduce((a, b) => a + b, 0);
                const isMobile = window.innerWidth < 640;

                channelChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.amounts,
                            backgroundColor: [
                                '#f97316',
                                '#0ea5e9'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: isMobile ? 'bottom' : 'right',
                                labels: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11, weight: '500' },
                                    color: '#475569',
                                    boxWidth: isMobile ? 8 : 12,
                                    padding: isMobile ? 6 : 8
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                                bodyFont: { family: 'Inter', size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        const val = context.raw;
                                        const pct = total > 0 ? roundTo((val / total) * 100, 1) : 0;
                                        const formatted = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
                                        return ` ${context.label}: ${formatted} (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: isMobile ? '55%' : '65%'
                    }
                });
            }

            // Vẽ biểu đồ số lượng đơn theo từng kênh bán
            function initChannelOrdersChart(data) {
                const ctx = document.getElementById('channel-orders-chart');
                if (!ctx) return;

                if (channelOrdersChart) {
                    channelOrdersChart.destroy();
                }

                const total = data.counts.reduce((a, b) => a + b, 0);
                const isMobile = window.innerWidth < 640;

                channelOrdersChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.counts,
                            backgroundColor: [
                                '#f97316',
                                '#0ea5e9'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: isMobile ? 'bottom' : 'right',
                                labels: {
                                    font: { family: 'Inter', size: isMobile ? 9 : 11, weight: '500' },
                                    color: '#475569',
                                    boxWidth: isMobile ? 8 : 12,
                                    padding: isMobile ? 6 : 8
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                                bodyFont: { family: 'Inter', size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        const val = context.raw;
                                        const pct = total > 0 ? roundTo((val / total) * 100, 1) : 0;
                                        return ` ${context.label}: ${val} đơn (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: isMobile ? '55%' : '65%'
                    }
                });
            }

            if (presetSelect) {
                presetSelect.addEventListener('change', function () {
                    if (this.value === 'custom') {
                        dateFromContainer.classList.remove('hidden');
                        dateToContainer.classList.remove('hidden');
                    } else {
                        dateFromContainer.classList.add('hidden');
                        dateToContainer.classList.add('hidden');
                        filterForm.submit();
                    }
                });
            }

            if (exportBtn) {
                // Ghép URL xuất Excel kèm đúng tham số thời gian đang lọc, để file tải về khớp với những gì đang xem
                function buildExportUrl() {
                    const url = new URL(window.reportsConfig.exportUrl, window.location.origin);
                    if (filterForm) {
                        url.search = new URLSearchParams(new FormData(filterForm)).toString();
                    }
                    return url.toString();
                }

                // Kích hoạt tải file Excel, có hiện màn hình chờ trong lúc server dựng file
                function downloadReport() {
                    window.location.href = buildExportUrl();
                }

                exportBtn.addEventListener('click', function () {
                    if (window.AdminAlert) {
                        window.AdminAlert.confirm(
                            'Hệ thống sẽ tạo file Excel (.xlsx) theo đúng khoảng thời gian đang lọc. Bạn có muốn tải xuống?',
                            downloadReport,
                            'Xuất báo cáo ra Excel?'
                        );
                    } else {
                        downloadReport();
                    }
                });
            }

            if (window.reportsConfig) {
                initRevenueChart(window.reportsConfig.revenueChartData);
                initStatusChart(window.reportsConfig.orderStatusChartData);
                initChannelChart(window.reportsConfig.channelRevenueChartData);
                initChannelOrdersChart(window.reportsConfig.channelOrdersChartData);
            }

            window.addEventListener('resize', function () {
                if (window.reportsConfig) {
                    if (revenueChart && statusChart && channelChart && channelOrdersChart) {
                        initRevenueChart(window.reportsConfig.revenueChartData);
                        initStatusChart(window.reportsConfig.orderStatusChartData);
                        initChannelChart(window.reportsConfig.channelRevenueChartData);
                        initChannelOrdersChart(window.reportsConfig.channelOrdersChartData);
                    }
                }
            });
        });
    </script>
@endpush