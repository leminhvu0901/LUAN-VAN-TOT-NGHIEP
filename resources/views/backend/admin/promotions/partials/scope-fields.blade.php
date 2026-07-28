{{--
    Khối chọn PHẠM VI ÁP DỤNG khuyến mãi + các trường phụ thuộc phạm vi.
    Dùng chung cho cả create.blade.php và edit.blade.php.

    Biến truyền vào:
    - $products, $categories: danh sách để chọn
    - $selectedScope: 'order' | 'product' | 'category' | 'buy_x_get_y'
    - $selectedProductIds, $selectedCategoryIds: mảng id đang chọn
    - $bxgy: bản ghi PromotionBuyXGetY đang có (null khi thêm mới)

    JS ẩn/hiện các khối theo phạm vi nằm ở form-common.js (hàm updateScopeUI).
--}}
<div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-4 sm:p-6">
    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
        <span class="material-symbols-outlined text-violet-500 text-[20px] icon-fill">category</span>
        Phạm vi áp dụng
    </h3>

    @php
        $scopeOptions = [
            'order' => ['icon' => 'receipt_long', 'title' => 'Giảm toàn đơn', 'desc' => 'Giảm trên tổng tiền cả đơn hàng'],
            'product' => ['icon' => 'local_cafe', 'title' => 'Giảm theo sản phẩm', 'desc' => 'Chỉ giảm trên các món được chọn'],
            'category' => ['icon' => 'folder', 'title' => 'Giảm theo danh mục', 'desc' => 'Chỉ giảm trên các danh mục được chọn'],
            'buy_x_get_y' => ['icon' => 'redeem', 'title' => 'Mua X tặng Y', 'desc' => 'Tặng món khi mua đủ số lượng'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5" id="scope-selector">
        @foreach($scopeOptions as $value => $opt)
            <label class="scope-option flex items-center gap-3 border-2 {{ $selectedScope === $value ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-4 cursor-pointer transition-all hover:border-gray-300"
                data-scope="{{ $value }}">
                <input type="radio" name="scope" value="{{ $value }}" class="hidden" {{ $selectedScope === $value ? 'checked' : '' }}>
                <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">{{ $opt['icon'] }}</span>
                </div>
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $opt['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $opt['desc'] }}</p>
                </div>
            </label>
        @endforeach
    </div>
    @error('scope') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

    {{-- Chọn sản phẩm (scope=product) --}}
    <div id="scope-product-fields" class="{{ $selectedScope === 'product' ? '' : 'hidden' }}">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Sản phẩm được áp dụng <span class="text-red-500">*</span></label>
        <input type="text" id="product-search" placeholder="Tìm nhanh theo tên sản phẩm..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-100 rounded-xl p-3" id="product-picker-list">
            @foreach($products as $product)
                <label class="product-option flex items-center gap-2 text-sm py-1" data-name="{{ Str::lower($product->name) }}">
                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                        {{ in_array($product->id, $selectedProductIds) ? 'checked' : '' }}
                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                    <span class="text-gray-700">{{ $product->name }}</span>
                </label>
            @endforeach
        </div>
        @error('product_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-gray-400 mt-1">Số tiền giảm chỉ tính trên tổng tiền của những sản phẩm được chọn.</p>
    </div>

    {{-- Chọn danh mục (scope=category) --}}
    <div id="scope-category-fields" class="{{ $selectedScope === 'category' ? '' : 'hidden' }}">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Danh mục được áp dụng <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-100 rounded-xl p-3">
            @foreach($categories as $category)
                <label class="flex items-center gap-2 text-sm py-1">
                    <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                        {{ in_array($category->id, $selectedCategoryIds) ? 'checked' : '' }}
                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                    <span class="text-gray-700">{{ $category->name }}</span>
                </label>
            @endforeach
        </div>
        @error('category_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-gray-400 mt-1">Số tiền giảm chỉ tính trên tổng tiền của các món thuộc danh mục được chọn.</p>
    </div>

    {{-- Cấu hình Mua X tặng Y (scope=buy_x_get_y) --}}
    <div id="scope-bxgy-fields" class="{{ $selectedScope === 'buy_x_get_y' ? '' : 'hidden' }} space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sản phẩm cần mua <span class="text-red-500">*</span></label>
                <select name="buy_product_id"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white">
                    <option value="">-- Chọn sản phẩm --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('buy_product_id', $bxgy?->buy_product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('buy_product_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng cần mua (X) <span class="text-red-500">*</span></label>
                <input type="number" name="buy_quantity" min="1" value="{{ old('buy_quantity', $bxgy?->buy_quantity) }}" placeholder="VD: 2"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                @error('buy_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sản phẩm được tặng <span class="text-red-500">*</span></label>
                <select name="gift_product_id"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white">
                    <option value="">-- Chọn sản phẩm --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('gift_product_id', $bxgy?->gift_product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Có thể chọn trùng với sản phẩm cần mua (vd: mua 2 tặng 1 cùng loại).</p>
                @error('gift_product_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng tặng (Y) <span class="text-red-500">*</span></label>
                <input type="number" name="gift_quantity" min="1" value="{{ old('gift_quantity', $bxgy?->gift_quantity) }}" placeholder="VD: 1"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                @error('gift_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Giới hạn số lần áp dụng / đơn</label>
                <input type="number" name="max_applications_per_order" min="1" value="{{ old('max_applications_per_order', $bxgy?->max_applications_per_order) }}" placeholder="Không giới hạn"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                <p class="text-xs text-gray-400 mt-1">Vd: 1 = mua 4 vẫn chỉ tặng 1 lần. Để trống = mua càng nhiều tặng càng nhiều.</p>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="auto_add_gift" value="1"
                            {{ old('auto_add_gift', $bxgy ? $bxgy->auto_add_gift : true) ? 'checked' : '' }}
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Tự động thêm quà</p>
                        <p class="text-xs text-gray-400">Tự thêm quà vào đơn khi đủ điều kiện</p>
                    </div>
                </label>
            </div>
        </div>
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            Chương trình Mua X tặng Y không giảm tiền nên không cần "Loại khuyến mãi"/"Giá trị giảm".
            Quà tặng được cộng thêm độc lập — một đơn có thể vừa dùng mã giảm giá vừa nhận quà.
        </div>
    </div>
</div>
