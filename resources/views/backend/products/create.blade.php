@extends('backend.layouts.app')

@section('title', 'Thêm Sản phẩm Mới')



@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('admin.products.index') }}" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
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
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Image -->
                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hình ảnh sản phẩm</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 hover:border-emerald-500 transition-all relative overflow-hidden group h-64" onclick="document.getElementById('image-upload').click()">
                        <input type="file" id="image-upload" name="image" class="hidden" accept="image/*" onchange="previewImage(event)">
                        
                        <div id="image-placeholder" class="flex flex-col items-center">
                            <span class="material-symbols-outlined text-5xl text-gray-400 mb-3 group-hover:text-emerald-500 transition-colors">cloud_upload</span>
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
                            <select name="category_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white">
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
                        <input type="file" id="gallery-input" name="gallery[]" multiple accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" onchange="previewGallery(event)">
                        <p class="text-xs text-gray-500 mt-1">Tối đa 5 ảnh phụ.</p>
                        <div id="gallery-preview-container" class="flex flex-wrap gap-2 mt-3"></div>
                    </div>
                    
                    <div class="flex items-center mt-2 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            <span class="text-sm font-semibold text-gray-700">Đang kinh doanh (Hiển thị)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.products.index') }}" class="px-6 py-2.5 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</a>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    Lưu sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/products/create.js') }}"></script>
@endpush
