@extends('backend.layouts.app')

@section('title', 'Tổng quan - Nhân viên giao hàng')

@section('content')
    <div class="p-4 sm:p-6 space-y-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Tổng quan giao hàng</h2>
            <p class="text-gray-500 text-sm mt-1">Đơn được phân công cho bạn hôm nay.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Chờ nhận</span>
                <p class="text-2xl font-black text-amber-600 mt-2">{{ number_format($pendingPickupCount) }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đang giao</span>
                <p class="text-2xl font-black text-blue-600 mt-2">{{ number_format($shippingCount) }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đã hoàn thành</span>
                <p class="text-2xl font-black text-emerald-600 mt-2">{{ number_format($completedCount) }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Giao thất bại</span>
                <p class="text-2xl font-black text-red-600 mt-2">{{ number_format($failedCount) }}</p>
            </div>
        </div>

        {{-- Đối soát COD: cần thu (đang giao), đã thu nhưng chưa nộp quầy, và đã nộp quầy --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3">
            <h3 class="font-bold text-gray-900">Đối soát COD của bạn</h3>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Cần thu (đơn đang giao)</span>
                <span class="text-lg font-black text-amber-600">{{ number_format($codToCollect, 0, ',', '.') }}đ</span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm text-gray-500">Đã thu, chưa nộp quầy</span>
                <span class="text-lg font-black {{ $codUnsettledTotal > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ number_format($codUnsettledTotal, 0, ',', '.') }}đ</span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm text-gray-500">Đã nộp quầy</span>
                <span class="text-lg font-black text-emerald-600">{{ number_format($codSettledTotal, 0, ',', '.') }}đ</span>
            </div>
            @if($codUnsettledTotal > 0)
                <p class="text-xs text-red-500 pt-1">Bạn còn giữ {{ number_format($codUnsettledTotal, 0, ',', '.') }}đ tiền mặt — vui lòng nộp lại cho lễ tân/quầy.</p>
            @endif
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-base">Đơn cần giao gần đây</h3>
                <a href="{{ route('staff.delivery.orders.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold">Xem tất cả</a>
            </div>
            <div class="space-y-3">
                @forelse ($recentOrders as $order)
                    <a href="{{ route('staff.delivery.orders.show', $order->id) }}" class="block bg-gray-50/50 p-3.5 rounded-xl border border-gray-100">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-bold text-gray-900">{{ $order->order_code }}</span>
                            <span class="text-emerald-600 font-bold">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $order->customer_name }} — {{ $order->delivery_address }}</div>
                    </a>
                @empty
                    <div class="text-center py-6 text-gray-400 text-sm">Không có đơn nào cần giao.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
