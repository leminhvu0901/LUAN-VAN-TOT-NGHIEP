            {{-- Card 1: Tổng đơn hàng --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-blue-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng đơn hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($stats['total_orders']) }}</p>
                    <p class="text-blue-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">receipt_long</span> đơn hàng
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">shopping_bag</span>
                </div>
            </div>

            {{-- Card 2: Tổng doanh thu --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng doanh thu</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($stats['total_revenue'], 0, ',', '.') }}đ</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">check_circle</span> hoàn thành
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">payments</span>
                </div>
            </div>

            {{-- Card 3: Đang chờ xử lý --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đang chờ xử lý</p>
                    <p class="text-2xl sm:text-3xl font-bold {{ $stats['pending_orders'] > 0 ? 'text-amber-600' : 'text-gray-900' }} truncate">{{ number_format($stats['pending_orders']) }}</p>
                    <p class="{{ $stats['pending_orders'] > 0 ? 'text-amber-500' : 'text-gray-400' }} font-medium text-[11px] flex items-center gap-1 truncate">
                        @if($stats['pending_orders'] > 0)
                            <span class="material-symbols-outlined text-[14px]">notification_important</span> cần duyệt gấp
                        @else
                            không có đơn chờ
                        @endif
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">assignment_late</span>
                </div>
            </div>

            {{-- Card 4: Đơn bị hủy --}}
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đơn đã hủy</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($stats['cancelled_orders']) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <span class="material-symbols-outlined text-[14px]">error</span> không thành công
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">cancel</span>
                </div>
            </div>
