@extends('backend.layouts.app')

@section('title', 'Chi tiết đơn giao hàng')

@section('content')
    <div class="p-4 sm:p-6 space-y-4 max-w-2xl mx-auto">
        <div class="flex items-center gap-3">
            <a href="{{ route('staff.delivery.orders.index') }}" class="w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <h2 class="text-xl font-bold text-gray-900">{{ $order->order_code }}</h2>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 space-y-3">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Khách hàng</p>
                <p class="font-bold text-gray-900">{{ $order->customer_name }}</p>
                <p class="text-sm text-gray-600">{{ $order->customer_phone }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Địa chỉ giao</p>
                <p class="text-sm text-gray-700">{{ $order->delivery_address }}</p>
            </div>
            @if($order->customer_note)
                <div>
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Ghi chú</p>
                    <p class="text-sm text-gray-700 italic">{{ $order->customer_note }}</p>
                </div>
            @endif
            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                <span class="text-sm font-semibold text-gray-600">{{ $order->payment_method === 'cod' ? 'Cần thu COD' : 'Đã thanh toán' }}</span>
                <span class="text-lg font-black text-emerald-600">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <a href="tel:{{ $order->customer_phone }}" class="min-h-[44px] flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                    <span class="material-symbols-outlined text-[18px]">call</span> Gọi khách
                </a>
                @php
                    // Ưu tiên tọa độ GPS chính xác (đã lưu lúc đặt hàng) thay vì tìm theo chuỗi địa chỉ text
                    // — tìm theo text dễ ra nhiều kết quả trùng tên đường/khu vực, không rõ điểm nào là đúng.
                    $mapUrl = ($order->delivery_latitude && $order->delivery_longitude)
                        ? "https://www.google.com/maps/search/?api=1&query={$order->delivery_latitude},{$order->delivery_longitude}"
                        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($order->delivery_address);
                @endphp
                <a href="{{ $mapUrl }}" target="_blank" class="min-h-[44px] flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-sm">
                    <span class="material-symbols-outlined text-[18px]">map</span> Mở bản đồ
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">Sản phẩm</p>
            <div class="space-y-2">
                @foreach($items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">{{ $item->product_name }} x{{ $item->quantity }}</span>
                        <span class="text-gray-900 font-semibold">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if($order->status === 'confirmed')
            <form action="{{ route('staff.delivery.orders.ship', $order->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full min-h-[48px] bg-primary text-white font-bold rounded-xl">Nhận đơn &amp; bắt đầu giao</button>
            </form>
        @elseif($order->status === 'shipping')
            <div class="grid grid-cols-2 gap-2">
                <form action="{{ route('staff.delivery.orders.complete', $order->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full min-h-[48px] bg-emerald-600 text-white font-bold rounded-xl">Giao thành công</button>
                </form>
                <button type="button" data-open-fail-modal="{{ $order->id }}" class="w-full min-h-[48px] bg-red-50 text-red-600 font-bold rounded-xl border border-red-200">Giao thất bại</button>
                <form id="fail-form-{{ $order->id }}" action="{{ route('staff.delivery.orders.fail', $order->id) }}" method="POST" class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reason">
                    <input type="hidden" name="failure_type">
                </form>
            </div>
        @else
            <div class="text-center py-3">
                <span class="inline-block text-sm font-bold px-3 py-1.5 rounded-lg {{ $order->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    {{ $order->status === 'completed' ? 'Đã hoàn thành' : 'Đã hủy' }}
                </span>
                @if($order->delivery_failed_reason)
                    <p class="text-xs text-red-600 mt-2">Lý do giao thất bại: {{ $order->delivery_failed_reason }}</p>
                @endif
            </div>
        @endif
    </div>

    @include('backend.staff.delivery.partials.fail-reason-modal')
@endsection
