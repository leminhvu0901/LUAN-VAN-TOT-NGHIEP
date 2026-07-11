<table class="w-full text-left min-w-[900px] border-collapse">
    {{-- Tiêu đề các cột của bảng --}}
    <thead class="bg-gray-50 sticky top-0 z-10">
        <tr>
            <th
                class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-32">
                Mã đơn hàng</th>
            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Khách hàng</th>
            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Tổng tiền</th>
            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Thanh toán</th>
            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Trạng thái</th>
            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                Thời gian</th>
            <th
                class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 w-24 text-center">
                Hành động</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        {{-- Lặp qua từng đơn hàng để hiển thị, nếu danh sách rỗng sẽ chuyển xuống xử lý ở khối @empty --}}
        @forelse($orders as $order)
            <tr class="hover:bg-gray-50/50 transition-colors group">
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-bold text-primary">{{ $order['code'] }}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-900">{{ $order['customer_name'] }}</div>
                    <div class="text-sm text-gray-500 mt-0.5">{{ $order['customer_phone'] }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-bold text-gray-900">{{ $order['total'] }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{-- Hiển thị tên phương thức thanh toán (COD, MOMO...) --}}
                    <div
                        class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 uppercase border border-gray-200 shadow-sm">
                        {{ $order['payment_method'] }}
                    </div>
                    {{-- Hiển thị trạng thái giao dịch nếu là thanh toán online (VD: MOMO) --}}
                    @if($order['payment_method'] === 'MOMO')
                        <div class="mt-1">
                            @if($order['payment_status'] === 'paid')
                                <span class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Đã thanh toán
                                </span>
                            @else
                                <span class="text-[11px] font-semibold text-amber-600 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">pending</span> Chờ thanh toán
                                </span>
                            @endif
                        </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{-- Xác định màu sắc của nhãn (badge) trạng thái dựa vào thuộc tính status_color --}}
                    @php
                        $badgeClass = '';
                        switch ($order['status_color']) {
                            case 'success':
                                $badgeClass = 'bg-emerald-100 text-emerald-700 font-bold border border-emerald-200';
                                break;
                            case 'info':
                                $badgeClass = 'bg-blue-100 text-blue-700 font-bold border border-blue-200';
                                break;
                            case 'warning':
                                $badgeClass = 'bg-amber-100 text-amber-700 font-bold border border-amber-200';
                                break;
                            case 'danger':
                                $badgeClass = 'bg-red-100 text-red-700 font-bold border border-red-200';
                                break;
                            case 'primary':
                                $badgeClass = 'bg-primary-light text-primary font-semibold';
                                break;
                        }
                    @endphp
                    <form action="{{ route('admin.orders.status.update', $order['id']) }}" method="POST" class="m-0">
                        @csrf
                        <select name="status" onchange="this.form.submit()" class="text-xs border-gray-300 rounded-full shadow-sm focus:border-primary focus:ring-primary {{ $badgeClass }} font-bold py-1 px-2 pr-6 cursor-pointer">
                            <option value="pending" {{ $order['status'] == 'Chờ xác nhận' ? 'selected' : '' }}>Chờ xác nhận</option>
                            <option value="confirmed" {{ $order['status'] == 'Đã xác nhận' ? 'selected' : '' }}>Đã xác nhận</option>
                            <option value="shipping" {{ $order['status'] == 'Đang giao' ? 'selected' : '' }}>Đang giao</option>
                            <option value="completed" {{ $order['status'] == 'Hoàn thành' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="cancelled" {{ $order['status'] == 'Đã hủy' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </form>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {!! nl2br(e($order['time'])) !!}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <div
                        class="flex items-center justify-center gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="{{ route('admin.orders.show', $order['id']) }}"
                            class="text-primary hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 p-1.5 rounded-md transition-colors"
                            title="Xem chi tiết">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </a>
                    </div>
                </td>
            </tr>
            {{-- Giao diện hiển thị khi không tìm thấy đơn hàng nào (VD: khi tìm kiếm không có kết quả hoặc CSDL trống) --}}
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

{{-- Hiển thị các nút phân trang nếu tổng số đơn hàng vượt quá số lượng hiển thị trên 1 trang --}}
@if($paginator->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-white shrink-0 ajax-pagination">
        {{ $paginator->links('pagination::tailwind') }}
    </div>
@endif