<div class="flex-1 overflow-y-auto overflow-x-hidden lg:overflow-x-auto custom-scrollbar relative w-full">
@if($materials->count() > 0)
    <!-- Giao diện Mobile -->
    <div class="block md:hidden space-y-4 p-4 w-full">
        <div class="flex items-center justify-between mb-2">
            <label class="flex items-center gap-2 text-sm text-gray-600 font-medium cursor-pointer">
                <input type="checkbox" id="selectAll-mobile" class="js-select-all rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 transition-colors">
                <span>Chọn tất cả</span>
            </label>
        </div>

        @foreach($materials as $material)
            @php
                $isDeleteBlocked = ($material->active_lots_count ?? 0) > 0;
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
                    
                    // Xét từng lô hàng còn tồn kho
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

                    // Nếu không có lô nào gặp vấn đề
                    if (empty($statuses)) {
                        if ($material->current_stock < 5) {
                            $statuses[] = ['text' => 'Sắp hết', 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                        } else {
                            $statuses[] = ['text' => 'Còn hàng', 'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'];
                        }
                    }
                }
            @endphp
            
            <div class="bg-white p-4 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3 relative group">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-white border border-gray-200 flex items-center justify-center shrink-0 shadow-sm">
                            @php
                                $matIcon = 'fa-cubes';
                                $lowerName = strtolower($material->name);
                                if (str_contains($lowerName, 'ly') || str_contains($lowerName, 'nắp')) {
                                    $matIcon = 'fa-mug-hot';
                                } elseif (str_contains($lowerName, 'trà') || str_contains($lowerName, 'cà phê')) {
                                    $matIcon = 'fa-leaf';
                                }
                            @endphp
                            <i class="fa-solid {{ $matIcon }} text-emerald-600 text-xl"></i>
                        </div>
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-base font-bold text-gray-900 truncate max-w-[150px]" title="{{ $material->name }}">{{ $material->name }}</span>
                            <span class="text-xs text-gray-500 mt-0.5">Mã: VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }} • {{ $material->unit }}</span>
                        </div>
                    </div>
                    <input type="checkbox" name="material_ids[]" value="{{ $material->id }}" class="material-checkbox row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 shrink-0 transition-colors {{ $isDeleteBlocked ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}" {{ $isDeleteBlocked ? 'disabled' : '' }}>
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
                    <a href="{{ route('admin.materials.imports', $material->id) }}" class="flex-1 text-center py-2 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors text-xs font-semibold flex items-center justify-center gap-1">
                        <i class="fa-solid fa-eye text-[14px]"></i> Chi tiết
                    </a>
                    @if($isDeleteBlocked)
                        <button type="button" disabled class="px-3 py-2 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed text-xs font-semibold flex items-center justify-center gap-1">
                            <i class="fa-solid fa-trash-can text-[14px]"></i> Xóa
                        </button>
                    @else
                        <form action="{{ route('admin.materials.destroy', $material->id) }}" method="POST" class="js-material-delete-form inline-block m-0 p-0"
                            onsubmit="return confirm('Xóa vật tư này? Hành động này không thể hoàn tác.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors text-xs font-semibold flex items-center justify-center gap-1">
                                <i class="fa-solid fa-trash-can text-[14px]"></i> Xóa
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Giao diện Desktop -->
<div class="hidden md:block">
    <table class="w-full text-left border-collapse relative">
        <thead class="bg-gray-50 border-b border-gray-100 sticky top-0 z-10">
            <tr>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap w-10 text-center">
                    <input type="checkbox" id="selectAll" class="js-select-all rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 cursor-pointer">
                </th>
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
                    $isDeleteBlocked = ($material->active_lots_count ?? 0) > 0;
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
                        
                        // Xét từng lô hàng còn tồn kho
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

                        // Nếu không có lô nào gặp vấn đề
                        if (empty($statuses)) {
                            if ($material->current_stock < 5) {
                                $statuses[] = ['text' => 'Sắp hết', 'color' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500'];
                            } else {
                                $statuses[] = ['text' => 'Còn hàng', 'color' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'];
                            }
                        }
                    }
                @endphp
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <input type="checkbox" name="material_ids[]" value="{{ $material->id }}"
                            class="material-checkbox row-checkbox rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 {{ $isDeleteBlocked ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}"
                            title="{{ $isDeleteBlocked ? 'Không thể chọn xóa vì vật tư vẫn còn lô hàng trong kho' : 'Chọn vật tư' }}"
                            {{ $isDeleteBlocked ? 'disabled' : '' }}>
                    </td>
                    <td class="px-4 py-3 font-semibold text-sm text-gray-500 whitespace-nowrap">
                        VT-{{ str_pad($material->id, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center">
                                @php
                                    $matIcon = 'fa-cubes';
                                    $lowerName = strtolower($material->name);
                                    if (str_contains($lowerName, 'ly') || str_contains($lowerName, 'nắp')) {
                                        $matIcon = 'fa-mug-hot';
                                    } elseif (str_contains($lowerName, 'trà') || str_contains($lowerName, 'cà phê')) {
                                        $matIcon = 'fa-leaf';
                                    }
                                @endphp
                                <i class="fa-solid {{ $matIcon }} text-emerald-600 text-lg"></i>
                            </div>
                            <span class="font-semibold text-sm text-gray-900 truncate max-w-[150px] xl:max-w-[250px]" title="{{ $material->name }}">{{ $material->name }}</span>
                        </div>
                    </td>
                    <td
                        class="px-4 py-3 font-semibold text-sm {{ $material->current_stock < 5 ? 'text-red-600' : 'text-gray-900' }} whitespace-nowrap">
                        {{ $material->current_stock }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-sm text-gray-900 whitespace-nowrap">
                        {{ $material->unit }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden min-w-[100px]">
                            <div class="{{ $barColor }} h-full rounded-full" style="width: {{ $barWidth }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex flex-row items-center gap-1 flex-wrap">
                            @foreach($statuses as $statusItem)
                                <span class="inline-flex items-center gap-1 px-2 py-1 {{ $statusItem['color'] }} rounded-lg font-medium text-xs">
                                    <span class="w-2 h-2 rounded-full {{ $statusItem['dot'] }}"></span>
                                    {{ $statusItem['text'] }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <div class="flex justify-center gap-1.5">
                            <a href="{{ route('admin.materials.imports', $material->id) }}"
                                class="text-primary hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 p-1.5 rounded-md transition-colors inline-block"
                                title="Xem chi tiết">
                                <i class="fa-solid fa-eye text-[14px]"></i>
                            </a>
                            @if($isDeleteBlocked)
                                <button type="button" disabled
                                    class="p-1.5 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed"
                                    title="Không thể xóa vì vật tư vẫn còn {{ $material->active_lots_count }} lô hàng trong kho">
                                    <i class="fa-solid fa-trash-can text-[14px]"></i>
                                </button>
                            @else
                                <form action="{{ route('admin.materials.destroy', $material->id) }}" method="POST" class="js-material-delete-form inline-block m-0 p-0"
                            onsubmit="return confirm('Xóa vật tư này? Hành động này không thể hoàn tác.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors group/btn" title="Xóa">
                                        <i class="fa-solid fa-trash-can text-[14px]"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fa-solid fa-magnifying-glass-chart text-5xl text-gray-200 mb-3"></i>
                            <p class="text-gray-500 text-lg font-medium">Không tìm thấy vật tư nào</p>
                            <p class="text-gray-400 text-sm mt-1">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>

@if(method_exists($materials, 'hasPages') && $materials->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl ajax-pagination mt-auto">
        {{ $materials->links('pagination::tailwind') }}
    </div>
@endif

<input type="hidden" id="total-materials-count" value="{{ $deletableMaterialsCount ?? (method_exists($materials, 'total') ? $materials->total() : count($materials)) }}">
