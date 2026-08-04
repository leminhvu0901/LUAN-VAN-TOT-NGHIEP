<div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar relative w-full">
    <!-- View cho màn hình lớn (Desktop Table) -->
    <div class="hidden lg:block">
        <table class="w-full text-left border-collapse relative">
    <thead class="bg-gray-50 sticky top-0 z-10">
        <tr>
            <th
                class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-10 text-center">
                <input type="checkbox"
                    class="js-select-all rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
            </th>
            <th
                class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-24">
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
            <th
                class="px-3 xl:px-4 py-4 text-[11px] xl:text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-20 text-center">
                Hành động</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($orders as $order)
            <tr class="hover:bg-gray-50/50 transition-colors group">
                <td class="px-3 xl:px-4 py-4 whitespace-nowrap text-center">
                    <input type="checkbox" name="order_ids[]" value="{{ $order['id'] }}"
                        class="order-checkbox rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
                </td>
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
                        <div
                            class="inline-flex items-center px-2.5 py-1 rounded text-[11px] xl:text-xs font-medium bg-gray-100 text-gray-600 uppercase border border-gray-200 shadow-sm shrink-0">
                            {{ $order['payment_method'] }}
                        </div>
                        @if(in_array($order['payment_method'], ['MOMO', 'VNPAY'], true))
                            <div class="shrink-0">
                                @if($order['payment_status'] === 'paid')
                                    <span class="badge-status badge-pay-paid scale-90 origin-left"><span
                                            class="material-symbols-outlined text-[14px]">check_circle</span> Đã thanh toán</span>
                                @else
                                    <span class="badge-status badge-pay-pending scale-90 origin-left"><span
                                            class="material-symbols-outlined text-[14px]">pending</span> Chờ TT</span>
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
                    <form action="{{ route('admin.orders.status.update', $order['id']) }}" method="POST" class="m-0">
                        @csrf
                        @php
                            $nextStatuses = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipping', 'cancelled'], 'shipping' => ['completed', 'cancelled'], 'completed' => [], 'cancelled' => []];
                            $allowedStatuses = array_merge([$order['raw_status']], $nextStatuses[$order['raw_status']] ?? []);
                            if ($order['payment_status'] === 'paid')
                                $allowedStatuses = array_values(array_diff($allowedStatuses, ['cancelled']));
                        @endphp
                        <select name="status" data-current-status="{{ $order['raw_status'] }}"
                            class="js-order-status-select order-status-select text-[11px] xl:text-xs border-gray-300 rounded-full shadow-sm focus:border-primary focus:ring-primary badge-status {{ $badgeClass }} font-bold py-1 px-2 pr-6 cursor-pointer">
                            <option value="pending" {{ $order['raw_status'] === 'pending' ? 'selected' : '' }} {{ !in_array('pending', $allowedStatuses) ? 'disabled' : '' }}>Chờ xác nhận</option>
                            <option value="confirmed" {{ $order['raw_status'] === 'confirmed' ? 'selected' : '' }} {{ !in_array('confirmed', $allowedStatuses) ? 'disabled' : '' }}>Đã xác nhận</option>
                            <option value="shipping" {{ $order['raw_status'] === 'shipping' ? 'selected' : '' }} {{ !in_array('shipping', $allowedStatuses) ? 'disabled' : '' }}>Đang giao</option>
                            <option value="completed" {{ $order['raw_status'] === 'completed' ? 'selected' : '' }} {{ !in_array('completed', $allowedStatuses) ? 'disabled' : '' }}>Hoàn thành</option>
                            <option value="cancelled" {{ $order['raw_status'] === 'cancelled' ? 'selected' : '' }} {{ !in_array('cancelled', $allowedStatuses) ? 'disabled' : '' }}>Đã hủy</option>
                        </select>
                    </form>
                    @if($order['needs_admin_approval'] ?? false)
                        <a href="{{ route('admin.orders.show', $order['id']) }}" class="mt-1.5 inline-flex items-center gap-1 text-[10px] xl:text-[11px] font-bold text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-full hover:bg-amber-200 transition-colors">
                            <span class="material-symbols-outlined text-[12px]">verified_user</span> Chờ Admin duyệt
                        </a>
                    @endif
                </td>
                <td class="px-3 xl:px-4 py-4 whitespace-nowrap text-sm">
                    {!! nl2br(e($order['time'])) !!}
                </td>
                <td class="px-3 xl:px-4 py-4 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-1 xl:gap-2">
                        <a href="{{ route('admin.orders.show', $order['id']) }}"
                            class="text-primary hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 p-1.5 rounded-md transition-colors"
                            title="Xem chi tiết">
                            <span class="material-symbols-outlined text-[16px] xl:text-[18px]">visibility</span>
                        </a>
                        <button type="button" data-url="{{ route('admin.orders.destroy', $order['id']) }}" data-id="{{ $order['id'] }}"
                            class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded-md transition-colors delete-order-btn"
                            title="Xóa đơn hàng">
                            <span class="material-symbols-outlined text-[16px] xl:text-[18px]">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
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

<!-- View cho màn hình nhỏ (Mobile/Tablet Cards) -->
<div class="block lg:hidden p-4 space-y-4 w-full">
    @if(count($orders) > 0)
        <div class="flex items-center gap-2 mb-2 px-1 w-full">
            <input type="checkbox"
                class="js-select-all rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer"
                id="mobileSelectAll">
            <label for="mobileSelectAll" class="text-sm font-semibold text-gray-700 cursor-pointer">Chọn tất cả trang
                này</label>
        </div>
    @endif

    @forelse($orders as $order)
        <div class="mobile-card w-full relative group">
            <div class="absolute top-4 left-4 z-10 bg-white/80 rounded">
                <input type="checkbox" name="order_ids[]" value="{{ $order['id'] }}"
                    class="order-checkbox rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
            </div>

            <div class="mobile-card-row pl-8">
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
                    <div
                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200 shrink-0">
                        {{ $order['payment_method'] }}
                    </div>
                    @if(in_array($order['payment_method'], ['MOMO', 'VNPAY'], true))
                        <div class="shrink-0">
                            @if($order['payment_status'] === 'paid')
                                <span class="badge-status badge-pay-paid scale-90 origin-right"><span
                                        class="material-symbols-outlined text-[12px]">check_circle</span> Đã TT</span>
                            @else
                                <span class="badge-status badge-pay-pending scale-90 origin-right"><span
                                        class="material-symbols-outlined text-[12px]">pending</span> Chờ TT</span>
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
                <form action="{{ route('admin.orders.status.update', $order['id']) }}" method="POST" class="m-0 w-full">
                    @csrf
                    @php
                        $nextStatuses = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipping', 'cancelled'], 'shipping' => ['completed', 'cancelled'], 'completed' => [], 'cancelled' => []];
                        $allowedStatuses = array_merge([$order['raw_status']], $nextStatuses[$order['raw_status']] ?? []);
                        if ($order['payment_status'] === 'paid')
                            $allowedStatuses = array_values(array_diff($allowedStatuses, ['cancelled']));
                    @endphp
                    <select name="status" data-current-status="{{ $order['raw_status'] }}"
                        class="js-order-status-select order-status-select text-xs w-full border-gray-200 rounded-lg shadow-sm focus:border-primary focus:ring-primary badge-status {{ $badgeClass }} py-2 px-3 cursor-pointer appearance-none text-center">
                        <option value="pending" {{ $order['raw_status'] === 'pending' ? 'selected' : '' }} {{ !in_array('pending', $allowedStatuses) ? 'disabled' : '' }}>Chờ xác nhận</option>
                        <option value="confirmed" {{ $order['raw_status'] === 'confirmed' ? 'selected' : '' }} {{ !in_array('confirmed', $allowedStatuses) ? 'disabled' : '' }}>Đã xác nhận</option>
                        <option value="shipping" {{ $order['raw_status'] === 'shipping' ? 'selected' : '' }} {{ !in_array('shipping', $allowedStatuses) ? 'disabled' : '' }}>Đang giao</option>
                        <option value="completed" {{ $order['raw_status'] === 'completed' ? 'selected' : '' }} {{ !in_array('completed', $allowedStatuses) ? 'disabled' : '' }}>Hoàn thành</option>
                        <option value="cancelled" {{ $order['raw_status'] === 'cancelled' ? 'selected' : '' }} {{ !in_array('cancelled', $allowedStatuses) ? 'disabled' : '' }}>Đã hủy</option>
                    </select>
                </form>
            </div>

            <div class="mobile-card-actions">
                <a href="{{ route('admin.orders.show', $order['id']) }}"
                    class="flex items-center gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 py-2 rounded-lg font-semibold text-sm transition-colors">
                    <span class="material-symbols-outlined text-[16px]">visibility</span> Xem
                </a>
                <button type="button" data-url="{{ route('admin.orders.destroy', $order['id']) }}" data-id="{{ $order['id'] }}"
                    class="delete-order-btn flex-1 flex items-center justify-center gap-1 bg-red-50 text-red-600 hover:bg-red-100 py-2 rounded-lg font-semibold text-sm transition-colors">
                    <span class="material-symbols-outlined text-[16px]">delete</span> Xóa
                </button>
            </div>
        </div>
    @empty
        <!-- Empty state is handled in table above for desktop, and here for mobile -->
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
<input type="hidden" id="total-orders-count" value="{{ $paginator->total() }}">