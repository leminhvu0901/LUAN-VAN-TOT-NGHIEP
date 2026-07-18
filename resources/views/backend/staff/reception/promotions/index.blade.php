@extends('backend.layouts.app')

@section('title', 'Khuyến mãi đang áp dụng - Nhân viên pha chế')

@section('content')
    <div class="p-4 sm:p-6 space-y-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Khuyến mãi đang áp dụng</h2>
            <p class="text-gray-500 text-sm mt-1">Danh sách mã khuyến mãi hiện đang có hiệu lực để tư vấn cho khách.</p>
        </div>

        <div class="space-y-3">
            @forelse($promotions as $promo)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-primary bg-primary-container/20 px-2 py-0.5 rounded-lg text-sm">{{ $promo->code }}</span>
                            @if($promo->is_recurring)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">Định kỳ</span>
                            @endif
                            @if(($promo->applies_to ?? 'all') === 'pickup')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-50 text-orange-700">Chỉ tại quầy</span>
                            @endif
                            @if($promo->apply_for === 'all')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Tự động áp dụng</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $promo->description }}</p>
                        @if($promo->min_order_amount)
                            <p class="text-xs text-gray-400 mt-0.5">Đơn tối thiểu: {{ number_format($promo->min_order_amount, 0, ',', '.') }}đ</p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-lg font-black text-emerald-600">
                            {{ $promo->type === 'percent' ? $promo->value . '%' : number_format($promo->value, 0, ',', '.') . 'đ' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400">
                    Hiện không có khuyến mãi nào đang áp dụng.
                </div>
            @endforelse
        </div>
    </div>
@endsection
