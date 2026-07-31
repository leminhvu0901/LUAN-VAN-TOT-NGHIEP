{{--
Khối chọn PHẠM VI ÁP DỤNG khuyến mãi + các trường phụ thuộc phạm vi.
Dùng chung cho cả create.blade.php và edit.blade.php.

Biến truyền vào:
- $products, $categories: danh sách để chọn
- $selectedScope: 'order' | 'product' | 'category' | 'combo'
- $selectedProductIds, $selectedCategoryIds: mảng id đang chọn
- $combo: bản ghi PromotionCombo đang có (null khi thêm mới)
- $comboItems: Collection PromotionComboItem đang có (rỗng khi thêm mới)

JS ẩn/hiện các khối theo phạm vi nằm ở form-common.js (hàm updateScopeUI).
--}}
@php
    $oldComboProductIds = old('combo_product_ids');
    $oldComboQuantities = old('combo_quantities');
    if (is_array($oldComboProductIds)) {
        $comboRows = collect($oldComboProductIds)->map(fn ($pid, $i) => ['product_id' => $pid, 'quantity' => $oldComboQuantities[$i] ?? 1])->values();
    } else {
        $comboRows = ($comboItems ?? collect())->map(fn ($ci) => ['product_id' => $ci->product_id, 'quantity' => $ci->quantity])->values();
    }
    if ($comboRows->isEmpty()) {
        $comboRows = collect([['product_id' => '', 'quantity' => 1]]);
    }
    $comboDiscountType = old('discount_type', $combo->discount_type ?? 'percent');
@endphp
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
            'combo' => ['icon' => 'redeem', 'title' => 'Combo', 'desc' => 'Mua đủ nhiều sản phẩm, giảm giá và/hoặc tặng quà'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5" id="scope-selector">
        @foreach($scopeOptions as $value => $opt)
            <label
                class="scope-option flex items-center gap-3 border-2 {{ $selectedScope === $value ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-4 cursor-pointer transition-all hover:border-gray-300"
                data-scope="{{ $value }}">
                <input type="radio" name="scope" value="{{ $value }}" class="hidden" {{ $selectedScope === $value ? 'checked' : '' }}>
                <div
                    class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">{{ $opt['icon'] }}</span>
                </div>
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $opt['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $opt['desc'] }}</p>
                </div>
            </label>
        @endforeach
    </div>
    @error('scope')
    <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

    {{-- Chọn sản phẩm (scope=product) --}}
    <div id="scope-product-fields" class="{{ $selectedScope === 'product' ? '' : 'hidden' }}">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Sản phẩm được áp dụng <span
                class="text-red-500">*</span></label>
        <input type="text" id="product-search" placeholder="Tìm nhanh theo tên sản phẩm..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm mb-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-100 rounded-xl p-3"
            id="product-picker-list">
            @foreach($products as $product)
                <label class="product-option flex items-center gap-2 text-sm py-1"
                    data-name="{{ Str::lower($product->name) }}">
                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" {{ in_array($product->id, $selectedProductIds) ? 'checked' : '' }}
                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                    <span class="text-gray-700">{{ $product->name }}</span>
                </label>
            @endforeach
        </div>
        @error('product_ids')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-gray-400 mt-1">Số tiền giảm chỉ tính trên tổng tiền của những sản phẩm được chọn.</p>
    </div>

    {{-- Chọn danh mục (scope=category) --}}
    <div id="scope-category-fields" class="{{ $selectedScope === 'category' ? '' : 'hidden' }}">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Danh mục được áp dụng <span
                class="text-red-500">*</span></label>
        <div
            class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-100 rounded-xl p-3">
            @foreach($categories as $category)
                <label class="flex items-center gap-2 text-sm py-1">
                    <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" {{ in_array($category->id, $selectedCategoryIds) ? 'checked' : '' }}
                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                    <span class="text-gray-700">{{ $category->name }}</span>
                </label>
            @endforeach
        </div>
        @error('category_ids')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-gray-400 mt-1">Số tiền giảm chỉ tính trên tổng tiền của các món thuộc danh mục được chọn.
        </p>
    </div>

    {{-- Cấu hình Combo (scope=combo) --}}
    <div id="scope-combo-fields" class="{{ $selectedScope === 'combo' ? '' : 'hidden' }} space-y-5">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sản phẩm bắt buộc trong combo <span
                    class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 mb-2">Khách phải mua ĐỦ tất cả sản phẩm dưới đây (đúng số lượng) mới được
                tính là đã mua combo.</p>
            <div id="combo-items" class="space-y-2">
                @foreach($comboRows as $row)
                    <div class="combo-item-row grid grid-cols-1 sm:grid-cols-[1fr_120px_40px] gap-2">
                        <select name="combo_product_ids[]"
                            class="custom-select-init w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white"
                            data-width-class="w-full">
                            <option value="">-- Chọn sản phẩm --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ (string) $row['product_id'] === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <input name="combo_quantities[]" type="number" min="1" value="{{ $row['quantity'] }}"
                            placeholder="SL" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <button type="button" class="js-remove-combo-item w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg"
                            title="Xóa sản phẩm"><span class="material-symbols-outlined">delete</span></button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-combo-item"
                class="mt-2 text-sm font-semibold text-emerald-700 flex items-center gap-1"><span
                    class="material-symbols-outlined text-[18px]">add</span>Thêm sản phẩm</button>
            @error('combo_product_ids')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            Combo phải bật ít nhất 1 trong 2 thưởng bên dưới — có thể bật cả 2 cùng lúc.
        </div>

        {{-- Thành phần 1: Giảm giá (độc lập, không loại trừ với Tặng quà) --}}
        <div class="border border-gray-200 rounded-xl p-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <div class="relative">
                    <input type="checkbox" name="combo_has_discount" id="combo_has_discount" value="1" {{ old('combo_has_discount', $combo?->hasDiscount()) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Giảm giá</p>
                    <p class="text-xs text-gray-400">Giảm % hoặc tiền cố định, chỉ tính trên giá trị các sản phẩm trong
                        combo</p>
                </div>
            </label>
            <div id="combo-discount-fields"
                class="mt-4 space-y-4 {{ old('combo_has_discount', $combo?->hasDiscount()) ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label
                        class="flex items-center gap-3 border-2 {{ $comboDiscountType == 'percent' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-3 cursor-pointer transition-all"
                        id="combo-discount-type-percent-label">
                        <input type="radio" name="discount_type" value="percent" class="hidden" {{ $comboDiscountType == 'percent' ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-gray-700">Giảm theo %</span>
                    </label>
                    <label
                        class="flex items-center gap-3 border-2 {{ $comboDiscountType == 'fixed' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-3 cursor-pointer transition-all"
                        id="combo-discount-type-fixed-label">
                        <input type="radio" name="discount_type" value="fixed" class="hidden" {{ $comboDiscountType == 'fixed' ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-gray-700">Giảm tiền cố định</span>
                    </label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá trị giảm <span
                                class="text-red-500">*</span> <span id="combo-discount-value-unit"
                                class="font-normal text-gray-500">{{ $comboDiscountType == 'percent' ? '(% tỷ lệ)' : '(VNĐ cố định)' }}</span></label>
                        <input type="number" name="discount_value" id="combo-discount-value" min="0"
                            step="{{ $comboDiscountType == 'percent' ? '1' : '1000' }}"
                            max="{{ $comboDiscountType == 'percent' ? '100' : '' }}"
                            value="{{ old('discount_value', $combo->discount_value ?? '') }}"
                            placeholder="{{ $comboDiscountType == 'percent' ? 'VD: 15' : 'VD: 10000' }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        @error('discount_value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div id="combo-max-discount-wrap" class="{{ $comboDiscountType == 'percent' ? '' : 'hidden' }}">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giảm tối đa (VNĐ)</label>
                        <input type="number" name="combo_max_discount_amount" min="0" step="1000"
                            value="{{ old('combo_max_discount_amount', $combo->max_discount_amount ?? '') }}"
                            placeholder="Không giới hạn"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                    </div>
                </div>
                @error('discount_type')
                <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Thành phần 2: Tặng quà (độc lập, không loại trừ với Giảm giá) --}}
        <div class="border border-gray-200 rounded-xl p-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <div class="relative">
                    <input type="checkbox" name="combo_has_gift" id="combo_has_gift" value="1" {{ old('combo_has_gift', $combo?->hasGift()) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Tặng quà</p>
                    <p class="text-xs text-gray-400">Tặng thêm sản phẩm khi mua đủ combo — cộng thêm độc lập, không phụ
                        thuộc phần giảm giá</p>
                </div>
            </label>
            <div id="combo-gift-fields"
                class="mt-4 space-y-4 {{ old('combo_has_gift', $combo?->hasGift()) ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sản phẩm được tặng <span
                                class="text-red-500">*</span></label>
                        <select name="gift_product_id"
                            class="custom-select-init w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white"
                            data-width-class="w-full">
                            <option value="">-- Chọn sản phẩm --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('gift_product_id', $combo->gift_product_id ?? null) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('gift_product_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng tặng <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="gift_quantity" min="1"
                            value="{{ old('gift_quantity', $combo->gift_quantity ?? '') }}" placeholder="VD: 1"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        @error('gift_quantity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="auto_add_gift" value="1" {{ old('auto_add_gift', $combo ? $combo->auto_add_gift : true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors">
                        </div>
                        <div
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5">
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Tự động thêm quà</p>
                        <p class="text-xs text-gray-400">Tự thêm quà vào đơn khi đủ điều kiện</p>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Giới hạn số lần áp dụng / đơn</label>
            <input type="number" name="max_applications_per_order" min="1"
                value="{{ old('max_applications_per_order', $combo->max_applications_per_order ?? '') }}"
                placeholder="Không giới hạn"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
            <p class="text-xs text-gray-400 mt-1">Áp dụng chung cho combo. Vd: 1 = mua nhiều lần combo trong 1 đơn vẫn
                chỉ tính 1 lần. Để trống = mua càng nhiều tính càng nhiều.</p>
        </div>
    </div>
</div>