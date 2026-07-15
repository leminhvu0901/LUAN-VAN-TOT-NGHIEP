<!-- Thống kê tổng quan -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
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
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider truncate">{{ $stat['label'] }}</span>
                <span class="material-symbols-outlined text-[20px] p-1.5 rounded-xl border {{ $classes[0] }} shrink-0">
                    {{ $stat['icon'] }}
                </span>
            </div>
            <div class="mt-4 space-y-1">
                <p class="text-lg sm:text-2xl font-bold text-gray-900 truncate">{{ $stat['value'] }}</p>
                <div class="flex items-center gap-1 text-[11px] font-semibold">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-lg {{ $stat['trend']['bg'] ?? 'bg-gray-100' }} {{ $stat['trend']['color'] ?? 'text-gray-600' }}">
                        @if ($stat['trend']['direction'] == 'up')
                            ↑
                        @elseif ($stat['trend']['direction'] == 'down')
                            ↓
                        @endif
                        {{ $stat['trend']['text'] }}
                    </span>
                    <span class="text-gray-400">so với kỳ trước</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Biểu đồ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Biểu đồ Doanh thu & Đơn hàng -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Xu hướng doanh thu & đơn hàng</h3>
                <p class="text-xs text-gray-500 mt-0.5">Biểu đồ biểu diễn tăng trưởng doanh thu và tần suất đặt hàng.</p>
            </div>
        </div>
        <div class="relative w-full h-[280px] sm:h-[320px]">
            <canvas id="revenue-chart"></canvas>
        </div>
    </div>

    <!-- Biểu đồ Trạng thái Đơn hàng -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
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

<!-- Báo cáo sản phẩm bán chạy & Doanh thu theo danh mục -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Top 5 sản phẩm bán chạy -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Top sản phẩm bán chạy</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sản phẩm có lượng tiêu thụ lớn nhất trong thời gian lọc.</p>
            </div>
        </div>

        <!-- Desktop View (Table) -->
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
                                    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                                </div>
                                <span class="font-semibold text-gray-900">{{ $p->name }}</span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-gray-800">{{ number_format($p->total_qty) }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($p->total_revenue, 0, ',', '.') }}đ</td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $p->percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500 w-8">{{ $p->percentage }}%</span>
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

        <!-- Mobile View (Card List) -->
        <div class="block sm:hidden space-y-3">
            @forelse ($topProducts as $p)
                <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex gap-3">
                    <div class="w-12 h-12 rounded-lg overflow-hidden border border-gray-100 bg-gray-50 shrink-0">
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $p->name }}</h4>
                        <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
                            <span>Đã bán: <b class="text-gray-800 font-bold">{{ $p->total_qty }}</b></span>
                            <span class="text-emerald-600 font-bold">{{ number_format($p->total_revenue, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <div class="flex-1 bg-gray-100 rounded-full h-1">
                                <div class="bg-emerald-500 h-1 rounded-full" style="width: {{ $p->percentage }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500">{{ $p->percentage }}%</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu sản phẩm.</div>
            @endforelse
        </div>
    </div>

    <!-- Báo cáo danh mục -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Cơ cấu danh mục</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Phân chia tỷ trọng doanh thu theo từng nhóm hàng.</p>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($categoryStats as $c)
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-gray-700">{{ $c->name }}</span>
                            <span class="text-xs font-bold text-gray-500">{{ number_format($c->total_revenue, 0, ',', '.') }}đ ({{ $c->percentage }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $c->percentage }}%"></div>
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
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base">Top khách hàng chi tiêu</h3>
                <p class="text-xs text-gray-500 mt-0.5">Khách hàng có tổng chi tiêu cao nhất tại quán trong kỳ lọc.</p>
            </div>
        </div>

        <!-- Desktop View (Table) -->
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
                            <td class="py-3 px-3 font-semibold text-gray-900">{{ $c->customer_name }}</td>
                            <td class="py-3 px-3 text-center text-gray-500">{{ $c->customer_phone ?: 'N/A' }}</td>
                            <td class="py-3 px-3 text-center font-semibold text-gray-800">{{ $c->total_orders }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($c->total_spend, 0, ',', '.') }}đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">Chưa có dữ liệu khách hàng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile View (Card list) -->
        <div class="block sm:hidden space-y-3">
            @forelse ($topCustomers as $c)
                <div class="bg-gray-50/50 p-3 rounded-xl border border-gray-100 flex flex-col gap-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-bold text-gray-900">{{ $c->customer_name }}</span>
                        <span class="text-emerald-600 font-bold">{{ number_format($c->total_spend, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>SĐT: <b class="text-gray-700">{{ $c->customer_phone ?: 'N/A' }}</b></span>
                        <span>Đã đặt: <b class="text-gray-700 font-bold">{{ $c->total_orders }} đơn</b></span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu khách hàng.</div>
            @endforelse
        </div>
    </div>

    <!-- Báo cáo tồn kho -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Tình trạng tồn kho</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Cảnh báo nguyên liệu sắp hết hoặc hết hàng trong kho.</p>
                </div>
            </div>

            <!-- Tổng giá trị tồn kho -->
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 mb-4 flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tổng giá trị tồn ước tính</span>
                <span class="text-sm font-bold text-gray-800">{{ number_format($estimatedInventoryValue, 0, ',', '.') }}đ</span>
            </div>

            <div class="space-y-3">
                <!-- Hết hàng -->
                @if ($outOfStockMaterials->isNotEmpty())
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-red-600 flex items-center gap-1 uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[14px]">warning</span> Hết hàng ({{ $outOfStockMaterials->count() }})
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($outOfStockMaterials as $m)
                                <span class="text-[11px] font-semibold bg-red-50 text-red-700 px-2 py-0.5 rounded-lg border border-red-100">{{ $m->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Sắp hết hàng -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-amber-600 flex items-center gap-1 uppercase tracking-wider">
                        <span class="material-symbols-outlined text-[14px]">info</span> Sắp hết hàng ({{ $lowStockMaterials->count() }})
                    </span>
                    @if ($lowStockMaterials->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($lowStockMaterials as $m)
                                <span class="text-[11px] font-semibold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-lg border border-amber-100" title="Tồn: {{ $m->current_stock }} {{ $m->unit }}">
                                    {{ $m->name }} ({{ $m->current_stock }})
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400">Tồn kho nguyên liệu ở trạng thái an toàn.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-50 text-right">
            <a href="{{ route('admin.materials.index') }}" class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-colors">
                Xem chi tiết kho kho <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>
    </div>
</div>

<!-- Đơn hàng gần đây -->
<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm mt-6">
    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
        <div>
            <h3 class="font-bold text-gray-900 text-base">Đơn hàng gần đây</h3>
            <p class="text-xs text-gray-500 mt-0.5">Danh sách các đơn hàng mới nhất trên hệ thống.</p>
        </div>
    </div>

    <!-- Desktop View (Table) -->
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
                @forelse ($recentOrders as $order)
                    <tr class="hover:bg-gray-50/30 transition-colors">
                        <td class="py-3 px-3 font-semibold text-gray-900">{{ $order['code'] }}</td>
                        <td class="py-3 px-3 font-semibold text-gray-900">{{ $order['customer_name'] }}</td>
                        <td class="py-3 px-3 text-center text-gray-500">{{ $order['time'] }}</td>
                        <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ $order['total'] }}</td>
                        <td class="py-3 px-3 text-center">
                            <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-lg {{ $order['status_class'] }}">
                                {{ $order['status_label'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <a href="{{ route('admin.orders.show', $order['id']) }}" class="inline-flex items-center justify-center p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors" title="Xem chi tiết đơn hàng">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-6 text-gray-400">Không có đơn hàng nào gần đây.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile View (Card list) -->
    <div class="block sm:hidden space-y-3">
        @forelse ($recentOrders as $order)
            <div class="bg-gray-50/50 p-3.5 rounded-xl border border-gray-100 flex flex-col gap-2 relative">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-bold text-gray-900">{{ $order['code'] }}</span>
                    <span class="text-emerald-600 font-bold">{{ $order['total'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>Khách: <b class="text-gray-800">{{ $order['customer_name'] }}</b></span>
                    <span>{{ $order['time'] }}</span>
                </div>
                <div class="flex items-center justify-between mt-1 pt-2 border-t border-gray-100">
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $order['status_class'] }}">
                        {{ $order['status_label'] }}
                    </span>
                    <a href="{{ route('admin.orders.show', $order['id']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600">
                        Chi tiết <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-gray-400 text-sm">Không có đơn hàng.</div>
        @endforelse
    </div>
</div>
