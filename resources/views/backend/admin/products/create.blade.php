@extends('backend.layouts.app')

@section('title', 'Thêm Sản phẩm Mới')

@section('content')
<div class="p-4 sm:p-6 space-y-4 sm:space-y-6 products-page">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('admin.products.index') }}"
            onclick="smartGoBack(event)"
            class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fa-solid fa-arrow-left text-[14px]"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Thêm Sản phẩm Mới</h2>
            <p class="text-gray-500 text-sm mt-1">Tạo một sản phẩm mới để hiển thị trên menu.</p>
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
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <input type="hidden" name="back_url" value="{{ $backUrl }}">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Image -->
                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hình ảnh sản phẩm</label>
                    <div class="js-image-upload-trigger border-2 border-dashed border-gray-300 rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 hover:border-emerald-500 transition-all relative overflow-hidden group h-64">
                        <input type="file" id="image-upload" name="image" class="hidden" accept="image/*">
                        
                        <div id="image-placeholder" class="flex flex-col items-center">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400 mb-3 group-hover:text-emerald-500 transition-colors"></i>
                            <span class="font-medium text-gray-700">Nhấn để tải ảnh lên</span>
                            <span class="text-sm text-gray-500 mt-1">PNG, JPG (Tối đa 2MB)</span>
                        </div>
                        
                        <img id="image-preview" class="absolute inset-0 w-full h-full object-cover hidden" src="" alt="Preview">
                    </div>
                </div>
                
                <!-- Right Column: Details -->
                <div class="lg:col-span-2 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên sản phẩm <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        <p class="text-xs text-gray-500 mt-1">Tối đa 50 ký tự.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                            <select name="category_id" required class="product-category-select w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white">
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mã SKU (Tùy chọn)</label>
                            <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Để trống tự tạo" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá bán cơ bản (VNĐ) <span class="text-red-500">*</span></label>
                        <input type="text" id="display_price" value="{{ old('base_price') ? number_format(old('base_price'), 0, ',', '.') : '' }}" placeholder="VD: 25.000" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        <input type="hidden" id="raw_price" name="base_price" value="{{ old('base_price') }}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả ngắn gọn</label>
                        <textarea name="description" rows="3" maxlength="500" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">{{ old('description') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Tối đa 500 ký tự.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Bộ sưu tập ảnh (Ảnh phụ)</label>
                        <input type="file" id="gallery-input" name="gallery[]" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <p class="text-xs text-gray-500 mt-1">Tối đa 5 ảnh phụ.</p>
                        <div id="gallery-preview-container" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 mt-3"></div>
                    </div>
                    
                    <div class="border-t border-gray-100 pt-5 space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Kích thước và giá cộng thêm</h3>
                            <div id="product-sizes" class="space-y-2 mt-2">
                                @foreach(old('size_names', ['']) as $index => $sizeName)
                                <div class="product-topping-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_40px] gap-2">
                                    <input name="size_names[]" value="{{ $sizeName }}" maxlength="50" placeholder="Tên size" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <input name="size_price_adjustments[]" type="number" min="0" max="50000000" step="1000" value="{{ old('size_price_adjustments.' . $index, 0) }}" placeholder="Giá cộng thêm" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <button type="button" class="js-remove-size w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa kích thước"><i class="fa-solid fa-trash-can text-sm"></i></button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" id="add-product-size" class="mt-2 text-sm font-semibold text-emerald-700 flex items-center gap-1.5"><i class="fa-solid fa-plus text-[14px]"></i>Thêm kích thước</button>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Topping áp dụng</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                                @foreach($toppings as $topping)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="topping_ids[]" value="{{ $topping->id }}" {{ in_array($topping->id, old('topping_ids', [])) ? 'checked' : '' }}>{{ $topping->name }}</label>
                                @endforeach
                            </div>
                        </div>

                    </div>
                    <div class="flex items-center mt-2 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
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
                    <i class="fa-solid fa-floppy-disk text-[16px]"></i>
                    Lưu sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const maxImageBytes = 2 * 1024 * 1024;

    // Hiện thông báo lỗi trên form sản phẩm
    function showError(message) {
        if (window.Swal) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Thông báo', 
                text: message, 
                confirmButtonText: 'Đóng' 
            });
        } else {
            window.alert(message);
        }
    }

    // Sinh một dòng size mới khi bấm nút thêm
    function createSizeRow() {
        const row = document.createElement('div');
        row.className = 'product-size-row grid grid-cols-[1fr_1fr_40px] gap-2';
        row.innerHTML = 
            '<input name="size_names[]" maxlength="50" placeholder="Tên size" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<input name="size_price_adjustments[]" type="number" min="0" max="50000000" step="1000" value="0" placeholder="Giá cộng thêm" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<button type="button" class="js-remove-size w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa kích thước">' +
                '<i class="fa-solid fa-trash-can text-sm"></i>' +
            '</button>';
        return row;
    }

    // Khởi tạo khu vực quản lý size với nút thêm và xóa dòng
    function initSizes() {
        const container = document.getElementById('product-sizes');
        const addButton = document.getElementById('add-product-size');
        if (!container || !addButton) return;

        addButton.addEventListener('click', function () {
            if (container.querySelectorAll('.product-size-row').length < 10) {
                container.appendChild(createSizeRow());
            }
        });

        container.addEventListener('click', function (event) {
            const button = event.target.closest('.js-remove-size');
            if (!button) return;
            const rows = container.querySelectorAll('.product-size-row');
            
            if (rows.length === 1) {
                rows[0].querySelectorAll('input').forEach((input) => {
                    input.value = input.type === 'number' ? '0' : '';
                });
            } else {
                button.closest('.product-size-row').remove();
            }
        });
    }

    // Xử lý chọn và xem trước ảnh chính cùng album ảnh phụ
    function initImages() {
        const trigger = document.querySelector('.js-image-upload-trigger');
        const input = document.getElementById('image-upload');
        const gallery = document.getElementById('gallery-input');

        if (trigger && input) {
            trigger.addEventListener('click', (event) => { 
                if (event.target !== input) input.click(); 
            });

            input.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                if (file.size > maxImageBytes) { 
                    showError('Ảnh chính không được vượt quá 2MB.'); 
                    this.value = ''; 
                    return; 
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = document.getElementById('image-preview');
                    preview.src = event.target.result;
                    preview.classList.remove('hidden');
                    document.getElementById('image-placeholder')?.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            });
        }

        if (gallery) {
            gallery.addEventListener('change', function () {
                const files = Array.from(this.files || []);

                if (document.querySelectorAll('.gallery-item').length + files.length > 5 || 
                    files.some((file) => file.size > maxImageBytes)) {
                    showError('Tối đa 5 ảnh phụ và mỗi ảnh không được vượt quá 2MB.');
                    this.value = '';
                    return;
                }

                const container = document.getElementById('gallery-preview-container');
                container.innerHTML = '';
                files.forEach((file) => {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const image = document.createElement('img');
                        image.src = event.target.result;
                        image.className = 'w-16 h-16 object-cover rounded-lg border border-gray-200';
                        container.appendChild(image);
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
    }

    // Định dạng ô giá bán theo kiểu tiền Việt Nam trong lúc gõ
    function initPrice() {
        const display = document.getElementById('display_price');
        const raw = document.getElementById('raw_price');
        if (!display || !raw) return;

        display.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');

            if (Number(value) > 50000000) { 
                value = '50000000'; 
                showError('Giá bán không được vượt quá 50.000.000 VNĐ.'); 
            }

            raw.value = value;
            this.value = value ? new Intl.NumberFormat('vi-VN').format(value) : '';
        });
    }

    // Khởi tạo ô chọn danh mục sản phẩm
    function initCategorySelect() {
        const categorySelects = document.querySelectorAll('.product-category-select');
        if (categorySelects.length > 0 && typeof Choices !== 'undefined') {
            categorySelects.forEach(select => {
                if (!select.dataset.choicesInitialized) {
                    new Choices(select, {
                        searchEnabled: true,
                        shouldSort: false,
                        itemSelectText: '',
                        searchPlaceholderValue: 'Tìm danh mục...',
                        noResultsText: 'Không tìm thấy danh mục',
                        noChoicesText: 'Không còn lựa chọn',
                    });
                    select.dataset.choicesInitialized = 'true';
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSizes();
        initImages();
        initPrice();
        initCategorySelect();
    });
})();
</script>
@endpush

