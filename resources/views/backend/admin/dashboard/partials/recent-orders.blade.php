<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
        <div>
            <h3 class="font-bold text-gray-900 text-base">Đơn hàng mới nhận</h3>
            <p class="text-xs text-gray-500 mt-0.5">Danh sách các đơn đặt hàng mới nhất trên toàn quán.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-colors">Xem tất cả</a>
    </div>

    <!-- Desktop View -->
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
                        <td class="py-3 px-3 text-center text-gray-500">{{ $order['time'] }}</td>
                        <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ $order['total'] }}</td>
                        <td class="py-3 px-3 text-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $order['status_class'] }}">
                                {{ $order['status_label'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <a href="{{ route('admin.orders.show', $order['id']) }}" 
                               class="inline-flex items-center justify-center p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors" 
                               title="Xem chi tiết đơn hàng">
                                <i class="fa-solid fa-eye text-[14px]"></i>
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

    <!-- Mobile View -->
    <div class="block sm:hidden space-y-3">
        @forelse ($recentOrders as $order)
            <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex flex-col gap-2 relative">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-bold text-gray-900">{{ $order['code'] }}</span>
                    <span class="text-emerald-600 font-bold">{{ $order['total'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>Khách: <b class="text-gray-800">{{ $order['customer_name'] }}</b></span>
                    <span>{{ $order['time'] }}</span>
                </div>
                <div class="flex items-center justify-between mt-1 pt-2 border-t border-gray-100">
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $order['status_class'] }}">
                        {{ $order['status_label'] }}
                    </span>
                    <a href="{{ route('admin.orders.show', $order['id']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600">
                        Chi tiết <i class="fa-solid fa-arrow-right text-[11px]"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-gray-400 text-sm">Không có đơn hàng nào cần xử lý.</div>
        @endforelse
    </div>
</div>
