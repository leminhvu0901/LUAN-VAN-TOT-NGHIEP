{{-- Card 1: Tổng đơn hàng --}}
<div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-blue-300 transition-all group gap-2">
    <div class="space-y-1 min-w-0">
        <p class="font-semibold text-xs text-gray-500 truncate">Tổng đơn hàng</p>
        <p class="text-lg xl:text-xl 2xl:text-2xl font-bold text-gray-900 tracking-tight whitespace-nowrap">{{ number_format($stats['total_orders']) }}</p>
        <p class="text-blue-600 font-medium text-[11px] flex items-center gap-1 truncate">
            <span class="material-symbols-outlined text-[14px]">receipt_long</span> đơn hàng
        </p>
    </div>
    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform flex-shrink-0">
        <span class="material-symbols-outlined text-lg icon-fill">shopping_bag</span>
    </div>
</div>

{{-- Card 2: Đang chờ xử lý --}}
<div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
    <div class="space-y-1 min-w-0">
        <p class="font-semibold text-xs text-gray-500 truncate">Đang chờ xử lý</p>
        <p class="text-lg xl:text-xl 2xl:text-2xl font-bold {{ $stats['pending_orders'] > 0 ? 'text-amber-600' : 'text-gray-900' }} tracking-tight whitespace-nowrap">{{ number_format($stats['pending_orders']) }}</p>
        <p class="{{ $stats['pending_orders'] > 0 ? 'text-amber-500' : 'text-gray-400' }} font-medium text-[11px] flex items-center gap-1 truncate">
            @if($stats['pending_orders'] > 0)
                <span class="material-symbols-outlined text-[14px]">notification_important</span> cần duyệt gấp
            @else
                không có đơn chờ
            @endif
        </p>
    </div>
    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
        <span class="material-symbols-outlined text-lg icon-fill">assignment_late</span>
    </div>
</div>

{{-- Card 3: Đơn bị hủy --}}
<div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
    <div class="space-y-1 min-w-0">
        <p class="font-semibold text-xs text-gray-500 truncate">Đơn đã hủy</p>
        <p class="text-lg xl:text-xl 2xl:text-2xl font-bold text-gray-900 tracking-tight whitespace-nowrap">{{ number_format($stats['cancelled_orders']) }}</p>
        <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
            <span class="material-symbols-outlined text-[14px]">error</span> không thành công
        </p>
    </div>
    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
        <span class="material-symbols-outlined text-lg icon-fill">cancel</span>
    </div>
</div>
