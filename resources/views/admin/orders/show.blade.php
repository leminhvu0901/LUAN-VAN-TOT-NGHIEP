@extends('admin.layouts.app')

@section('title', 'Chi tiết Đơn hàng ' . $order->order_code)

@section('content')
<div class="flex flex-col gap-6 h-full pb-4">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.index') }}" class="w-8 h-8 flex items-center justify-center rounded-full bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Đơn hàng <span class="text-primary">{{ $order->order_code ?? ('#HPY-' . $order->id) }}</span></h1>
                
                @php
                    $badgeClass = 'bg-gray-100 text-gray-600';
                    $statusText = 'Không xác định';
                    switch ($order->status) {
                        case 'pending': $badgeClass = 'bg-amber-100 text-amber-700'; $statusText = 'Chờ xác nhận'; break;
                        case 'confirmed': $badgeClass = 'bg-blue-100 text-blue-700'; $statusText = 'Đã xác nhận'; break;
                        case 'shipping': $badgeClass = 'bg-indigo-100 text-indigo-700'; $statusText = 'Đang giao'; break;
                        case 'completed': $badgeClass = 'bg-emerald-100 text-emerald-700'; $statusText = 'Hoàn thành'; break;
                        case 'cancelled': $badgeClass = 'bg-red-100 text-red-700'; $statusText = 'Đã hủy'; break;
                    }
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                    {{ $statusText }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1 ml-11">Ngày đặt: {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors print:hidden">
                <span class="material-symbols-outlined text-[20px]">print</span>
                In hóa đơn
            </button>
        </div>
    </div>

    @if($order->status === 'cancelled' && $order->cancel_reason)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
        <span class="material-symbols-outlined text-red-500 mt-0.5">info</span>
        <div>
            <h4 class="font-bold text-red-800">Lý do hủy đơn hàng</h4>
            <p class="text-sm text-red-600 mt-1">{{ $order->cancel_reason }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Items and Totals --}}
        <div class="lg:col-span-2 flex flex-col gap-6">
            {{-- Order Items --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-lg">Chi tiết món</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($items as $item)
                    <div class="p-5 flex gap-4">
                        <div class="w-20 h-20 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden flex-shrink-0">
                            @if($item->product_image)
                                <img src="{{ asset('images/' . $item->product_image) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <span class="material-symbols-outlined text-3xl">local_cafe</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ $item->product_name }}</h4>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-medium">Size {{ $item->size ?? 'M' }}</span>
                                    </div>
                                    @if(isset($item->toppings) && $item->toppings)
                                        <p class="text-xs text-gray-500 mt-1">+ Topping: {{ $item->toppings }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-gray-900">{{ number_format($item->unit_price, 0, ',', '.') }}đ</span>
                                    <p class="text-sm text-gray-500">x{{ $item->quantity }}</p>
                                </div>
                            </div>
                            <div class="text-right mt-2">
                                <span class="font-bold text-primary">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Summary --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <div class="flex flex-col gap-3">
                    <div class="flex justify-between text-gray-600">
                        <span>Tạm tính ({{ $items->sum('quantity') }} món)</span>
                        <span class="font-medium">{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí giao hàng ({{ $order->distance_km ?? 0 }}km)</span>
                        <span class="font-medium">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}đ</span>
                    </div>
                    @if($order->weather_fee > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>Phụ thu thời tiết xấu</span>
                        <span class="font-medium">{{ number_format($order->weather_fee, 0, ',', '.') }}đ</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-600">
                        <span>Khuyến mãi {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</span>
                        <span class="font-medium">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                    </div>
                    @endif
                    <div class="pt-4 mt-2 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-900 text-lg">Tổng cộng</span>
                        <span class="font-bold text-primary text-xl">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Customer Info --}}
        <div class="flex flex-col gap-6">
            {{-- Customer --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-400">person</span>
                    Khách hàng
                </h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg">
                        {{ substr($order->customer_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">{{ $order->customer_name }}</div>
                        <div class="text-sm text-gray-500">{{ $order->customer_phone }}</div>
                    </div>
                </div>
            </div>

            {{-- Delivery --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-400">local_shipping</span>
                    Giao hàng
                </h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Địa chỉ giao</p>
                        <p class="text-sm text-gray-900">{{ $order->delivery_address }}</p>
                    </div>
                    @if($order->customer_note)
                    <div>
                        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">Ghi chú của khách</p>
                        <p class="text-sm text-gray-900 bg-yellow-50 p-3 rounded-lg border border-yellow-100">{{ $order->customer_note }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Payment --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-400">payments</span>
                    Thanh toán
                </h3>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-900 uppercase">{{ $order->payment_method ?? 'COD' }}</div>
                        @if($order->payment_method === 'momo')
                            @if(($order->payment_status ?? '') === 'paid')
                                <div class="text-sm font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Đã thanh toán
                                </div>
                            @else
                                <div class="text-sm font-semibold text-amber-600 flex items-center gap-1 mt-1">
                                    <span class="material-symbols-outlined text-[16px]">pending</span> Chờ thanh toán
                                </div>
                            @endif
                        @else
                            <div class="text-sm text-gray-500 mt-1">Thanh toán khi nhận hàng</div>
                        @endif
                    </div>
                    <span class="material-symbols-outlined text-4xl text-gray-200">
                        {{ ($order->payment_method ?? '') === 'momo' ? 'account_balance_wallet' : 'money' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
