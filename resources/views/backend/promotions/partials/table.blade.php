<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="w-8 px-1 py-3 text-center">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                </th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã / Mô tả</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Loại</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Giá trị</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Đơn tối thiểu</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Thời gian</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Lượt dùng</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-1 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($promotions as $promo)
                @php
                    $now = now();
                    $isActive   = $promo->is_active;
                    if ($promo->is_recurring) {
                        $isLive     = $isActive;
                        $isUpcoming = false;
                        $isExpired  = false;
                    } else {
                        $hasStarted = !$promo->start_at || $promo->start_at <= $now;
                        $notExpired = !$promo->end_at || $promo->end_at >= $now;
                        $isLive     = $isActive && $hasStarted && $notExpired;
                        $isUpcoming = $isActive && $promo->start_at && $promo->start_at > $now;
                        $isExpired  = $promo->end_at && $promo->end_at < $now;
                    }
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors group" id="promo-row-{{ $promo->id }}">
                    <td class="px-1 py-2 text-center">
                        <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" value="{{ $promo->id }}">
                    </td>
                    <td class="px-1 py-2">
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <span class="font-bold text-gray-800 font-mono tracking-wide text-sm">{{ $promo->code }}</span>
                            @if($promo->description)
                                <span class="text-xs text-gray-400 truncate max-w-[150px]" title="{{ $promo->description }}">{{ $promo->description }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-1 py-2">
                        @if($promo->type === 'percent')
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-violet-100 text-violet-700 rounded font-medium text-xs whitespace-nowrap">
                                <span class="material-symbols-outlined text-[14px]">percent</span> Giảm %
                            </span>
                        @else
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium text-xs whitespace-nowrap">
                                <span class="material-symbols-outlined text-[14px]">payments</span> Giảm tiền
                            </span>
                        @endif
                    </td>
                    <td class="px-1 py-2 font-semibold text-gray-900 whitespace-nowrap">
                        @if($promo->type === 'percent')
                            <span class="text-violet-600 text-sm">{{ number_format($promo->value, 0) }}%</span>
                            @if($promo->max_discount_amount)
                                <span class="text-xs text-gray-400 block">Tối đa {{ number_format($promo->max_discount_amount, 0, ',', '.') }}đ</span>
                            @endif
                        @else
                            <span class="text-blue-600 text-sm">{{ number_format($promo->value, 0, ',', '.') }}đ</span>
                        @endif
                    </td>
                    <td class="px-1 py-2 text-gray-700 whitespace-nowrap">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-medium">{{ $promo->min_order_amount ? number_format($promo->min_order_amount, 0, ',', '.') . 'đ' : '—' }}</span>
                            @if($promo->apply_for !== 'all')
                                <span class="inline-flex w-fit px-1 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase
                                    @if($promo->apply_for == 'new') bg-gray-100 text-gray-600
                                    @elseif($promo->apply_for == 'silver') bg-gray-200 text-gray-700
                                    @elseif($promo->apply_for == 'gold') bg-amber-100 text-amber-700
                                    @elseif($promo->apply_for == 'diamond') bg-blue-100 text-blue-700
                                    @endif">
                                    {{ $promo->apply_for }}
                                </span>
                            @else
                                <span class="inline-flex w-fit px-1 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-emerald-50 text-emerald-600">ALL</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-1 py-2 text-sm text-gray-600 whitespace-nowrap">
                        <div class="flex flex-col gap-0.5">
                            @if($promo->is_recurring)
                                @php
                                    $days = [1=>'T2', 2=>'T3', 3=>'T4', 4=>'T5', 5=>'T6', 6=>'T7', 7=>'CN'];
                                    $promoDays = is_array($promo->recurring_days) ? $promo->recurring_days : [];
                                    $dayLabels = array_map(function($d) use ($days) { return $days[$d] ?? ''; }, $promoDays);
                                @endphp
                                <span><span class="font-medium text-gray-500 text-xs">Lặp:</span> {{ implode(', ', $dayLabels) ?: 'Hàng ngày' }}</span>
                                <span><span class="font-medium text-gray-500 text-xs">Giờ:</span> {{ $promo->recurring_start_time ? \Carbon\Carbon::parse($promo->recurring_start_time)->format('H:i') : '00:00' }} - {{ $promo->recurring_end_time ? \Carbon\Carbon::parse($promo->recurring_end_time)->format('H:i') : '23:59' }}</span>
                            @else
                                <span><span class="font-medium text-gray-500 text-xs">Từ:</span> {{ $promo->start_at ? \Carbon\Carbon::parse($promo->start_at)->format('d/m/y') : '—' }}</span>
                                <span><span class="font-medium text-gray-500 text-xs">Đến:</span> {{ $promo->end_at ? \Carbon\Carbon::parse($promo->end_at)->format('d/m/y') : 'Không giới hạn' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-1 py-2 text-gray-700 whitespace-nowrap">
                        <div class="flex flex-col gap-0.5">
                            <span class="font-semibold text-sm">{{ $promo->used_count ?? 0 }} lượt</span>
                            @if($promo->usage_limit)
                                <span class="text-xs text-gray-400">/ {{ $promo->usage_limit }} tối đa</span>
                            @else
                                <span class="text-xs text-gray-400">không giới hạn</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-1 py-2 whitespace-nowrap">
                        @if($isLive)
                            <span class="inline-flex items-center gap-1 px-1.5 py-1 bg-emerald-100 text-emerald-700 rounded font-medium text-xs whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
                                Đang diễn ra
                            </span>
                        @elseif($isUpcoming)
                            <span class="inline-flex items-center gap-1 px-1.5 py-1 bg-amber-100 text-amber-700 rounded font-medium text-xs whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                Sắp tới
                            </span>
                        @elseif($isExpired)
                            <span class="inline-flex items-center gap-1 px-1.5 py-1 bg-red-100 text-red-600 rounded font-medium text-xs whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                                Hết hạn
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-1.5 py-1 bg-gray-100 text-gray-600 rounded font-medium text-xs whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                Vô hiệu
                            </span>
                        @endif
                    </td>
                    <td class="px-1 py-2 text-center whitespace-nowrap">
                        <div class="flex justify-center gap-1">
                            <a href="{{ route('admin.promotions.edit', $promo->id) }}"
                                class="p-1 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Sửa">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                            <button type="button"
                                onclick="deletePromotion({{ $promo->id }}, '{{ $promo->code }}')"
                                class="p-1 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Xóa">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-gray-400">
                            <span class="material-symbols-outlined text-5xl">local_offer</span>
                            <span class="font-medium">Không tìm thấy khuyến mãi nào.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($promotions->hasPages())
<div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 pagination-container">
    {{ $promotions->links() }}
</div>
@endif
