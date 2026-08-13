<div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar relative w-full">
    <!-- View cho màn hình lớn -->
    <div class="hidden lg:block">
        <table class="w-full text-left border-collapse relative">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-24">
                        Mã đơn hàng</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Khách hàng</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Tổng tiền</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Thanh toán</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Trạng thái</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        Thời gian</th>
                    <th class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-20 text-center">
                        Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap">
                            <span class="font-bold text-primary">{{ $order['code'] }}</span>
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap">
                            <div class="font-bold text-gray-900 truncate max-w-[120px] xl:max-w-[150px]" title="{{ $order['customer_name'] }}">{{ $order['customer_name'] }}</div>
                            <div class="text-sm text-gray-500 mt-0.5">{{ $order['customer_phone'] }}</div>
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap">
                            <span class="font-bold text-gray-900">{{ $order['total'] }}</span>
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="inline-flex items-center px-2.5 py-1 rounded text-[11px] xl:text-xs font-medium bg-gray-100 text-gray-600 uppercase border border-gray-200 shadow-sm shrink-0">
                                    {{ $order['payment_method'] }}
                                </div>
                                @if(in_array($order['payment_method'], ['VNPAY'], true))
                                    <div class="shrink-0">
                                        @if($order['payment_status'] === 'paid')
                                            <span class="badge-status badge-pay-paid scale-90 origin-left"><span class="material-symbols-outlined text-[14px]">check_circle</span> Đã thanh toán</span>
                                        @else
                                            <span class="badge-status badge-pay-pending scale-90 origin-left"><span class="material-symbols-outlined text-[14px]">pending</span> Chờ TT</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap">
                            @php
                                $badgeClass = '';
                                switch ($order['status_color']) {
                                    case 'success':
                                        $badgeClass = 'badge-completed';
                                        break;
                                    case 'info':
                                        $badgeClass = 'badge-confirmed';
                                        break;
                                    case 'warning':
                                        $badgeClass = 'badge-pending';
                                        break;
                                    case 'danger':
                                        $badgeClass = 'badge-cancelled';
                                        break;
                                    case 'primary':
                                        $badgeClass = 'badge-shipping';
                                        break;
                                }
                            @endphp
                            {{-- Chỉ hiển thị, không cho chỉnh tại đây --}}
                            <span class="badge-status {{ $badgeClass }} font-bold py-1 px-2.5 text-[11px] xl:text-xs inline-block">
                                {{ $order['status'] }}
                            </span>
                            @if($order['needs_admin_approval'] ?? false)
                                <a href="{{ route('staff.reception.orders.show', $order['id']) }}" class="mt-1.5 inline-flex items-center gap-1 text-[10px] xl:text-[11px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-full hover:bg-amber-200 transition-colors">
                                    <span class="material-symbols-outlined text-[12px]">hourglass_top</span> Chờ Admin duyệt
                                </a>
                            @endif
                            @if($order['needs_delivery_assignment'] ?? false)
                                <a href="{{ route('staff.reception.orders.show', $order['id']) }}" class="mt-1.5 inline-flex items-center gap-1 text-[10px] xl:text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full hover:bg-amber-100 transition-colors">
                                    <span class="material-symbols-outlined text-[12px]">local_shipping</span> Chưa phân công
                                </a>
                            @endif
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap text-sm">
                            {!! nl2br(e($order['time'])) !!}
                        </td>
                        <td class="px-3 xl:px-4 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-1 xl:gap-2">
                                <a href="{{ route('staff.reception.orders.show', $order['id']) }}"
                                    class="text-primary hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 p-1.5 rounded-md transition-colors"
                                    title="Xem chi tiết">
                                    <span class="material-symbols-outlined text-[16px] xl:text-[18px]">visibility</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-6xl text-gray-200 mb-4">search_off</span>
                                <p class="text-gray-500 text-lg font-medium">Không tìm thấy đơn hàng nào</p>
                                <p class="text-gray-400 text-sm mt-1">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- View cho màn hình nhỏ -->
    <div class="block lg:hidden p-4 space-y-4 w-full">
        @forelse($orders as $order)
            <div class="mobile-card w-full relative group">
                <div class="mobile-card-row">
                    <span class="mobile-card-label">Mã đơn</span>
                    <span class="mobile-card-value text-primary font-bold">{{ $order['code'] }}</span>
                </div>

                <div class="mobile-card-row">
                    <span class="mobile-card-label">Khách hàng</span>
                    <div class="text-right">
                        <div class="font-bold text-gray-900 truncate max-w-[150px] ml-auto" title="{{ $order['customer_name'] }}">{{ $order['customer_name'] }}</div>
                        <div class="text-sm text-gray-500 mt-0.5">{{ $order['customer_phone'] }}</div>
                    </div>
                </div>

                <div class="mobile-card-row">
                    <span class="mobile-card-label">Tổng tiền</span>
                    <span class="mobile-card-value text-gray-900 font-bold">{{ $order['total'] }}</span>
                </div>

                <div class="mobile-card-row items-center">
                    <span class="mobile-card-label">Thanh toán</span>
                    <div class="text-right flex items-center justify-end gap-1.5">
                        <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200 shrink-0">
                            {{ $order['payment_method'] }}
                        </div>
                        @if(in_array($order['payment_method'], ['VNPAY'], true))
                            <div class="shrink-0">
                                @if($order['payment_status'] === 'paid')
                                    <span class="badge-status badge-pay-paid scale-90 origin-right"><span class="material-symbols-outlined text-[12px]">check_circle</span> Đã TT</span>
                                @else
                                    <span class="badge-status badge-pay-pending scale-90 origin-right"><span class="material-symbols-outlined text-[12px]">pending</span> Chờ TT</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mobile-card-row">
                    <span class="mobile-card-label">Ngày đặt</span>
                    <span class="text-sm text-gray-700 text-right">{!! nl2br(e($order['time'])) !!}</span>
                </div>

                <div class="mobile-card-row flex-col items-stretch gap-2 mt-2 pt-3 border-t border-gray-100">
                    <span class="mobile-card-label text-left">Trạng thái đơn hàng</span>
                    @php
                        $badgeClass = '';
                        switch ($order['status_color']) {
                            case 'success':
                                $badgeClass = 'badge-completed';
                                        break;
                            case 'info':
                                $badgeClass = 'badge-confirmed';
                                        break;
                            case 'warning':
                                $badgeClass = 'badge-pending';
                                        break;
                            case 'danger':
                                $badgeClass = 'badge-cancelled';
                                        break;
                            case 'primary':
                                $badgeClass = 'badge-shipping';
                                        break;
                        }
                    @endphp
                    {{-- Chỉ hiển thị, không cho chỉnh tại đây --}}
                    <span class="badge-status {{ $badgeClass }} font-bold py-2 px-3 text-xs w-full text-center inline-block">
                        {{ $order['status'] }}
                    </span>
                    @if($order['needs_delivery_assignment'] ?? false)
                        <a href="{{ route('staff.reception.orders.show', $order['id']) }}" class="mt-1 inline-flex items-center justify-center gap-1 w-full text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg hover:bg-amber-100 transition-colors">
                            <span class="material-symbols-outlined text-[13px]">local_shipping</span> Chưa phân công — Bấm để chọn shipper
                        </a>
                    @endif
                </div>

                <div class="mobile-card-actions">
                    <a href="{{ route('staff.reception.orders.show', $order['id']) }}"
                        class="flex items-center justify-center w-full gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 py-2 rounded-lg font-semibold text-sm transition-colors">
                        <span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white rounded-xl border border-gray-100">
                <span class="material-symbols-outlined text-6xl text-gray-200 mb-4">search_off</span>
                <p class="text-gray-500 text-lg font-medium">Không tìm thấy đơn hàng nào</p>
            </div>
        @endforelse
    </div>
</div>

@if($paginator->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-white shrink-0 ajax-pagination mt-auto">
        {{ $paginator->links('pagination::tailwind') }}
    </div>
@endif
