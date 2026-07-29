        <div class="flex-1 overflow-y-auto overflow-x-hidden lg:overflow-x-auto custom-scrollbar relative w-full">
            <table class="w-full text-left border-collapse hidden lg:table relative">
                <thead class="bg-gray-50 border-b border-gray-100 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-xs text-gray-500 uppercase tracking-wider whitespace-nowrap w-10 text-center">
                            <input type="checkbox" class="js-select-all rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
                        </th>
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
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-700 whitespace-nowrap">
                                {{ $product->sku }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center flex-shrink-0 overflow-hidden {{ !$product->is_active ? 'grayscale opacity-60' : '' }}">
                                        @php
                                            $imageUrl = $product->image ? $product->image_url : '';
                                        @endphp

                                        @if($imageUrl)
                                            <img class="w-full h-full object-cover" src="{{ $imageUrl }}" alt="{{ $product->name }}"/>
                                        @else
                                            <span class="material-symbols-outlined text-gray-400">image</span>
                                        @endif
                                    </div>
                                    <span class="font-semibold text-sm text-gray-900 truncate max-w-[150px] xl:max-w-[250px]" title="{{ $product->name }}">{{ $product->name }}</span>
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
                                <span class="badge-status badge-active">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Đang kinh doanh
                                </span>
                                @else
                                <span class="badge-status badge-inactive">
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
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="js-product-delete-form inline-block m-0 p-0" data-ajax="true">
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
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-6xl text-gray-200 mb-4">search_off</span>
                                    <p class="text-gray-500 text-lg font-medium">Không tìm thấy sản phẩm nào</p>
                                    <p class="text-gray-400 text-sm mt-1">Vui lòng thử lại với từ khóa hoặc bộ lọc khác.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Mobile Cards View -->
            <div class="block lg:hidden flex flex-col gap-3 p-3 w-full">
                @if(count($products) > 0)
                    <div class="flex items-center justify-between mb-2 px-1">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                            <input type="checkbox" class="js-select-all rounded border-gray-300 text-primary shadow-sm focus:ring-primary">
                            <span>Chọn tất cả trang này</span>
                        </label>
                    </div>
                @endif

                @forelse($products as $product)
                    @php
                        $imageUrl = $product->image ? $product->image_url : '';
                    @endphp
                    <div class="mobile-card relative group w-full {{ !$product->is_active ? 'opacity-75' : '' }}">
                        <div class="absolute top-4 right-4 z-10">
                            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox w-5 h-5 rounded border-gray-300 text-primary shadow-sm focus:ring-primary cursor-pointer">
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="product-image-preview bg-gray-50 flex items-center justify-center flex-shrink-0 overflow-hidden border border-gray-200">
                                @if($imageUrl)
                                    <img class="w-full h-full object-cover {{ !$product->is_active ? 'grayscale' : '' }}" src="{{ $imageUrl }}" alt="{{ $product->name }}"/>
                                @else
                                    <span class="material-symbols-outlined text-gray-400 text-2xl">image</span>
                                @endif
                            </div>
                            
                            <div class="flex-1 min-w-0 pr-8">
                                <h4 class="font-bold text-gray-900 text-base leading-tight mb-1 truncate" title="{{ $product->name }}">{{ $product->name }}</h4>
                                <p class="text-xs text-gray-500 font-medium mb-1">SKU: {{ $product->sku ?: '---' }}</p>
                                <p class="text-xs text-emerald-600 font-medium">{{ $product->category->name ?? 'Không phân loại' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div class="bg-gray-50 p-2 rounded-lg">
                                <p class="text-[11px] text-gray-500 font-medium mb-0.5">Giá bán</p>
                                <p class="font-bold text-gray-900 text-sm">{{ number_format($product->base_price, 0, ',', '.') }} đ</p>
                            </div>
                            <div class="bg-gray-50 p-2 rounded-lg">
                                <p class="text-[11px] text-gray-500 font-medium mb-0.5">Trạng thái</p>
                                @if($product->is_active)
                                    <span class="badge-status badge-active">Đang bán</span>
                                @else
                                    <span class="badge-status badge-inactive">Ngừng bán</span>
                                @endif
                            </div>
                        </div>

                        <div class="mobile-card-actions">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg font-medium text-sm hover:bg-blue-100 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                Sửa
                            </a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="js-product-delete-form flex-1 flex" data-ajax="true">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 text-red-600 rounded-lg font-medium text-sm hover:bg-red-100 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-gray-300 mb-3 block">search_off</span>
                        <p class="text-gray-500 font-medium">Không tìm thấy sản phẩm nào</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 ajax-pagination mt-auto">
            {{ $products->links() }}
        </div>
        @endif
