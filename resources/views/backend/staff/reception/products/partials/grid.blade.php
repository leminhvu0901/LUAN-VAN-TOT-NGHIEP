{{-- Lưới sản phẩm + phân trang — tách riêng để tái sử dụng cho cả lần tải trang đầu tiên lẫn phản hồi
AJAX khi lọc/chuyển trang (ProductController::index() trả về đúng partial này khi $request->expectsJson()). --}}
<div id="reception-products-grid-area">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($products as $product)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden {{ !$product->is_active ? 'opacity-60' : '' }}">
                <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                </div>
                <div class="p-3">
                    <p class="font-semibold text-sm text-gray-900 truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $product->category->name ?? '' }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-emerald-600 font-bold text-sm">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                        @if($product->is_active)
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Đang bán</span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700">Hết hàng</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400">Không tìm thấy sản phẩm nào.</div>
        @endforelse
    </div>

    @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="pt-2">{{ $products->links('pagination::tailwind') }}</div>
    @endif
</div>
