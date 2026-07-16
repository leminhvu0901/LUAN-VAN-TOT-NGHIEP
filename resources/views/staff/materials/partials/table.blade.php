<div class="flex-1 overflow-y-auto overflow-x-hidden lg:overflow-x-auto custom-scrollbar relative w-full">
@if($materials->count() > 0)
    <!-- Giao diện Mobile (Card view) -->
    <div class="block md:hidden space-y-4 p-4 w-full">
        @foreach($materials as $material)
            @php
                $statuses = [];
                $barColor = 'bg-emerald-500';
                $barWidth = min(100, max(5, ($material->current_stock / 100) * 100));

                if ($material->current_stock == 0) {
                    $statuses[] = ['text' => 'Cần nhập gấp', 'color' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-500 animate-pulse'];
                    $barColor = 'bg-red-500';
                } else {
                    if ($material->current_stock < 5) {
                        $barColor = 'bg-orange-500';
                    }
                    
                    foreach ($material->imports->where('remaining_quantity', '>', 0) as $lot) {
                        if ($lot->expiration_date) {
                            $days = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date)->startOfDay(), false);
                            if ($days < 0) {
                                $statuses[] = ['text' => "Lô (LOT-{$lot->id}) đã hết hạn", 'color' => 'bg-red-100 text-red-700 font-bold', 'dot' => 'bg-red-500 animate-pulse'];
                            } elseif ($days <= 30) {
                                $statuses[] = ['text' => "Lô (LOT-{$lot->id}) sắp hết hạn ({$days} ngày)", 'color' => 'bg-amber-100 text-amber-700 font-bold', 'dot' => 'bg-amber-500 animate-pulse'];
                            }
                        }
                        
                        if ($lot->remaining_quantity < 5) {
                            $statuses[] = ['text' => "Lô (LOT-{$lot->id}) sắp hết hàng ({$lot->remaining_quantity})", 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                        }
                    }

                    if (empty($statuses)) {
                        if ($material->current_stock < 5) {
                            $statuses[] = ['text' => 'Sắp hết', 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                        } else {
                            $statuses[] = ['text' => 'Còn hàng', 'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'];
                        }
                    }
                    if (isset($material->disposed_count) && $material->disposed_count > 0) {
                        $statuses[] = ['text' => "Đã thu hồi ({$material->disposed_count} lần)", 'color' => 'bg-gray-100 text-gray-700 font-medium', 'dot' => 'bg-gray-500'];
                    }
                }
            @endphp
            
            <div class="bg-white p-4 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3 relative group">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-white border border-gray-200 flex items-center justify-center shrink-0 shadow-sm">
                            <span class="material-symbols-outlined text-emerald-600 text-2xl">
                                {{ str_contains(strtolower($material->name), 'ly') || str_contains(strtolower($material->name), 'nắp') ? 'local_cafe' : (str_contains(strtolower($material->name), 'trà') || str_contains(strtolower($material->name), 'cà phê') ? 'eco' : 'bubble_chart') }}
                            </span>
                        </div>
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-base font-bold text-gray-900 truncate max-w-[180px]" title="{{ $material->name }}">{{ $material->name }}</span>
                            <span class="text-xs text-gray-500 mt-0.5">Mã: VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }} • {{ $material->unit }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100 flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] text-gray-500 font-medium uppercase tracking-wider">Tồn kho</span>
                        <span class="text-sm font-bold {{ $material->current_stock < 5 ? 'text-red-600' : 'text-gray-900' }}">{{ $material->current_stock }} {{ $material->unit }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                        <div class="{{ $barColor }} h-full rounded-full" style="width: {{ $barWidth }}%"></div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5 mt-0.5">
                    @foreach($statuses as $statusItem)
                        <span class="inline-flex items-center gap-1 px-2 py-1 {{ $statusItem['color'] }} rounded-lg font-medium text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusItem['dot'] }}"></span>
                            {{ $statusItem['text'] }}
                        </span>
                    @endforeach
                </div>

                <hr class="border-gray-100 border-dashed my-1">

                <div class="flex justify-end gap-2">
                    <a href="{{ route('staff.materials.imports', $material->id) }}" class="w-full text-center py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors text-xs font-semibold flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">visibility</span> Chi tiết / Nhập kho
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Giao diện Desktop (Table view) -->
<div class="hidden md:block">
    <table class="w-full text-left border-collapse relative">
        <thead class="bg-gray-50 border-b border-gray-100 sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    Mã VT</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Tên vật tư
                </th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    Tồn kho</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">
                    Đơn vị</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Cảnh báo
                </th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Trạng thái
                </th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">
                    Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($materials as $material)
                @php
                    $statuses = [];
                    $barColor = 'bg-emerald-500';
                    $barWidth = min(100, max(5, ($material->current_stock / 100) * 100));

                    if ($material->current_stock == 0) {
                        $statuses[] = ['text' => 'Cần nhập gấp', 'color' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-500 animate-pulse'];
                        $barColor = 'bg-red-500';
                    } else {
                        if ($material->current_stock < 5) {
                            $barColor = 'bg-orange-500';
                        }
                        
                        foreach ($material->imports->where('remaining_quantity', '>', 0) as $lot) {
                            if ($lot->expiration_date) {
                                $days = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date)->startOfDay(), false);
                                if ($days < 0) {
                                    $statuses[] = ['text' => "Lô (LOT-{$lot->id}) đã hết hạn", 'color' => 'bg-red-100 text-red-700 font-bold', 'dot' => 'bg-red-500 animate-pulse'];
                                } elseif ($days <= 30) {
                                    $statuses[] = ['text' => "Lô (LOT-{$lot->id}) sắp hết hạn ({$days} ngày)", 'color' => 'bg-amber-100 text-amber-700 font-bold', 'dot' => 'bg-amber-500 animate-pulse'];
                                }
                            }
                            
                            if ($lot->remaining_quantity < 5) {
                                $statuses[] = ['text' => "Lô (LOT-{$lot->id}) sắp hết hàng ({$lot->remaining_quantity})", 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                            }
                        }

                        if (empty($statuses)) {
                            if ($material->current_stock < 5) {
                                $statuses[] = ['text' => 'Sắp hết', 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                            } else {
                                $statuses[] = ['text' => 'Còn hàng', 'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'];
                            }
                        }
                        if (isset($material->disposed_count) && $material->disposed_count > 0) {
                            $statuses[] = ['text' => "Đã thu hồi ({$material->disposed_count} lần)", 'color' => 'bg-gray-100 text-gray-700 font-medium', 'dot' => 'bg-gray-500'];
                        }
                    }
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="px-4 py-4 text-sm font-semibold text-gray-500 whitespace-nowrap">
                        VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-4 text-sm font-bold text-gray-900 whitespace-nowrap truncate max-w-[200px]" title="{{ $material->name }}">
                        {{ $material->name }}</td>
                    <td class="px-4 py-4 text-sm font-black whitespace-nowrap {{ $material->current_stock < 5 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $material->current_stock }}</td>
                    <td class="px-4 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $material->unit }}</td>
                    <td class="px-4 py-4 text-sm whitespace-nowrap">
                        <div class="flex flex-col gap-1.5">
                            <div class="w-24 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                <div class="{{ $barColor }} h-full rounded-full" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-sm whitespace-nowrap">
                        <div class="flex flex-wrap gap-1">
                            @foreach($statuses as $statusItem)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 {{ $statusItem['color'] }} rounded-lg font-medium text-[10px]">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusItem['dot'] }}"></span>
                                    {{ $statusItem['text'] }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('staff.materials.imports', $material->id) }}" class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors" title="Xem chi tiết phiếu nhập">
                                <span class="material-symbols-outlined text-[16px]">visibility</span> Chi tiết / Nhập kho
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-6xl text-gray-200 mb-4">search_off</span>
                            <p class="text-gray-500 text-lg font-medium">Không tìm thấy vật tư nào</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($materials->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-white shrink-0 ajax-pagination mt-auto">
        {{ $materials->links('pagination::tailwind') }}
    </div>
@endif
<input type="hidden" id="total-materials-count" value="{{ $materials->total() }}">
