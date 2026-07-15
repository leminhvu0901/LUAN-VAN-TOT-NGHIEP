<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
        <div>
            <h3 class="font-bold text-gray-900 text-base">Cảnh báo tồn kho & hạn dùng</h3>
            <p class="text-xs text-gray-500 mt-0.5">Cảnh báo vận hành sản phẩm, nguyên liệu và thời hạn lô hàng.</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($stockAlerts as $item)
            @php
                $isCritical = $item['is_critical'];
                $colorClasses = $isCritical 
                    ? 'bg-red-50/45 border-red-100/50 text-red-650' 
                    : 'bg-amber-50/45 border-amber-100/50 text-amber-650';
                $icon = $isCritical ? 'error' : 'warning';
                $iconColor = $isCritical ? 'text-red-600 animate-pulse' : 'text-amber-500';
                $badgeClasses = $isCritical ? 'bg-red-200/50 text-red-750' : 'bg-amber-200/50 text-amber-700';
            @endphp
            
            <a href="{{ $item['link'] }}" class="p-3 rounded-xl border {{ $colorClasses }} flex items-center justify-between gap-3 hover:-translate-y-0.5 transition-all hover:shadow-sm duration-150 block">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="material-symbols-outlined text-[20px] {{ $iconColor }} shrink-0">
                        {{ $icon }}
                    </span>
                    <div class="min-w-0">
                        <h4 class="text-xs font-bold text-gray-800 truncate" title="{{ $item['name'] }}">{{ $item['name'] }}</h4>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            {{ $item['min'] }}
                        </p>
                    </div>
                </div>
                
                <div class="text-right shrink-0">
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $badgeClasses }}">
                        {{ $item['current'] }}
                    </span>
                </div>
            </a>
        @empty
            <div class="text-center py-8 flex flex-col items-center justify-center gap-2">
                <span class="material-symbols-outlined text-4xl text-emerald-500">check_circle</span>
                <span class="text-xs text-gray-500">Tồn kho và hạn dùng ở trạng thái an toàn.</span>
            </div>
        @endforelse
    </div>
</div>
