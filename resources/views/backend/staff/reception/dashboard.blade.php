@extends('backend.layouts.app')

@section('title', 'Tổng quan - Khu vực Pha chế')

@section('content')
    <div class="dashboard-page p-4 md:p-6 space-y-6">

        <!-- 1. Header trang -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Khu Vực Pha Chế</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi hoạt động, quản lý đơn hàng và giám sát kho nguyên liệu.</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-100 rounded-xl shadow-sm text-xs font-semibold text-gray-600 shrink-0">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">calendar_today</span>
                <span>Hôm nay: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- 2. Thống kê nhanh (Stats Cards) -->
        <div class="grid grid-cols-2 lg:grid-cols-7 gap-4">
            <!-- Đơn chờ xác nhận -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đơn chờ duyệt</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-amber-600">{{ number_format($pendingOrdersCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Cần xác nhận ngay</p>
                </div>
            </div>

            <!-- Đơn chờ phân công vận chuyển -->
            <a href="{{ route('staff.reception.orders.index', ['status' => 'confirmed']) }}"
                class="bg-white p-4 rounded-2xl border {{ $needsAssignmentCount > 0 ? 'border-amber-300' : 'border-gray-100' }} shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Chờ phân công</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black {{ $needsAssignmentCount > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ number_format($needsAssignmentCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Cần chọn shipper</p>
                </div>
            </a>

            <!-- Đơn đang giao -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đơn đang giao</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-blue-600">{{ number_format($shippingOrdersCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Đang trên đường giao</p>
                </div>
            </div>

            <!-- Đơn đã hủy -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đơn đã hủy</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-red-500">{{ number_format($cancelledOrdersCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Không thành công</p>
                </div>
            </div>

            <!-- Vật tư đã hết -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nguyên liệu đã hết</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-red-600">{{ number_format($outOfStockMaterialsCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Cần nhập kho gấp</p>
                </div>
            </div>

            <!-- Vật tư sắp hết -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nguyên liệu sắp hết</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-amber-600">{{ number_format($lowStockMaterialsCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Tồn dưới mức tối thiểu</p>
                </div>
            </div>

            <!-- Vật tư sắp hết hạn -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Lô sắp hết hạn</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-red-500">{{ number_format($expiringMaterialsCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Hạn dưới 30 ngày</p>
                </div>
            </div>
        </div>

        <!-- 2.5. Doanh thu hôm nay theo hình thức thanh toán -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 text-base mb-4">Doanh thu hôm nay</h3>

            @php
                $circumference = 2 * M_PI * 40;
                $gap = 2.5; // Khoảng hở giữa 2 mảng donut (tương đương "surface gap" giữa các mảng)
                $cashNominal = $circumference * $cashRevenuePercent / 100;
                $transferNominal = $circumference * $transferRevenuePercent / 100;
                $cashArcLength = max(0, $cashNominal - $gap);
                $transferArcLength = max(0, $transferNominal - $gap);
                $hasRevenue = $totalRevenueToday > 0;
            @endphp

            <div class="flex flex-col sm:flex-row items-center gap-6">
                {{-- Donut: vàng = tiền mặt, xanh = chuyển khoản (VNPay) --}}
                <div class="relative w-40 h-40 shrink-0">
                    <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90" role="img" aria-label="Tỉ lệ doanh thu hôm nay theo hình thức thanh toán">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="14" />
                        @if($hasRevenue)
                            @if($cashArcLength > 0)
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#f59e0b" stroke-width="14"
                                    stroke-dasharray="{{ $cashArcLength }} {{ $circumference - $cashArcLength }}"
                                    stroke-dashoffset="0" stroke-linecap="round" />
                            @endif
                            @if($transferArcLength > 0)
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#3b82f6" stroke-width="14"
                                    stroke-dasharray="{{ $transferArcLength }} {{ $circumference - $transferArcLength }}"
                                    stroke-dashoffset="{{ -$cashNominal }}" stroke-linecap="round" />
                            @endif
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Tổng cộng</span>
                        <span class="text-base font-black text-gray-900">{{ number_format($totalRevenueToday, 0, ',', '.') }}đ</span>
                    </div>
                </div>

                {{-- Chú giải: luôn hiển thị số tiền + % trực tiếp (không phụ thuộc màu sắc/hover) --}}
                <div class="flex-1 w-full space-y-3">
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                            <span class="text-sm font-semibold text-gray-700">Tiền mặt</span>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900">{{ number_format($cashRevenueToday, 0, ',', '.') }}đ</div>
                            <div class="text-xs text-gray-500">{{ $cashRevenuePercent }}%</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>
                            <span class="text-sm font-semibold text-gray-700">Chuyển khoản (VNPay)</span>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900">{{ number_format($transferRevenueToday, 0, ',', '.') }}đ</div>
                            <div class="text-xs text-gray-500">{{ $transferRevenuePercent }}%</div>
                        </div>
                    </div>
                    @if(!$hasRevenue)
                        <p class="text-xs text-gray-400 text-center pt-1">Chưa có doanh thu nào được ghi nhận hôm nay.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Đơn hàng mới nhận -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Đơn hàng mới nhận</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Danh sách các đơn đặt hàng mới nhất trên hệ thống.</p>
                </div>
                <a href="{{ route('staff.reception.orders.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-colors">Xem tất cả</a>
            </div>

            <!-- Desktop View (Table) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            <th class="py-2.5 px-3">Mã đơn</th>
                            <th class="py-2.5 px-3">Khách hàng</th>
                            <th class="py-2.5 px-3 text-center">Thời gian đặt</th>
                            <th class="py-2.5 px-3 text-right">Tổng tiền</th>
                            <th class="py-2.5 px-3 text-center">Trạng thái</th>
                            <th class="py-2.5 px-3 text-center w-16">Xem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/60 text-sm text-gray-700">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-gray-50/30 transition-colors">
                                <td class="py-3 px-3 font-semibold text-gray-900">{{ $order['code'] }}</td>
                                <td class="py-3 px-3 font-semibold text-gray-900">{{ $order['customer_name'] }}</td>
                                <td class="py-3 px-3 text-center text-gray-500">{!! nl2br(e($order['time'])) !!}</td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ $order['total'] }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-lg {{ $order['status_class'] }}">
                                        {{ $order['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <a href="{{ route('staff.reception.orders.show', $order['id']) }}" 
                                       class="inline-flex items-center justify-center p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors" 
                                       title="Xem chi tiết đơn hàng">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-gray-400">Không có đơn hàng nào cần xử lý.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View (Card list) -->
            <div class="block sm:hidden space-y-3">
                @forelse ($recentOrders as $order)
                    <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex flex-col gap-2 relative">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-bold text-gray-900">{{ $order['code'] }}</span>
                            <span class="text-emerald-600 font-bold">{{ $order['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Khách: <b class="text-gray-800">{{ $order['customer_name'] }}</b></span>
                            <span>{!! nl2br(e($order['time'])) !!}</span>
                        </div>
                        <div class="flex items-center justify-between mt-1 pt-2 border-t border-gray-100">
                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $order['status_class'] }}">
                                {{ $order['status_label'] }}
                            </span>
                            <a href="{{ route('staff.reception.orders.show', $order['id']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600">
                                Chi tiết <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-400 text-sm">Không có đơn hàng nào cần xử lý.</div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
