<!-- Thống kê tổng quan -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
    @foreach ($overviewStats as $key => $stat)
        @php
            $colorClasses = [
                'emerald' => ['bg-emerald-50 border-emerald-100 text-emerald-600', 'text-emerald-700'],
                'blue' => ['bg-blue-50 border-blue-100 text-blue-600', 'text-blue-700'],
                'teal' => ['bg-teal-50 border-teal-100 text-teal-600', 'text-teal-700'],
                'red' => ['bg-red-50 border-red-100 text-red-600', 'text-red-700'],
                'indigo' => ['bg-indigo-50 border-indigo-100 text-indigo-600', 'text-indigo-700'],
                'amber' => ['bg-amber-50 border-amber-100 text-amber-600', 'text-amber-700'],
            ];
            $classes = $colorClasses[$stat['color']] ?? $colorClasses['blue'];
        @endphp
        <div class="bg-white p-3 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all min-w-0">
            <div class="flex items-start justify-between gap-1.5">
                <span class="text-[10px] sm:text-xs font-semibold text-gray-400 uppercase tracking-wider leading-tight min-h-[2rem] line-clamp-2 break-words">{{ $stat['label'] }}</span>
                <div class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl border {{ $classes[0] }} shrink-0">
                    <i class="{{ $stat['icon'] }} text-xs sm:text-sm"></i>
                </div>
            </div>
            <div class="mt-3 sm:mt-4 space-y-1.5">
                <p class="text-[18px] sm:text-2xl font-bold text-gray-900 leading-none break-all overflow-wrap-anywhere reports-stat-val">{{ $stat['value'] }}</p>
                <div class="flex flex-wrap items-center gap-1 text-[10px] sm:text-[11px] font-semibold leading-normal">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-lg {{ $stat['trend']['bg'] ?? 'bg-gray-100' }} {{ $stat['trend']['color'] ?? 'text-gray-600' }} shrink-0">
                        @if ($stat['trend']['direction'] == 'up')
                            ↑
                        @elseif ($stat['trend']['direction'] == 'down')
                            ↓
                        @endif
                        {{ $stat['trend']['text'] }}
                    </span>
                    <span class="text-gray-400 block sm:inline">so với kỳ trước</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Biểu đồ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Biểu đồ Doanh thu & Đơn hàng -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Xu hướng doanh thu & đơn hàng</h3>
                <p class="text-xs text-gray-500 mt-0.5">Biểu đồ biểu diễn tăng trưởng doanh thu và tần suất đặt hàng.</p>
            </div>
        </div>
        <div class="relative w-full h-[240px] sm:h-[320px]">
            <canvas id="revenue-chart"></canvas>
        </div>
    </div>

    <!-- Biểu đồ Trạng thái Đơn hàng -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Trạng thái đơn hàng</h3>
                <p class="text-xs text-gray-500 mt-0.5">Tỷ lệ phần trăm và số lượng đơn hàng theo từng trạng thái.</p>
            </div>
        </div>
        <div class="relative w-full h-[220px] sm:h-[240px] flex items-center justify-center">
            <canvas id="status-chart"></canvas>
        </div>
    </div>
</div>

<!-- Biểu đồ theo kênh bán: Tại quầy / Đặt online -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Doanh thu theo kênh bán</h3>
                <p class="text-xs text-gray-500 mt-0.5">So sánh doanh thu đơn tại quầy và đơn đặt online (giao hàng).</p>
            </div>
        </div>
        <div class="relative w-full h-[220px] sm:h-[240px] flex items-center justify-center">
            <canvas id="channel-chart"></canvas>
        </div>
    </div>

    <!-- Biểu đồ Số lượng đơn theo kênh bán -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Đơn hàng theo kênh bán</h3>
                <p class="text-xs text-gray-500 mt-0.5">Tỷ lệ số lượng đơn tại quầy và đơn đặt online (giao hàng).</p>
            </div>
        </div>
        <div class="relative w-full h-[220px] sm:h-[240px] flex items-center justify-center">
            <canvas id="channel-orders-chart"></canvas>
        </div>
    </div>
</div>

<!-- Báo cáo sản phẩm bán chạy & Doanh thu theo danh mục -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Top 5 sản phẩm bán chạy -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Top sản phẩm bán chạy</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sản phẩm có lượng tiêu thụ lớn nhất trong thời gian lọc.</p>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <th class="py-2.5 px-3">Sản phẩm</th>
                        <th class="py-2.5 px-3 text-center">Đã bán</th>
                        <th class="py-2.5 px-3 text-right">Doanh thu</th>
                        <th class="py-2.5 px-3 text-right w-36">Tỷ lệ doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100/60 text-sm text-gray-700">
                    @forelse ($topProducts as $p)
                        <tr class="hover:bg-gray-50/30 transition-colors">
                            <td class="py-3 px-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg overflow-hidden border border-gray-100 bg-gray-50 shrink-0">
                                    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='{{ asset('images/products/placeholder.jpg') }}';">
                                </div>
                                <span class="font-semibold text-gray-900 break-words">{{ $p->name }}</span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-gray-800">{{ number_format($p->total_qty) }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">
                                {{ number_format($p->total_revenue, 0, ',', '.') }}đ
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min(100, max(0, $p->percentage)) }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500 w-8">{{ min(100, max(0, $p->percentage)) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">Không có dữ liệu trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="block sm:hidden space-y-3">
            @forelse ($topProducts as $p)
                <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex gap-3 min-w-0">
                    <div class="w-12 h-12 rounded-lg overflow-hidden border border-gray-100 bg-gray-50 shrink-0">
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='{{ asset('images/products/placeholder.jpg') }}';">
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <h4 class="font-semibold text-gray-900 text-sm break-words line-clamp-2 leading-tight">{{ $p->name }}</h4>
                        <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 mt-1 gap-1">
                            <span>Đã bán: <b class="text-gray-800 font-bold">{{ $p->total_qty }}</b></span>
                            <span class="text-emerald-600 font-bold break-all">{{ number_format($p->total_revenue, 0, ',', '.') }}đ</span>
                        </div>
                        @php
                            $percentage = min(100, max(0, $p->percentage));
                        @endphp
                        <div class="flex items-center gap-2 mt-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-1">
                                <div class="bg-emerald-500 h-1 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 shrink-0">{{ $percentage }}%</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu sản phẩm.</div>
            @endforelse
        </div>
    </div>

    <!-- Báo cáo danh mục -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Cơ cấu danh mục</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Phân chia tỷ trọng doanh thu theo từng nhóm hàng.</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($categoryStats as $c)
                    @php
                        $catPercentage = min(100, max(0, $c->percentage));
                    @endphp
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm gap-1">
                            <span class="font-semibold text-gray-700 break-words leading-tight">{{ $c->name }}</span>
                            <span class="text-xs font-bold text-gray-500 shrink-0">
                                {{ number_format($c->total_revenue, 0, ',', '.') }}đ ({{ $catPercentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $catPercentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu danh mục.</div>
                @endforelse
            </div>
        </div>

        @if ($categoryStats->isNotEmpty())
            <div class="mt-4 pt-3 border-t border-gray-50 text-[11px] text-gray-400 text-center">
                Dữ liệu chỉ bao gồm các đơn hàng đã thanh toán và giao thành công.
            </div>
        @endif
    </div>
</div>

<!-- Báo cáo tồn kho & Top khách hàng chi tiêu -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Top Khách hàng chi tiêu -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Top khách hàng chi tiêu</h3>
                <p class="text-xs text-gray-500 mt-0.5">Khách hàng có tổng chi tiêu cao nhất tại quán trong kỳ lọc.</p>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <th class="py-2.5 px-3">Họ tên khách hàng</th>
                        <th class="py-2.5 px-3 text-center">Số điện thoại</th>
                        <th class="py-2.5 px-3 text-center">Số đơn</th>
                        <th class="py-2.5 px-3 text-right">Tổng chi tiêu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100/60 text-sm text-gray-700">
                    @forelse ($topCustomers as $c)
                        <tr class="hover:bg-gray-50/30 transition-colors">
                            <td class="py-3 px-3 font-semibold text-gray-900 break-words">{{ $c->customer_name }}</td>
                            <td class="py-3 px-3 text-center text-gray-500">{{ $c->customer_phone ?: 'N/A' }}</td>
                            <td class="py-3 px-3 text-center font-semibold text-gray-800">{{ $c->total_orders }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">
                                {{ number_format($c->total_spend, 0, ',', '.') }}đ
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">Chưa có dữ liệu khách hàng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="block sm:hidden space-y-3">
            @forelse ($topCustomers as $c)
                <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex flex-col gap-2 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-sm">
                        <span class="font-bold text-gray-900 break-words leading-tight">{{ $c->customer_name }}</span>
                        <span class="text-emerald-600 font-bold shrink-0 break-all">{{ number_format($c->total_spend, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 gap-1 border-t border-gray-100/50 pt-2">
                        <span>SĐT: <b class="text-gray-700 break-all">{{ $c->customer_phone ?: 'N/A' }}</b></span>
                        <span>Đã đặt: <b class="text-gray-700 font-bold">{{ $c->total_orders }} đơn</b></span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu khách hàng.</div>
            @endforelse
        </div>
    </div>

    <!-- Báo cáo tồn kho -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between reports-inventory-card min-w-0">
        <div>
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-warehouse text-gray-500 text-base"></i>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm md:text-base">Tình trạng tồn kho</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Cảnh báo tồn kho nguyên liệu.</p>
                    </div>
                </div>
            </div>

            <!-- Tổng giá trị tồn kho -->
            <div class="p-3 rounded-2xl mb-4 flex items-center justify-between shadow-sm gap-2 reports-inventory-summary">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-white rounded-xl text-emerald-600 shadow-sm border border-emerald-100/30">
                        <i class="fa-solid fa-money-bill-wave text-sm block"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] sm:text-[10px] font-bold text-emerald-700 uppercase tracking-wider leading-none mb-1">Tổng giá trị tồn</span>
                        <span class="text-base sm:text-lg font-black text-emerald-800 whitespace-nowrap leading-none">{{ number_format($estimatedInventoryValue, 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Hết hàng -->
                @if ($outOfStockMaterials->isNotEmpty())
                    <div class="space-y-1.5 reports-stock-section">
                        <span class="text-xs font-bold text-red-600 flex items-center gap-1.5 uppercase tracking-wider mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> Hết hàng ({{ $outOfStockMaterials->count() }})
                        </span>
                        <div class="space-y-1.5">
                            @foreach ($outOfStockMaterials->take(4) as $m)
                                <div class="flex items-center justify-between gap-3 p-2 bg-red-50/40 hover:bg-red-50 border border-red-100/50 rounded-xl min-h-[38px] reports-stock-item min-w-0">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                        <span class="text-xs font-semibold text-red-950 break-words line-clamp-2 leading-tight flex-1 min-w-0">{{ $m->name }}</span>
                                    </div>
                                    <span class="text-[11px] font-black text-red-700 bg-red-100/70 border border-red-200/50 px-2 py-0.5 rounded-lg shrink-0">
                                        Hết hàng
                                    </span>
                                </div>
                            @endforeach
                            @if ($outOfStockMaterials->count() > 4)
                                <div class="text-[11px] font-semibold text-red-500/80 text-center py-1 mt-1 bg-red-50/20 rounded-lg border border-dashed border-red-200">
                                    +{{ $outOfStockMaterials->count() - 4 }} nguyên liệu hết hàng khác
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Sắp hết hàng -->
                <div class="space-y-1.5 reports-stock-section">
                    <span class="text-xs font-bold text-amber-600 flex items-center gap-1.5 uppercase tracking-wider mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span> Sắp hết hàng ({{ $lowStockMaterials->count() }})
                    </span>
                    @if ($lowStockMaterials->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($lowStockMaterials->take(4) as $m)
                                <div class="flex items-center justify-between gap-3 p-2 bg-amber-50/40 hover:bg-amber-50 border border-amber-100/50 rounded-xl min-h-[38px] reports-stock-item min-w-0">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                        <span class="text-xs font-semibold text-amber-950 break-words line-clamp-2 leading-tight flex-1 min-w-0">{{ $m->name }}</span>
                                    </div>
                                    <span class="text-[11px] font-black text-amber-700 bg-amber-100/70 border border-amber-200/50 px-2 py-0.5 rounded-lg shrink-0">
                                        {{ (float) $m->current_stock }} {{ $m->unit }}
                                    </span>
                                </div>
                            @endforeach
                            @if ($lowStockMaterials->count() > 4)
                                <div class="text-[11px] font-semibold text-amber-600/80 text-center py-1 mt-1 bg-amber-50/20 rounded-lg border border-dashed border-amber-200">
                                    +{{ $lowStockMaterials->count() - 4 }} nguyên liệu sắp hết khác
                                </div>
                            @endif
                        </div>
                    @else
                        @if ($outOfStockMaterials->isEmpty())
                            <div class="text-center py-6 text-gray-400 text-xs">
                                <i class="fa-solid fa-circle-check text-gray-300 text-2xl mb-1 block"></i>
                                Tồn kho nguyên liệu ở trạng thái an toàn.
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">Không có nguyên liệu sắp hết.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-50 text-right">
            <a href="{{ route('admin.materials.index') }}"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-all hover:gap-1.5 min-h-[40px] h-10 px-3 border border-gray-100 sm:border-0 rounded-xl sm:rounded-none bg-gray-50 sm:bg-transparent mt-2">
                Xem chi tiết kho <i class="fa-solid fa-arrow-right text-[11px]"></i>
            </a>
        </div>
    </div>
</div>

<!-- Đơn hàng gần đây -->
<div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm mt-6">
    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
        <div>
            <h3 class="font-bold text-gray-900 text-base">Đơn hàng gần đây</h3>
            <p class="text-xs text-gray-500 mt-0.5">Danh sách các đơn hàng mới nhất trên hệ thống.</p>
        </div>
    </div>

    @if ($recentOrders && count($recentOrders) > 0)
        <!-- Desktop View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <th class="py-2.5 px-3">Mã đơn</th>
                        <th class="py-2.5 px-3">Khách hàng</th>
                        <th class="py-2.5 px-3 text-center">Thời gian đặt</th>
                        <th class="py-2.5 px-3 text-right">Tổng tiền</th>
                        <th class="py-2.5 px-3 text-center">Trạng thái</th>
                        <th class="py-2.5 px-3 text-center w-28">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100/60 text-sm text-gray-700">
                    @foreach ($recentOrders as $order)
                        <tr class="hover:bg-gray-50/30 transition-colors">
                            <td class="py-3 px-3 font-semibold text-gray-900 break-words">{{ $order['code'] }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 break-words">{{ $order['customer_name'] }}</td>
                            <td class="py-3 px-3 text-center text-gray-500">{{ $order['time'] }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ $order['total'] }}</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-lg {{ $order['status_class'] }}">
                                    {{ $order['status_label'] }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('admin.orders.show', $order['id']) }}"
                                    class="inline-flex items-center justify-center p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                                    title="Xem chi tiết đơn hàng">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="block sm:hidden space-y-3">
            @foreach ($recentOrders as $order)
                <div class="bg-gray-50/30 hover:bg-gray-50/60 p-3.5 rounded-2xl border border-gray-100 flex flex-col gap-2 relative min-w-0 reports-mobile-card transition-colors">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-gray-900 break-all select-all">{{ $order['code'] }}</span>
                        <span class="text-xs font-bold text-gray-400 text-[11px]">{{ $order['time'] }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t border-gray-100/50 pt-2">
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-xs text-gray-500 leading-tight">Khách hàng</span>
                            <span class="text-xs font-semibold text-gray-800 break-words leading-tight mt-0.5">{{ $order['customer_name'] }}</span>
                        </div>
                        <div class="text-right flex flex-col shrink-0">
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider leading-none">Tổng tiền</span>
                            <span class="text-sm font-black text-emerald-600 leading-none mt-1">{{ $order['total'] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100/50 pt-2 gap-2 mt-0.5">
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $order['status_class'] }} shrink-0">
                            {{ $order['status_label'] }}
                        </span>
                        <a href="{{ route('admin.orders.show', $order['id']) }}"
                            class="inline-flex items-center justify-center gap-1 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl min-h-[38px] h-9 px-3 transition-colors">
                            Chi tiết <i class="fa-solid fa-arrow-right text-[11px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Trạng thái trống của đơn hàng -->
        <div class="flex flex-col items-center justify-center text-center py-10 px-4 rounded-2xl border border-dashed border-gray-200 mt-2 reports-empty-orders">
            <div class="p-3 bg-white rounded-2xl text-gray-400 border border-gray-100 shadow-sm mb-3">
                <i class="fa-solid fa-box-open text-2xl block"></i>
            </div>
            <h4 class="font-bold text-gray-700 text-sm">Chưa có đơn hàng gần đây</h4>
            <p class="text-xs text-gray-400 mt-1 max-w-[260px]">Các đơn hàng mới phát sinh sẽ tự động xuất hiện tại đây.</p>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-emerald-600 text-white text-xs font-bold rounded-xl mt-4 hover:bg-emerald-700 transition-all border border-emerald-600 shadow-sm min-h-[40px] h-10">
                <i class="fa-solid fa-eye text-xs"></i>
                <span>Xem tất cả đơn hàng</span>
            </a>
        </div>
    @endif
</div>