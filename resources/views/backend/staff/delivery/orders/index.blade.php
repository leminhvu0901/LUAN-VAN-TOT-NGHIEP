@extends('backend.layouts.app')

@section('title', 'Đơn giao hàng - Nhân viên giao hàng')

@section('content')
    <div class="p-4 sm:p-6 space-y-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Đơn giao hàng</h2>
            <p class="text-gray-500 text-sm mt-1">Chỉ hiển thị đơn được phân công cho bạn.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tab: luôn dùng thẻ card, không dùng bảng cuộn ngang --}}
        <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('staff.delivery.orders.index', ['tab' => 'assigned']) }}"
                class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap {{ $tab === 'assigned' ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600' }}">Đơn được giao</a>
            <a href="{{ route('staff.delivery.orders.index', ['tab' => 'shipping']) }}"
                class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap {{ $tab === 'shipping' ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600' }}">Đang giao</a>
            <a href="{{ route('staff.delivery.orders.index', ['tab' => 'history']) }}"
                class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap {{ $tab === 'history' ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600' }}">Lịch sử</a>
        </div>

        @if($tab === 'shipping')
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-600">Tổng COD cần thu</span>
                <span class="text-lg font-black text-emerald-600">{{ number_format($codToCollect, 0, ',', '.') }}đ</span>
            </div>
        @endif

        {{-- Danh sách đơn: luôn dạng card, không dùng table --}}
        <div class="space-y-3">
            @forelse ($orders as $order)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <a href="{{ route('staff.delivery.orders.show', $order->id) }}" class="font-bold text-gray-900 hover:text-primary">{{ $order->order_code }}</a>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_name }} — {{ $order->customer_phone }}</p>
                        </div>
                        <span class="text-emerald-600 font-bold text-sm shrink-0">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                    </div>

                    <p class="text-sm text-gray-600 leading-snug">{{ $order->delivery_address }}</p>

                    {{-- Danh sách món — để shipper biết cần lấy gì mà không phải bấm vào xem chi tiết. --}}
                    @if($order->items->isNotEmpty())
                        <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-2 space-y-0.5">
                            @foreach($order->items as $item)
                                <p>{{ $item->product_name ?? optional($item->product)->name ?? 'Sản phẩm' }} x{{ $item->quantity }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if($order->customer_note)
                        <p class="text-xs text-gray-500 italic bg-gray-50 rounded-lg p-2">Ghi chú: {{ $order->customer_note }}</p>
                    @endif

                    {{-- Trạng thái thanh toán: COD cần thu tiền mặt vs đã thanh toán online (MoMo/VNPay). --}}
                    @if($order->payment_method === 'cod')
                        <p class="text-xs font-semibold text-amber-700 bg-amber-50 inline-block px-2 py-1 rounded-lg">COD: {{ number_format($order->final_amount, 0, ',', '.') }}đ</p>
                    @else
                        <p class="text-xs font-semibold text-emerald-700 bg-emerald-50 inline-block px-2 py-1 rounded-lg">
                            <span class="material-symbols-outlined text-[14px] align-middle">check_circle</span>
                            Đã thanh toán {{ match($order->payment_method) { 'momo' => 'MoMo', 'vnpay' => 'VNPay', default => 'trực tuyến' } }} — không thu tiền
                        </p>
                    @endif

                    {{-- Phí ship / phụ phí thời tiết-giờ cao điểm — shipper cần biết để đối chiếu với tổng tiền COD. --}}
                    @php
                        $feeParts = [];
                        if ($order->shipping_fee > 0) $feeParts[] = 'Phí ship ' . number_format($order->shipping_fee, 0, ',', '.') . 'đ';
                        if ($order->weather_fee > 0) $feeParts[] = 'Phụ phí thời tiết ' . number_format($order->weather_fee, 0, ',', '.') . 'đ';
                        if ($order->peak_hour_fee > 0) $feeParts[] = 'Phụ phí giờ cao điểm ' . number_format($order->peak_hour_fee, 0, ',', '.') . 'đ';
                    @endphp
                    @if(!empty($feeParts))
                        <p class="text-xs text-gray-500">{{ implode(' • ', $feeParts) }}</p>
                    @endif

                    {{-- Vùng nút: mỗi nút tối thiểu 44px chiều cao, dễ bấm trên di động --}}
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="tel:{{ $order->customer_phone }}"
                            class="min-h-[44px] flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                            <span class="material-symbols-outlined text-[18px]">call</span> Gọi khách
                        </a>
                        @php
                            $mapUrl = ($order->delivery_latitude && $order->delivery_longitude)
                                ? "https://www.google.com/maps/search/?api=1&query={$order->delivery_latitude},{$order->delivery_longitude}"
                                : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($order->delivery_address);
                        @endphp
                        <a href="{{ $mapUrl }}" target="_blank"
                            class="min-h-[44px] flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                            <span class="material-symbols-outlined text-[18px]">map</span> Mở bản đồ
                        </a>
                    </div>

                    @if($tab === 'assigned')
                        <form action="{{ route('staff.delivery.orders.ship', $order->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full min-h-[44px] bg-primary text-white font-bold rounded-xl">Nhận đơn &amp; bắt đầu giao</button>
                        </form>
                    @elseif($tab === 'shipping')
                        <div class="grid grid-cols-2 gap-2">
                            <form action="{{ route('staff.delivery.orders.complete', $order->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full min-h-[44px] bg-emerald-600 text-white font-bold rounded-xl">Giao thành công</button>
                            </form>
                            <button type="button" data-open-fail-modal="{{ $order->id }}" class="w-full min-h-[44px] bg-red-50 text-red-600 font-bold rounded-xl border border-red-200">Giao thất bại</button>
                        </div>
                        <form id="fail-form-{{ $order->id }}" action="{{ route('staff.delivery.orders.fail', $order->id) }}" method="POST" class="hidden">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="reason">
                            <input type="hidden" name="failure_type">
                        </form>
                    @else
                        <span class="inline-block text-xs font-bold px-2 py-1 rounded-lg {{ $order->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $order->status === 'completed' ? 'Đã hoàn thành' : 'Đã hủy' }}
                        </span>
                        @if($order->delivery_failed_reason)
                            <p class="text-xs text-red-600 mt-1">Lý do thất bại: {{ $order->delivery_failed_reason }}</p>
                        @endif
                    @endif
                </div>
            @empty
                <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400">
                    <span class="material-symbols-outlined text-4xl text-gray-300">local_shipping</span>
                    <p class="text-sm font-semibold mt-2">Không có đơn nào ở mục này.</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($orders, 'hasPages') && $orders->hasPages())
            <div class="pt-2">{{ $orders->links('pagination::tailwind') }}</div>
        @endif
    </div>

    @include('backend.staff.delivery.partials.fail-reason-modal')
@endsection
