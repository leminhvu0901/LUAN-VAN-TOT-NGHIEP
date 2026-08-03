@extends('backend.layouts.app')

@section('title', 'Cập nhật Sản phẩm')



@section('content')
<div class="p-4 sm:p-6 space-y-4 sm:space-y-6 products-page">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('admin.products.index') }}"
            onclick="smartGoBack(event)"
            class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Cập nhật Sản phẩm</h2>
            <p class="text-gray-500 text-sm mt-1">Sửa thông tin sản phẩm: <span class="font-semibold">{{ $product->name }}</span></p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl organic-shadow overflow-hidden border border-gray-100">
        <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="back_url" value="{{ $backUrl }}">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Image -->
                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hình ảnh sản phẩm</label>
                    <div class="js-image-upload-trigger border-2 border-dashed border-gray-300 rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 hover:border-emerald-500 transition-all relative overflow-hidden group h-64">
                        <input type="file" id="image-upload" name="image" class="hidden" accept="image/*">
                        
                        {{-- upload_url() (qua accessor image_url) tạo URL từ đường dẫn tương đối trong
                             public/images/. --}}
                        @php
                            $imageUrl = $product->image ? $product->image_url : '';
                        @endphp

                        <div id="image-placeholder" class="flex flex-col items-center {{ $imageUrl ? 'hidden' : '' }}">
                            <span class="material-symbols-outlined text-5xl text-gray-400 mb-3 group-hover:text-emerald-500 transition-colors">cloud_upload</span>
                            <span class="font-medium text-gray-700">Nhấn để thay đổi ảnh</span>
                            <span class="text-sm text-gray-500 mt-1">PNG, JPG (Tối đa 2MB)</span>
                        </div>
                        
                        <img id="image-preview" class="absolute inset-0 w-full h-full object-cover {{ $imageUrl ? '' : 'hidden' }}" src="{{ $imageUrl }}" alt="Preview">
                        
                        @if($imageUrl)
                        <!-- Overlay for changing image -->
                        <div class="absolute inset-0 bg-black/50 hidden flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="material-symbols-outlined mb-1">edit</span>
                            <span class="text-sm font-medium">Đổi ảnh</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Right Column: Details -->
                <div class="lg:col-span-2 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        <p class="text-xs text-gray-500 mt-1">Tối đa 50 ký tự.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                            <select name="category_id" required class="product-category-select w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white">
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mã SKU (Tùy chọn)</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-gray-50 cursor-not-allowed" readonly title="Mã SKU không thể thay đổi">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá bán cơ bản (VNĐ) <span class="text-red-500">*</span></label>
                        <input type="text" id="display_price" value="{{ old('base_price', $product->base_price) ? number_format(old('base_price', $product->base_price), 0, ',', '.') : '' }}" placeholder="VD: 25.000" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        <input type="hidden" id="raw_price" name="base_price" value="{{ old('base_price', $product->base_price) }}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả ngắn gọn</label>
                        <textarea name="description" rows="3" maxlength="500" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">{{ old('description', $product->description) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Tối đa 500 ký tự.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Bộ sưu tập ảnh (Ảnh phụ)</label>
                        
                        @if($product->images && $product->images->count() > 0)
                        <div class="flex flex-wrap gap-3 mb-4">
                            @foreach($product->images as $galleryImg)
                                <div class="relative group gallery-item" id="gallery-item-{{ $galleryImg->id }}">
                                    <img src="{{ $galleryImg->image_url }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200" alt="Gallery image">
                                    <button type="button" class="js-delete-gallery-image absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-md hover:bg-red-600 focus:outline-none" data-gallery-image-id="{{ $galleryImg->id }}" title="Xóa ảnh này">
                                        <span class="material-symbols-outlined text-[14px] leading-none">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <input type="file" id="gallery-input" name="gallery[]" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <p class="text-xs text-gray-500 mt-1">Tối đa 5 ảnh phụ tổng cộng.</p>
                        <div id="gallery-preview-container" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 mt-3"></div>
                    </div>
                    
                    @php
                        $oldSizeNames = old('size_names', $product->sizes->pluck('size_name')->all());
                        $oldSizePrices = old('size_price_adjustments', $product->sizes->pluck('price_adjustment')->all());
                        if (count($oldSizeNames) === 0) $oldSizeNames = [''];
                        $selectedToppings = old('topping_ids', $product->toppings->pluck('id')->all());
                    @endphp
                    <div class="border-t border-gray-100 pt-5 space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Kích thước và giá cộng thêm</h3>
                            <div id="product-sizes" class="space-y-2 mt-2">
                                @foreach($oldSizeNames as $index => $sizeName)
                                <div class="product-topping-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_40px] gap-2">
                                    <input name="size_names[]" value="{{ $sizeName }}" maxlength="50" placeholder="Tên size" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <input name="size_price_adjustments[]" type="number" min="0" max="50000000" step="1000" value="{{ $oldSizePrices[$index] ?? 0 }}" placeholder="Giá cộng thêm" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <button type="button" class="js-remove-size w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa kích thước"><span class="material-symbols-outlined">delete</span></button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" id="add-product-size" class="mt-2 text-sm font-semibold text-emerald-700 flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">add</span>Thêm kích thước</button>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Topping áp dụng</h3>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                @foreach($toppings as $topping)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="topping_ids[]" value="{{ $topping->id }}" {{ in_array($topping->id, $selectedToppings) ? 'checked' : '' }}>{{ $topping->name }}</label>
                                @endforeach
                            </div>
                        </div>

                    </div>
                    <div class="flex items-center mt-2 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            <span class="text-sm font-semibold text-gray-700">Đang kinh doanh (Hiển thị)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.products.index') }}"
                    onclick="smartGoBack(event)"
                    class="w-full sm:w-auto px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors text-center">
                    Hủy bỏ
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm text-center flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/admin/products/form-common.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/backend/admin/products/edit.js') }}?v={{ time() }}"></script>
@endpush
