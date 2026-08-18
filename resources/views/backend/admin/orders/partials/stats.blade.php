            {{-- Card 1: Tổng đơn hàng --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-blue-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng đơn hàng</p>
                    <p class="text-lg xl:text-xl 2xl:text-2xl font-bold text-gray-900 tracking-tight whitespace-nowrap">{{ number_format($stats['total_orders']) }}</p>
                    <p class="text-blue-600 font-medium text-[11px] flex items-center gap-1.5 truncate">
                        <i class="fa-solid fa-receipt text-[11px]"></i> đơn hàng
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-bag-shopping text-base"></i>
                </div>
            </div>

            {{-- Card 2: Tổng doanh thu --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0 overflow-hidden">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng doanh thu</p>
                    <p class="text-lg xl:text-xl 2xl:text-2xl font-bold text-gray-900 tracking-tight whitespace-nowrap overflow-x-auto" style="scrollbar-width: none;">{{ number_format($stats['total_revenue'], 0, ',', '.') }}<span class="text-sm ml-0.5">đ</span></p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1.5 truncate">
                        <i class="fa-solid fa-circle-check text-[11px]"></i> hoàn thành
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-money-bill-wave text-base"></i>
                </div>
            </div>

            {{-- Card 3: Đang chờ xử lý --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang chờ xử lý</p>
                    <p class="text-lg xl:text-xl 2xl:text-2xl font-bold {{ $stats['pending_orders'] > 0 ? 'text-amber-600' : 'text-gray-900' }} tracking-tight whitespace-nowrap">{{ number_format($stats['pending_orders']) }}</p>
                    <p class="{{ $stats['pending_orders'] > 0 ? 'text-amber-500' : 'text-gray-400' }} font-medium text-[11px] flex items-center gap-1.5 truncate">
                        @if($stats['pending_orders'] > 0)
                            <i class="fa-solid fa-bell text-[11px]"></i> cần duyệt gấp
                        @else
                            không có đơn chờ
                        @endif
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-clock text-base"></i>
                </div>
            </div>

            {{-- Card 4: Đơn bị hủy --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đơn đã hủy</p>
                    <p class="text-lg xl:text-xl 2xl:text-2xl font-bold text-gray-900 tracking-tight whitespace-nowrap">{{ number_format($stats['cancelled_orders']) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1.5 truncate">
                        <i class="fa-solid fa-circle-exclamation text-[11px]"></i> không thành công
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-circle-xmark text-base"></i>
                </div>
            </div>
