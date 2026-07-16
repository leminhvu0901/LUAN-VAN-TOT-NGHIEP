@extends('backend.layouts.app')

@section('title', 'Tổng quan - Khu vực Nhân viên')

@section('content')
    <div class="dashboard-page p-4 md:p-6 space-y-6">

        <!-- 1. Header trang -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Khu Vực Nhân Viên</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi hoạt động, quản lý đơn hàng và giám sát kho nguyên liệu.</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-100 rounded-xl shadow-sm text-xs font-semibold text-gray-600 shrink-0">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">calendar_today</span>
                <span>Hôm nay: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- 2. Thống kê nhanh (Stats Cards) -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Đơn chờ xác nhận -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đơn chờ duyệt</span>
                <div class="mt-3">
                    <p class="text-xl sm:text-2xl font-black text-amber-600">{{ number_format($pendingOrdersCount) }}</p>
                    <p class="text-[10px] text-gray-500 font-bold mt-1">Cần xác nhận ngay</p>
                </div>
            </div>

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

        <!-- 3. Đơn hàng mới nhận -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Đơn hàng mới nhận</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Danh sách các đơn đặt hàng mới nhất trên hệ thống.</p>
                </div>
                <a href="{{ route('staff.orders.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-colors">Xem tất cả</a>
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
                                    <a href="{{ route('staff.orders.show', $order['id']) }}" 
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
                            <a href="{{ route('staff.orders.show', $order['id']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600">
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
