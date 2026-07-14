<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="w-10 px-4 py-3 text-center">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-colors cursor-pointer w-4 h-4">
                </th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Tên danh mục</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Thứ tự hiển thị</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Trạng thái</th>
                <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($categories as $category)
                <tr class="hover:bg-gray-50/50 transition-colors group" id="category-row-{{ $category->id }}">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="row-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-colors cursor-pointer w-4 h-4" value="{{ $category->id }}">
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <span class="text-sm font-bold text-gray-800 tracking-wide truncate" title="{{ $category->name }}">{{ $category->name }}</span>
                            <span class="text-xs text-gray-400 truncate">ID: {{ $category->id }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-gray-100 text-gray-700 rounded-md font-semibold text-xs border border-gray-200">
                            {{ $category->display_order }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        @if($category->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-medium text-xs whitespace-nowrap">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                Đang hiển thị
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg font-medium text-xs whitespace-nowrap">
                                <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                                Đang ẩn
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <div class="flex justify-center gap-1.5">
                            <a href="{{ route('admin.categories.edit', $category->id) }}"
                                class="p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Sửa">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                            <button type="button"
                                onclick="deleteCategory({{ $category->id }}, '{{ $category->name }}')"
                                class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Xóa">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3 text-gray-400">
                            <span class="material-symbols-outlined text-5xl">category</span>
                            <span class="font-medium">Không tìm thấy danh mục nào.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($categories->hasPages())
<div class="px-6 py-4 border-t border-gray-100 ajax-pagination">
    {{ $categories->links() }}
</div>
@endif
<input type="hidden" id="total-categories-count" value="{{ $categories->total() }}">
