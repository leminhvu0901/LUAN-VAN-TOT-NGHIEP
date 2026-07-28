<!-- Giao diện Mobile (Card view) -->
<div class="block lg:hidden space-y-4 p-4">
    <div class="flex items-center justify-between mb-3 px-1">
        <label class="flex items-center gap-2 text-sm text-gray-700 font-bold cursor-pointer group">
            <input type="checkbox" id="selectAll-mobile" class="js-select-all rounded-md border-gray-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 transition-colors group-hover:border-emerald-500">
            <span class="group-hover:text-emerald-600 transition-colors">Chọn tất cả</span>
        </label>
        <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2.5 py-1 rounded-full">{{ count($promotions) }} khuyến mãi</span>
    </div>

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
        <div class="bg-white p-4 sm:p-5 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3.5 relative group transition-all hover:shadow-md" id="promo-card-{{ $promo->id }}">
            <!-- Header: Mã + Checkbox -->
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-2.5 w-[calc(100%-2rem)]">
                    <span class="font-bold text-gray-900 font-mono tracking-wide text-base">{{ $promo->code }}</span>
                    @if($promo->is_recurring)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-amber-50 text-amber-700 rounded-md font-bold text-[10px] uppercase border border-amber-200">
                            Lặp lại
                        </span>
                    @endif
                    @if($promo->requires_staff_verification)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-rose-50 text-rose-700 rounded-md font-bold text-[10px] uppercase border border-rose-200" title="Mã này không tự động áp — cần nhân viên xác nhận">
                            <span class="material-symbols-outlined text-[11px]">verified_user</span>
                            Cần xác nhận
                        </span>
                    @endif
                </div>
                <input type="checkbox" class="row-checkbox rounded-md border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer w-5 h-5 shrink-0 transition-colors" value="{{ $promo->id }}">
            </div>

            <!-- Description -->
            @if($promo->description)
                <div class="text-[13px] text-gray-500 leading-relaxed italic" style="overflow-wrap: anywhere; word-break: break-word;">
                    "{{ $promo->description }}"
                </div>
            @endif

            <!-- Khung thông số giảm giá -->
            <div class="grid grid-cols-2 gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100 mt-1">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Loại giảm</span>
                    @if($promo->type === 'percent')
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-violet-700">
                            <span class="material-symbols-outlined text-[16px]">percent</span> Phần trăm
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-blue-700">
                            <span class="material-symbols-outlined text-[16px]">payments</span> Cố định
                        </span>
                    @endif
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Giá trị</span>
                    @if($promo->type === 'percent')
                        <span class="text-sm font-extrabold text-violet-700">{{ number_format($promo->value, 0) }}%</span>
                        @if($promo->max_discount_amount)
                            <span class="text-[10px] text-gray-400">Tối đa {{ number_format($promo->max_discount_amount, 0, ',', '.') }}đ</span>
                        @endif
                    @else
                        <span class="text-sm font-extrabold text-blue-700">{{ number_format($promo->value, 0, ',', '.') }}đ</span>
                    @endif
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Đơn tối thiểu</span>
                    <span class="text-xs font-bold text-gray-700">{{ $promo->min_order_amount ? number_format($promo->min_order_amount, 0, ',', '.') . 'đ' : 'Không có' }}</span>
                    @if($promo->min_quantity)
                        <span class="text-[11px] text-gray-500">Tối thiểu {{ $promo->min_quantity }} món</span>
                    @endif
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Đối tượng</span>
                    @if($promo->apply_for !== 'all')
                        <span class="inline-flex w-fit px-1.5 py-0.5 rounded text-[10px] font-extrabold tracking-wider uppercase
                            @if($promo->apply_for == 'new') bg-gray-100 text-gray-600
                            @elseif($promo->apply_for == 'silver') bg-gray-200 text-gray-700
                            @elseif($promo->apply_for == 'gold') bg-amber-100 text-amber-700
                            @elseif($promo->apply_for == 'diamond') bg-blue-100 text-blue-700
                            @endif">
                            {{ $promo->apply_for }}
                        </span>
                    @else
                        <span class="inline-flex w-fit px-1.5 py-0.5 rounded text-[10px] font-extrabold tracking-wider uppercase bg-emerald-50 text-emerald-600 border border-emerald-100">Tất cả</span>
                    @endif
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Kênh</span>
                    <span class="inline-flex w-fit px-1.5 py-0.5 rounded text-[10px] font-extrabold tracking-wider uppercase
                        {{ ($promo->applies_to ?? 'all') === 'pickup' ? 'bg-orange-100 text-orange-700' : (($promo->applies_to ?? 'all') === 'delivery' ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ match($promo->applies_to ?? 'all') { 'pickup' => 'Tại quầy', 'delivery' => 'Giao hàng', default => 'Tất cả' } }}
                    </span>
                </div>
            </div>

            <!-- Usage limit & time -->
            <div class="flex flex-col gap-1.5 text-xs text-gray-600 px-1">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-gray-400">autorenew</span>
                    <span>Đã dùng: <strong class="text-gray-900">{{ $promo->used_count ?? 0 }}</strong> / {{ $promo->usage_limit ? $promo->usage_limit . ' lượt' : 'Không giới hạn' }}</span>
                </div>
                <div class="flex items-start gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-gray-400 mt-0.5">schedule</span>
                    <div class="flex flex-col min-w-0" style="overflow-wrap: anywhere; word-break: break-word;">
                        @if($promo->is_recurring)
                            @php
                                $days = [1=>'Thứ 2', 2=>'Thứ 3', 3=>'Thứ 4', 4=>'Thứ 5', 5=>'Thứ 6', 6=>'Thứ 7', 7=>'Chủ Nhật'];
                                $promoDays = is_array($promo->recurring_days) ? $promo->recurring_days : [];
                                $dayLabels = array_map(function($d) use ($days) { return $days[$d] ?? ''; }, $promoDays);
                            @endphp
                            <span>{{ implode(', ', $dayLabels) ?: 'Hàng ngày' }}</span>
                            <span class="text-gray-400 text-[11px]">{{ $promo->recurring_start_time ? \Carbon\Carbon::parse($promo->recurring_start_time)->format('H:i') : '00:00' }} - {{ $promo->recurring_end_time ? \Carbon\Carbon::parse($promo->recurring_end_time)->format('H:i') : '23:59' }}</span>
                        @else
                            <span>{{ $promo->start_at ? \Carbon\Carbon::parse($promo->start_at)->format('d/m/Y') : '—' }} - {{ $promo->end_at ? \Carbon\Carbon::parse($promo->end_at)->format('d/m/Y') : 'Vô hạn' }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="border-gray-100 border-dashed my-1">

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <div>
                    @if($isLive)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-xl font-bold text-xs border border-emerald-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Đang diễn ra
                        </span>
                    @elseif($isUpcoming)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-xl font-bold text-xs border border-indigo-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Sắp tới
                        </span>
                    @elseif($isExpired)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-red-50 text-red-600 rounded-xl font-bold text-xs border border-red-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Hết hạn
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-50 text-gray-500 rounded-xl font-bold text-xs border border-gray-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            Vô hiệu
                        </span>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.promotions.edit', $promo->id) }}"
                        class="px-3.5 py-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100 hover:border-blue-200 rounded-xl transition-colors text-xs font-bold flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Sửa
                    </a>
                    <button type="button"
                        data-id="{{ $promo->id }}" data-code="{{ $promo->code }}"
                        class="js-delete-promotion px-3.5 py-2 text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 hover:border-red-200 rounded-xl transition-colors text-xs font-bold flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white p-8 rounded-2xl organic-shadow border border-gray-100 text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner mb-3">
                <span class="material-symbols-outlined text-4xl text-gray-300">local_offer</span>
            </div>
            <p class="font-bold text-gray-600 text-sm">Không tìm thấy khuyến mãi nào</p>
            <p class="text-xs text-gray-400 mt-1">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @endforelse
</div>

<!-- Giao diện Desktop (Table view) -->
<div class="hidden lg:block overflow-x-auto">
    <table class="w-full text-left border-collapse text-sm whitespace-nowrap">
        <thead class="bg-gray-50/80 border-b border-gray-100">
            <tr>
                <th class="w-10 px-3 py-3 text-center">
                    <input type="checkbox" id="selectAll" class="js-select-all rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                </th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Mã / Mô tả</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Loại</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Giá trị</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Điều kiện</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Kênh áp dụng</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Thời gian</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Lượt dùng</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-3 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
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
                <tr class="hover:bg-gray-50/50 transition-colors duration-200 group" id="promo-row-{{ $promo->id }}">
                    <td class="px-3 py-3 text-center">
                        <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer" value="{{ $promo->id }}">
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex flex-col gap-0.5 max-w-[170px] whitespace-normal">
                            <div class="flex flex-wrap items-center gap-1">
                                <span class="font-bold text-gray-800 font-mono tracking-wide text-sm">{{ $promo->code }}</span>
                                @if($promo->requires_staff_verification)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-rose-50 text-rose-700 rounded-md font-bold text-[10px] uppercase border border-rose-200" title="Không tự động áp — cần nhân viên xác nhận">
                                        <span class="material-symbols-outlined text-[11px]">verified_user</span>
                                        Cần xác nhận
                                    </span>
                                @endif
                            </div>
                            @if($promo->description)
                                <span class="text-xs text-gray-400 line-clamp-2" title="{{ $promo->description }}">{{ $promo->description }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        @if($promo->type === 'percent')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg font-semibold text-xs border border-violet-100">
                                <span class="material-symbols-outlined text-[14px]">percent</span> Giảm %
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg font-semibold text-xs border border-blue-100">
                                <span class="material-symbols-outlined text-[14px]">payments</span> Giảm tiền
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        @if($promo->type === 'percent')
                            <span class="text-violet-700 font-bold text-sm">{{ number_format($promo->value, 0) }}%</span>
                            @if($promo->max_discount_amount)
                                <span class="text-[11px] text-gray-400 block mt-0.5">Tối đa {{ number_format($promo->max_discount_amount, 0, ',', '.') }}đ</span>
                            @endif
                        @else
                            <span class="text-blue-700 font-bold text-sm">{{ number_format($promo->value, 0, ',', '.') }}đ</span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex flex-col gap-1 max-w-[150px] whitespace-normal">
                            <span class="text-sm text-gray-700 font-medium">Đơn ≥ {{ $promo->min_order_amount ? number_format($promo->min_order_amount, 0, ',', '.') . 'đ' : '0đ' }}</span>
                            @if($promo->min_quantity)
                                <span class="text-xs text-gray-500">Mua tối thiểu {{ $promo->min_quantity }} món</span>
                            @endif
                            @if($promo->apply_for !== 'all')
                                <span class="inline-flex w-fit px-1.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase
                                    @if($promo->apply_for == 'new') bg-gray-100 text-gray-600
                                    @elseif($promo->apply_for == 'silver') bg-gray-200 text-gray-700
                                    @elseif($promo->apply_for == 'gold') bg-amber-100 text-amber-700
                                    @elseif($promo->apply_for == 'diamond') bg-blue-100 text-blue-700
                                    @endif">
                                    {{ $promo->apply_for }}
                                </span>
                            @else
                                <span class="inline-flex w-fit px-1.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-emerald-50 text-emerald-600 border border-emerald-100">Tất cả</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <span class="inline-flex w-fit px-2 py-1 rounded-lg text-xs font-bold
                            {{ ($promo->applies_to ?? 'all') === 'pickup' ? 'bg-orange-100 text-orange-700' : (($promo->applies_to ?? 'all') === 'delivery' ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ match($promo->applies_to ?? 'all') { 'pickup' => 'Tại quầy', 'delivery' => 'Giao hàng', default => 'Mọi kênh' } }}
                        </span>
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex flex-col gap-1 text-xs text-gray-600 max-w-[150px] whitespace-normal">
                            @if($promo->is_recurring)
                                @php
                                    $days = [1=>'T2', 2=>'T3', 3=>'T4', 4=>'T5', 5=>'T6', 6=>'T7', 7=>'CN'];
                                    $promoDays = is_array($promo->recurring_days) ? $promo->recurring_days : [];
                                    $dayLabels = array_map(function($d) use ($days) { return $days[$d] ?? ''; }, $promoDays);
                                @endphp
                                <span class="font-semibold text-amber-600 flex items-center gap-0.5"><span class="material-symbols-outlined text-[14px]">autorenew</span> Lặp: {{ implode(', ', $dayLabels) ?: 'Hàng ngày' }}</span>
                                <span class="text-gray-400">{{ $promo->recurring_start_time ? \Carbon\Carbon::parse($promo->recurring_start_time)->format('H:i') : '00:00' }} - {{ $promo->recurring_end_time ? \Carbon\Carbon::parse($promo->recurring_end_time)->format('H:i') : '23:59' }}</span>
                            @else
                                <span><span class="font-medium text-gray-400">Từ:</span> {{ $promo->start_at ? \Carbon\Carbon::parse($promo->start_at)->format('d/m/Y') : '—' }}</span>
                                <span><span class="font-medium text-gray-400">Đến:</span> {{ $promo->end_at ? \Carbon\Carbon::parse($promo->end_at)->format('d/m/Y') : 'Không giới hạn' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex flex-col gap-0.5">
                            <span class="font-bold text-gray-800 text-sm">{{ $promo->used_count ?? 0 }} lượt</span>
                            @if($promo->usage_limit)
                                <span class="text-xs text-gray-400">/ {{ $promo->usage_limit }} tối đa</span>
                            @else
                                <span class="text-xs text-gray-400">vô hạn</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        @if($isLive)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg font-bold text-xs border border-emerald-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Đang diễn ra
                            </span>
                        @elseif($isUpcoming)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg font-bold text-xs border border-indigo-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                Sắp tới
                            </span>
                        @elseif($isExpired)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-red-50 text-red-600 rounded-lg font-bold text-xs border border-red-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Hết hạn
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-50 text-gray-500 rounded-lg font-bold text-xs border border-gray-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                Vô hiệu
                            </span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-1.5">
                            <a href="{{ route('admin.promotions.edit', $promo->id) }}"
                                class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-100" title="Sửa">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                            <button type="button"
                                data-id="{{ $promo->id }}" data-code="{{ $promo->code }}"
                                class="js-delete-promotion p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-100" title="Xóa">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3 text-gray-400">
                            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner">
                                <span class="material-symbols-outlined text-4xl text-gray-300">local_offer</span>
                            </div>
                            <div class="space-y-1">
                                <p class="font-bold text-gray-600 text-base">Không tìm thấy khuyến mãi nào</p>
                                <p class="text-sm">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($promotions->hasPages())
<div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 pagination-container rounded-b-2xl">
    {{ $promotions->links() }}
</div>
@endif
