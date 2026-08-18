<div class="flex-1 overflow-y-auto overflow-x-hidden lg:overflow-x-auto custom-scrollbar relative w-full">
@if($categories->count() > 0)
    <!-- Giao diện Mobile -->
    <div class="block md:hidden space-y-4 p-4 w-full">
        <div class="flex items-center justify-between mb-2">
            <label class="flex items-center gap-2 text-sm text-gray-600 font-medium cursor-pointer">
                <input type="checkbox" id="selectAll-mobile" class="js-select-all rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 transition-colors">
                <span>Chọn tất cả</span>
            </label>
        </div>

        @foreach($categories as $category)
            <div class="bg-white p-4 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3 relative group" id="category-card-{{ $category->id }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-base font-bold text-gray-900" style="overflow-wrap: anywhere; word-break: break-word;">{{ $category->name }}</span>
                        <span class="text-xs text-gray-500 mt-0.5">ID: {{ $category->id }} • Thứ tự hiển thị: <span class="font-semibold text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded">{{ $category->display_order }}</span></span>
                    </div>
                    <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-colors w-4 h-4 shrink-0 {{ $category->products_count > 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}" value="{{ $category->id }}" {{ $category->products_count > 0 ? 'disabled title="Không thể chọn vì danh mục đang có sản phẩm"' : '' }}>
                </div>
                
                <hr class="border-gray-100 border-dashed">
                
                <div class="flex items-center justify-between">
                    <div>
                        @if($category->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-medium text-[11px] whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                Đang hiển thị
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg font-medium text-[11px] whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                Đang ẩn
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                            class="px-3 py-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1" title="Sửa">
                            <i class="fa-solid fa-pen text-[13px]"></i> Sửa
                        </a>
                        @if ($category->products_count > 0)
                            <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 text-gray-400 bg-gray-50 cursor-not-allowed" title="Không thể xóa danh mục đang có sản phẩm" disabled>
                                <i class="fa-solid fa-trash-can text-[13px]"></i> Xóa
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" onsubmit="return confirm('Xóa danh mục này? Hành động này không thể hoàn tác.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg transition-colors text-xs font-semibold flex items-center gap-1 text-red-600 bg-red-50 hover:bg-red-100" title="Xóa">
                                    <i class="fa-solid fa-trash-can text-[13px]"></i> Xóa
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Giao diện Desktop -->
    <div class="hidden md:block w-full">
        <table class="w-full text-left border-collapse relative">
            <thead class="bg-gray-50 border-b border-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="w-10 px-3 xl:px-4 py-3 text-center">
                        <input type="checkbox" id="selectAll" class="js-select-all rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-colors cursor-pointer w-4 h-4">
                    </th>
                    <th class="px-3 xl:px-4 py-3 font-semibold text-[11px] xl:text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Tên danh mục</th>
                    <th class="px-3 xl:px-4 py-3 font-semibold text-[11px] xl:text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Thứ tự hiển thị</th>
                    <th class="px-3 xl:px-4 py-3 font-semibold text-[11px] xl:text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Trạng thái</th>
                    <th class="px-3 xl:px-4 py-3 font-semibold text-[11px] xl:text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Hành động</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($categories as $category)
                    <tr class="hover:bg-gray-50/50 transition-colors group" id="category-row-{{ $category->id }}">
                        <td class="px-3 xl:px-4 py-3 text-center">
                            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-colors w-4 h-4 {{ $category->products_count > 0 ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}" value="{{ $category->id }}" {!! $category->products_count > 0 ? 'disabled title="Không thể chọn vì danh mục đang có sản phẩm"' : '' !!}>
                        </td>
                        <td class="px-3 xl:px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-sm font-bold text-gray-800 tracking-wide truncate max-w-[150px]" title="{{ $category->name }}">{{ $category->name }}</span>
                                <span class="text-[11px] xl:text-xs text-gray-400 truncate">ID: {{ $category->id }}</span>
                            </div>
                        </td>
                        <td class="px-3 xl:px-4 py-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-gray-100 text-gray-700 rounded-md font-semibold text-[11px] xl:text-xs border border-gray-200">
                                {{ $category->display_order }}
                            </span>
                        </td>
                        <td class="px-3 xl:px-4 py-3 text-center whitespace-nowrap">
                            @if($category->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-medium text-[11px] xl:text-xs whitespace-nowrap scale-90 origin-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                    Đang hiển thị
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg font-medium text-[11px] xl:text-xs whitespace-nowrap scale-90 origin-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                    Đang ẩn
                                </span>
                            @endif
                        </td>
                        <td class="px-3 xl:px-4 py-3 text-center whitespace-nowrap">
                            <div class="flex justify-center gap-1.5">
                                <a href="{{ route('admin.categories.edit', $category->id) }}"
                                    class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors" title="Sửa">
                                    <i class="fa-solid fa-pen text-[14px]"></i>
                                </a>
                                @if ($category->products_count > 0)
                                    <button type="button" class="p-1.5 rounded-lg text-gray-400 bg-gray-50 cursor-not-allowed" title="Không thể xóa danh mục đang có sản phẩm" disabled>
                                        <i class="fa-solid fa-trash-can text-[14px]"></i>
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category->id) }}" onsubmit="return confirm('Xóa danh mục này? Hành động này không thể hoàn tác.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg transition-colors text-red-600 bg-red-50 hover:bg-red-100" title="Xóa">
                                            <i class="fa-solid fa-trash-can text-[14px]"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@else
    <div class="p-12 text-center bg-white rounded-b-2xl h-full flex flex-col justify-center">
        <div class="flex flex-col items-center gap-3 text-gray-400">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner">
                <i class="fa-solid fa-layer-group text-3xl text-gray-300"></i>
            </div>
            <span class="font-medium text-gray-600 text-base">Không tìm thấy danh mục nào.</span>
            <p class="text-sm">Hãy thử thay đổi điều kiện lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    </div>
@endif
</div>

@if($categories->hasPages())
<div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl ajax-pagination mt-auto">
    {{ $categories->links('pagination::tailwind') }}
</div>
@endif

<input type="hidden" id="total-categories-count" value="{{ $categories->total() }}">
