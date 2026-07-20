@extends('backend.layouts.app')

@section('title', 'Thêm Đánh giá mới')

@section('content')
<div class="reviews-page">
<div class="p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3 sm:gap-4 mb-2 sm:mb-4">
        <a href="{{ route('admin.reviews.index') }}" class="p-1.5 sm:p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors shrink-0">
            <span class="material-symbols-outlined text-[18px] sm:text-[20px]">arrow_back</span>
        </a>
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">Thêm Đánh giá mới</h2>
            <p class="text-gray-500 text-xs sm:text-sm mt-0.5 sm:mt-1 truncate">Tính năng thêm trực tiếp từ trang quản trị</p>
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

    <form action="{{ route('admin.reviews.store') }}" method="POST" id="review-form" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Cột trái: Thông tin cơ bản -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Card: Thông tin chính -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-[20px] icon-fill">reviews</span>
                        Nội dung đánh giá
                    </h3>
                    <div class="space-y-5">
                        
                        <!-- Khách hàng -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">person</span>
                                Khách hàng <span class="text-red-500">*</span>
                            </label>
                            <select name="user_id" class="custom-select-init w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all text-sm bg-gray-50 focus:bg-white hover:bg-gray-100 cursor-pointer" data-width-class="w-full">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Sản phẩm -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">inventory_2</span>
                                Sản phẩm đánh giá <span class="text-red-500">*</span>
                            </label>
                            <select name="product_id" class="custom-select-init w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all text-sm bg-gray-50 focus:bg-white hover:bg-gray-100 cursor-pointer" data-width-class="w-full">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Số sao -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-amber-500 icon-fill">star</span>
                                Số sao đánh giá <span class="text-red-500">*</span>
                            </label>
                            <select name="rating" class="custom-select-init w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all text-sm appearance-none bg-gray-50 focus:bg-white hover:bg-gray-100 cursor-pointer" data-width-class="w-full">
                                <option value="5" {{ old('rating') == '5' ? 'selected' : '' }}>5 Sao (Rất Tốt)</option>
                                <option value="4" {{ old('rating') == '4' ? 'selected' : '' }}>4 Sao (Tốt)</option>
                                <option value="3" {{ old('rating') == '3' ? 'selected' : '' }}>3 Sao (Bình thường)</option>
                                <option value="2" {{ old('rating') == '2' ? 'selected' : '' }}>2 Sao (Kém)</option>
                                <option value="1" {{ old('rating') == '1' ? 'selected' : '' }}>1 Sao (Rất Kém)</option>
                            </select>
                        </div>

                        <!-- Nội dung -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">chat</span>
                                Nhận xét của khách
                            </label>
                            <textarea id="review-comment" name="comment" rows="4" maxlength="200"
                                placeholder="Viết nhận xét..."
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all text-sm bg-gray-50 focus:bg-white">{{ old('comment') }}</textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p id="comment-error" class="text-xs text-red-500 hidden animate-fade-in-up">Nội dung nhận xét tối đa 200 ký tự!</p>
                                <p id="comment-counter" class="text-xs text-gray-400 ml-auto">0/200</p>
                            </div>
                        </div>
                        
                        <hr class="border-gray-100">
                        
                        <!-- Quản lý Hình ảnh -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">image</span>
                                Hình ảnh đính kèm
                            </label>
                            
                            <div>
                                <p class="text-xs text-gray-500 mb-2">Chọn nhiều ảnh cùng lúc, tối đa 5 ảnh, 2MB/ảnh:</p>
                                <div class="relative group">
                                    <input type="file" name="new_images[]" id="new-images-input" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="w-full flex flex-col items-center justify-center gap-2 px-4 py-6 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 group-hover:bg-emerald-50 group-hover:border-emerald-300 transition-colors">
                                        <span class="material-symbols-outlined text-[32px] text-gray-400 group-hover:text-emerald-500 transition-colors">cloud_upload</span>
                                        <div class="text-center">
                                            <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Bấm để chọn ảnh hoặc Kéo thả vào đây</p>
                                            <p class="text-xs text-gray-500 mt-1">Hỗ trợ JPG, PNG, GIF</p>
                                        </div>
                                    </div>
                                </div>
                                <div id="new-images-preview" class="flex flex-wrap gap-3 mt-4 empty:hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Trạng thái & Lưu -->
            <div class="lg:col-span-1 space-y-5">
                
                <!-- Card: Trạng thái -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-500 text-[20px] icon-fill">toggle_on</span>
                        Trạng thái
                    </h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="is_visible" value="1"
                                id="toggle-active"
                                {{ old('is_visible', true) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Hiển thị công khai</p>
                            <p class="text-xs text-gray-400">Cho phép hiện trên website</p>
                        </div>
                    </label>
                </div>

                <!-- Nút Lưu -->
                <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-3 mt-4">
                    <button type="submit"
                        class="w-full sm:flex-1 px-6 py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">add_circle</span>
                        Thêm
                    </button>
                    <a href="{{ route('admin.reviews.index') }}"
                        class="w-full sm:flex-1 px-6 py-3 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors flex items-center justify-center gap-2 border border-gray-200">
                        <span class="material-symbols-outlined text-[20px]">cancel</span>
                        Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/admin/reviews/create.js') }}?v={{ time() }}"></script>
@endpush
