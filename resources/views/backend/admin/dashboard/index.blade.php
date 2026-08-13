@extends('backend.layouts.app')

@section('title', 'Tổng quan vận hành - Happy Tea')

@section('content')
    <div class="dashboard-page p-4 md:p-6 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Tổng quan</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi hoạt động và các tác vụ cần xử lý trong hệ thống.</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-100 rounded-xl shadow-sm text-xs font-semibold text-gray-600 shrink-0">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">calendar_today</span>
                <span>Hôm nay: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Khu vực cần xử lý ngay -->
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tác vụ cần xử lý ngay</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>
            @include('backend.admin.dashboard.partials.action-cards')
        </div>

        <!-- Số liệu vận hành hôm nay -->
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Hiệu suất vận hành hôm nay</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>
            
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Đơn hàng -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đơn hàng hôm nay</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">{{ number_format($compareStats['orders']['value']) }}</p>
                        <p class="text-[10px] font-bold mt-1 {{ $compareStats['orders']['diff'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $compareStats['orders']['diff'] >= 0 ? '+' : '' }}{{ $compareStats['orders']['diff'] }} đơn so với hôm qua
                        </p>
                    </div>
                </div>

                <!-- Doanh thu -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Doanh thu hôm nay</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-emerald-600">{{ $compareStats['revenue']['value'] }}</p>
                        <p class="text-[10px] font-bold mt-1 {{ $compareStats['revenue']['diff'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $compareStats['revenue']['diff'] >= 0 ? '+' : '-' }}{{ $compareStats['revenue']['diff_formatted'] }} so với hôm qua
                        </p>
                    </div>
                </div>

                <!-- Khách hàng mới -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Khách hàng mới hôm nay</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">{{ number_format($compareStats['customers']['value']) }}</p>
                        <p class="text-[10px] font-bold mt-1 {{ $compareStats['customers']['diff'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $compareStats['customers']['diff'] >= 0 ? '+' : '' }}{{ $compareStats['customers']['diff'] }} khách so với hôm qua
                        </p>
                    </div>
                </div>

                <!-- Sản phẩm đã bán -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Ly nước đã bán hôm nay</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">{{ number_format($compareStats['products_sold']['value']) }}</p>
                        <p class="text-[10px] font-bold mt-1 {{ $compareStats['products_sold']['diff'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $compareStats['products_sold']['diff'] >= 0 ? '+' : '' }}{{ $compareStats['products_sold']['diff'] }} món so với hôm qua
                        </p>
                    </div>
                </div>

                <!-- Tỷ lệ hoàn thành -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tỷ lệ hoàn thành đơn</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">{{ $compareStats['completion_rate'] }}%</p>
                        <div class="w-full bg-gray-100 h-1 rounded-full mt-2 overflow-hidden">
                            <div class="bg-emerald-500 h-1 rounded-full" style="width: {{ $compareStats['completion_rate'] }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Tỷ lệ thanh toán -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tỷ lệ thanh toán thành công</span>
                    <div class="mt-3">
                        <p class="text-xl sm:text-2xl font-black text-gray-900">{{ $compareStats['payment_rate'] }}%</p>
                        <div class="w-full bg-gray-100 h-1 rounded-full mt-2 overflow-hidden">
                            <div class="bg-blue-500 h-1 rounded-full" style="width: {{ $compareStats['payment_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ vận hành 7 ngày gần nhất -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Hiệu suất vận hành 7 ngày gần nhất</h3>
                <p class="text-xs text-gray-500 mt-0.5">Biểu đồ thể hiện biến động số đơn đặt và doanh thu hoàn thành mỗi ngày.</p>
            </div>
            <div class="relative w-full h-[240px] sm:h-[280px] mt-4">
                <canvas id="operational-chart"></canvas>
            </div>
        </div>

        <!-- Khối thông tin chi tiết -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cột trái: Đơn hàng & Đánh giá -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Đơn hàng mới nhất -->
                @include('backend.admin.dashboard.partials.recent-orders')

                <!-- Đánh giá mới nhất -->
                @include('backend.admin.dashboard.partials.recent-reviews')
            </div>

            <!-- Cột phải: Cảnh báo kho & Hoạt động gần đây -->
            <div class="space-y-6">
                <!-- Cảnh báo kho -->
                @include('backend.admin.dashboard.partials.stock-alerts')

                <!-- Hoạt động gần đây -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Hoạt động gần đây</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Lịch sử hoạt động và ghi nhận thay đổi của hệ thống.</p>
                        </div>
                    </div>

                    <div class="relative pl-6 border-l-2 border-gray-100 space-y-5 py-2">
                        @forelse ($recentActivities as $act)
                            @php
                                $colorMap = [
                                    'emerald' => 'bg-emerald-500 ring-emerald-100 text-white',
                                    'blue' => 'bg-blue-500 ring-blue-100 text-white',
                                    'amber' => 'bg-amber-500 ring-amber-100 text-white',
                                    'indigo' => 'bg-indigo-500 ring-indigo-100 text-white',
                                ];
                                $colorClass = $colorMap[$act['color']] ?? 'bg-gray-400 ring-gray-100';
                            @endphp
                            <div class="relative">
                                <!-- Icon dot indicator -->
                                <span class="absolute -left-[31px] top-0.5 w-4.5 h-4.5 rounded-full ring-4 {{ $colorClass }} flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[10px] shrink-0 font-bold">
                                        {{ $act['icon'] }}
                                    </span>
                                </span>
                                
                                <div class="text-xs">
                                    <span class="text-gray-400 font-medium block mb-0.5">{{ $act['time']->diffForHumans() }}</span>
                                    <a href="{{ $act['link'] }}" class="text-gray-700 font-semibold hover:text-emerald-700 transition-colors hover:underline block leading-relaxed pr-2">
                                        {{ $act['text'] }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-400 text-xs pl-0">Không có hoạt động nào được ghi nhận.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.dashboardConfig = {
            chartData: {!! json_encode($chartData) !!}
        };

        document.addEventListener('DOMContentLoaded', function () {
            let operationalChart = null;

            // Vẽ biểu đồ vận hành tổng quan trên trang dashboard
            function initOperationalChart(data) {
                const ctx = document.getElementById('operational-chart');
                if (!ctx) return;

                if (operationalChart) {
                    operationalChart.destroy();
                }

                operationalChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Doanh thu hoàn thành (VNĐ)',
                                data: data.revenue,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                borderWidth: 3,
                                pointBackgroundColor: '#10b981',
                                pointHoverRadius: 6,
                                tension: 0.3,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Số đơn đặt hàng',
                                data: data.orders,
                                type: 'bar',
                                backgroundColor: 'rgba(59, 130, 246, 0.35)',
                                hoverBackgroundColor: 'rgba(59, 130, 246, 0.55)',
                                borderRadius: 5,
                                barPercentage: 0.5,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: { family: 'Inter', size: 12, weight: '500' },
                                    color: '#475569'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                                bodyFont: { family: 'Inter', size: 12 },
                                padding: 12,
                                cornerRadius: 10,
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
                                    font: { family: 'Inter', size: 11 },
                                    color: '#64748b'
                                }
                            },
                            y: {
                                position: 'left',
                                type: 'linear',
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    font: { family: 'Inter', size: 11 },
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
                                    font: { family: 'Inter', size: 11 },
                                    color: '#64748b',
                                    stepSize: 1,
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            if (window.dashboardConfig && window.dashboardConfig.chartData) {
                initOperationalChart(window.dashboardConfig.chartData);
            }
        });
    </script>
@endpush

