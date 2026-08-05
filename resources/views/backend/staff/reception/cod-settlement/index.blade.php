@extends('backend.layouts.app')

@section('title', 'Đối soát COD - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 space-y-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Đối soát tiền COD</h2>
            <p class="text-gray-500 text-sm mt-1">Xác nhận nhân viên vận chuyển đã nộp lại tiền mặt COD thu được từ khách cho quầy.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="p-4 bg-gray-50 text-gray-600 rounded-xl border border-gray-200 text-sm font-medium">
                {{ session('info') }}
            </div>
        @endif

        <div class="space-y-4">
            @forelse($groups as $group)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ $group['staff']->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $group['staff']->phone ?: 'Chưa cập nhật SĐT' }}</p>
                        </div>
                        {{-- Màn hẹp: xếp 2 ô số liệu + nút "Nộp tất cả" dọc xuống thay vì nhồi chung 1 hàng
                             ngang - nhãn "Đã nộp hôm nay" dài hơn "Chưa nộp" nên tự xuống dòng còn ô kia
                             thì không, làm 2 ô lệch chiều cao và nút bị đẩy lệch theo. --}}
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                            <div class="flex items-center justify-between sm:justify-end gap-4">
                                <div class="text-left sm:text-right">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Chưa nộp</p>
                                    <p class="font-black text-lg {{ $group['unsettled_total'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                        {{ number_format($group['unsettled_total'], 0, ',', '.') }}đ
                                    </p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Đã nộp hôm nay</p>
                                    <p class="font-black text-lg text-emerald-600">{{ number_format($group['settled_total_today'], 0, ',', '.') }}đ</p>
                                </div>
                            </div>
                            @if($group['unsettled_orders']->isNotEmpty())
                                <form action="{{ route('staff.reception.cod_settlement.settle_all', $group['staff']->id) }}" method="POST"
                                    class="w-full sm:w-auto"
                                    data-confirm-message="Xác nhận đã nhận đủ {{ number_format($group['unsettled_total'], 0, ',', '.') }}đ từ {{ $group['staff']->name }}?"
                                    onsubmit="return confirm(this.dataset.confirmMessage);">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto min-h-[40px] px-4 bg-primary text-white font-bold rounded-lg text-sm whitespace-nowrap">
                                        Nộp tất cả ({{ $group['unsettled_orders']->count() }})
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($group['unsettled_orders']->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">Không có đơn COD nào cần đối soát.</p>
                    @else
                        <p class="text-[11px] text-gray-400 pt-3">Các đơn dưới đây <span class="font-bold text-amber-600">CHƯA nộp</span> — bấm "Xác nhận đã nộp" ngay khi nhận tiền mặt từ nhân viên.</p>
                        <div class="divide-y divide-gray-50">
                            @foreach($group['unsettled_orders'] as $order)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-primary text-sm">{{ $order->order_code }}</span>
                                            <span class="text-xs text-gray-500 truncate">{{ $order->customer_name }}</span>
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full shrink-0">Chưa nộp</span>
                                        </div>
                                        <p class="text-[11px] text-gray-400">Hoàn thành lúc {{ \Carbon\Carbon::parse($order->updated_at)->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto shrink-0">
                                        <span class="font-bold text-gray-900 text-sm">{{ number_format($order->final_amount, 0, ',', '.') }}đ</span>
                                        <form action="{{ route('staff.reception.cod_settlement.settle_one', $order->id) }}" method="POST"
                                            data-confirm-message="Xác nhận đã nhận {{ number_format($order->final_amount, 0, ',', '.') }}đ tiền mặt cho đơn {{ $order->order_code }}?"
                                            onsubmit="return confirm(this.dataset.confirmMessage);">
                                            @csrf
                                            <button type="submit" class="min-h-[36px] px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs whitespace-nowrap flex items-center gap-1 transition-colors">
                                                <span class="material-symbols-outlined text-[16px]">check_circle</span> Xác nhận đã nộp
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400">
                    Chưa có nhân viên vận chuyển nào trong hệ thống.
                </div>
            @endforelse
        </div>
    </div>

@endsection
