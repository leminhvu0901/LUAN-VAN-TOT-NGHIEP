        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Mã SP (SKU)</th>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Sản phẩm</th>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Danh mục</th>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Giá bán</th>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap">Trạng thái</th>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center whitespace-nowrap">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-4 py-3 text-sm font-bold text-gray-700 whitespace-nowrap">
                                {{ $product->sku }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center flex-shrink-0 overflow-hidden {{ !$product->is_active ? 'grayscale opacity-60' : '' }}">
                                        @php
                                            $imageUrl = $product->image ? asset('images/' . $product->image) : '';
                                        @endphp

                                        @if($imageUrl)
                                            <img class="w-full h-full object-cover" src="{{ $imageUrl }}" alt="{{ $product->name }}"/>
                                        @else
                                            <span class="material-symbols-outlined text-gray-400">image</span>
                                        @endif
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900">{{ $product->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-sm text-gray-600 whitespace-nowrap">
                                {{ $product->category->name ?? 'Không phân loại' }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-sm text-gray-900 whitespace-nowrap">
                                {{ number_format($product->base_price, 0, ',', '.') }} đ
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($product->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-medium text-xs whitespace-nowrap">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Đang kinh doanh
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 rounded-lg font-medium text-xs whitespace-nowrap">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    Ngừng kinh doanh
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex justify-center gap-1.5">
                                    <a href="{{ route('admin.products.edit', $product->id) }}"
                                        class="p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors group/btn" title="Sửa">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors group/btn" title="Xóa">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <span class="material-symbols-outlined text-5xl">inventory_2</span>
                                    <span class="font-medium">Không tìm thấy sản phẩm nào.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 pagination-container">
            {{ $products->links() }}
        </div>
        @endif
