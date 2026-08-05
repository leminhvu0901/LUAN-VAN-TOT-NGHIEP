@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Đánh giá')

@section('content')
<div class="reviews-page">
<div class="p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3 sm:gap-4 mb-2 sm:mb-4">
        <a href="{{ route('admin.reviews.index') }}"
            onclick="smartGoBack(event)"
            class="p-1.5 sm:p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors shrink-0">
            <span class="material-symbols-outlined text-[18px] sm:text-[20px]">arrow_back</span>
        </a>
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">Chi tiết đánh giá #{{ $review->id }}</h2>
            <p class="text-gray-500 text-xs sm:text-sm mt-0.5 sm:mt-1 truncate">Khách hàng: <span class="font-bold text-amber-600">{{ $review->user->name }}</span></p>
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

    <form action="{{ route('admin.reviews.update', $review->id) }}" method="POST" id="review-form" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Cột trái: Thông tin cơ bản -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Card: Thông tin chính -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600 text-[20px] icon-fill">reviews</span>
                        Nội dung đánh giá
                    </h3>
                    <div class="space-y-5">
                        
                        <!-- Sản phẩm -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-gray-400">inventory_2</span>
                                Sản phẩm đánh giá
                            </label>
                            <input type="text" disabled value="{{ $review->product->name }}"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50/50 text-gray-500 text-sm cursor-not-allowed">
                        </div>

                        <!-- Số sao -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                                <span class="material-symbols-outlined text-[18px] text-amber-500 icon-fill">star</span>
                                Số sao đánh giá <span class="text-red-500">*</span>
                            </label>
                            <select name="rating" class="custom-select-init w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all text-sm appearance-none bg-gray-50 focus:bg-white hover:bg-gray-100 cursor-pointer" data-width-class="w-full">
                                <option value="5" {{ old('rating', $review->rating) == '5' ? 'selected' : '' }}>5 Sao (Rất Tốt)</option>
                                <option value="4" {{ old('rating', $review->rating) == '4' ? 'selected' : '' }}>4 Sao (Tốt)</option>
                                <option value="3" {{ old('rating', $review->rating) == '3' ? 'selected' : '' }}>3 Sao (Bình thường)</option>
                                <option value="2" {{ old('rating', $review->rating) == '2' ? 'selected' : '' }}>2 Sao (Kém)</option>
                                <option value="1" {{ old('rating', $review->rating) == '1' ? 'selected' : '' }}>1 Sao (Rất Kém)</option>
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
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all text-sm bg-gray-50 focus:bg-white">{{ old('comment', $review->comment) }}</textarea>
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
                                Quản lý hình ảnh đính kèm
                            </label>
                            
                            @php 
                                $images = $review->image ? json_decode($review->image, true) : []; 
                                if (!is_array($images)) $images = [];
                            @endphp
                            
                            @if(count($images) > 0)
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100 mb-4">
                                    <div class="flex flex-wrap gap-4" id="existing-images-container">
                                        @foreach($images as $index => $img)
                                            <div class="group relative w-24 h-24 rounded-xl overflow-hidden bg-white border border-gray-200 shadow-sm transition-all" id="review-image-{{ $index }}">
                                                <!-- Link xem ảnh gốc -->
                                                <a href="{{ upload_url($img) }}" target="_blank" class="block w-full h-full">
                                                    <img src="{{ upload_url($img) }}" class="w-full h-full object-cover group-hover:opacity-80 transition-opacity">
                                                </a>
                                                
                                                <!-- Nút X góc phải trên (Xóa bằng AJAX) -->
                                                <button type="button" data-image="{{ $img }}" data-id="{{ $review->id }}" data-index="{{ $index }}" class="js-delete-review-image absolute top-1 right-1 w-6 h-6 rounded-full bg-white/90 backdrop-blur text-gray-500 flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white" title="Xóa ảnh này ngay lập tức">
                                                    <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Upload ảnh mới -->
                            <div>
                                <p class="text-xs text-gray-500 mb-2">Thêm hình ảnh mới (Chọn nhiều ảnh cùng lúc, tối đa 2MB/ảnh):</p>
                                <input type="file" id="new-images-input" name="new_images[]" multiple accept="image/*"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 transition-all border border-gray-200 rounded-xl p-2 bg-gray-50">
                                
                                <!-- Khu vực hiển thị trước (preview) các ảnh mới được chọn -->
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
                        <span class="material-symbols-outlined text-amber-500 text-[20px] icon-fill">toggle_on</span>
                        Trạng thái
                    </h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="is_visible" value="1"
                                id="toggle-active"
                                {{ old('is_visible', $review->is_visible) ? 'checked' : '' }}
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
                        class="w-full sm:flex-1 px-6 py-3 bg-amber-600 text-white font-semibold rounded-xl hover:bg-amber-700 organic-shadow transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Lưu
                    </button>
                    <a href="{{ route('admin.reviews.index') }}"
                        onclick="smartGoBack(event)"
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    let accumulatedFiles = new DataTransfer();
    const newImagesInput = document.getElementById('new-images-input');
    const previewContainer = document.getElementById('new-images-preview');

    if (newImagesInput) {
        newImagesInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            const existingImagesCount = document.querySelectorAll('[id^="review-image-"]').length;
            let currentTotal = existingImagesCount + accumulatedFiles.files.length;
            let excessCount = 0;

            Array.from(files).forEach(file => {
                const isDuplicate = Array.from(accumulatedFiles.files).some(f => f.name === file.name && f.size === file.size);
                if (!isDuplicate && file.type.startsWith('image/')) {
                    if (currentTotal < 5) {
                        accumulatedFiles.items.add(file);
                        currentTotal++;
                    } else {
                        excessCount++;
                    }
                }
            });

            if (excessCount > 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Đạt giới hạn!',
                        text: `Tối đa 5 ảnh (gồm ảnh cũ). Đã bỏ qua ${excessCount} ảnh.`,
                        width: '320px',
                        padding: '1rem',
                        confirmButtonText: 'Đóng',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            htmlContainer: 'text-sm text-gray-600 mt-1',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                } else {
                    alert(`Tối đa chỉ được 5 ảnh (gồm cả ảnh cũ). Đã tự động bỏ qua ${excessCount} ảnh vượt mức.`);
                }
            }

            this.files = accumulatedFiles.files;
            previewContainer.innerHTML = ''; 

            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgWrap = document.createElement('div');
                    imgWrap.className = 'relative w-24 h-24 rounded-xl overflow-hidden border-2 border-amber-300 shadow-sm opacity-0 transform scale-95 transition-all duration-300 group';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover';
                    
                    const badge = document.createElement('span');
                    badge.className = 'absolute top-0 right-0 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-bl-lg shadow-sm pointer-events-none';
                    badge.textContent = 'MỚI';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'absolute top-1 left-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600';
                    removeBtn.innerHTML = '<span class="material-symbols-outlined text-[14px] font-bold">close</span>';
                    removeBtn.title = 'Bỏ chọn ảnh này';
                    removeBtn.onclick = function() {
                        const dt = new DataTransfer();
                        Array.from(accumulatedFiles.files).forEach((f, i) => {
                            if (i !== index) dt.items.add(f);
                        });
                        accumulatedFiles = dt;
                        newImagesInput.files = accumulatedFiles.files;
                        imgWrap.remove();
                    };

                    imgWrap.appendChild(img);
                    imgWrap.appendChild(badge);
                    imgWrap.appendChild(removeBtn);
                    previewContainer.appendChild(imgWrap);

                    setTimeout(() => {
                        imgWrap.classList.remove('opacity-0', 'scale-95');
                    }, 50);
                }
                reader.readAsDataURL(file);
            });
        });
    }

    const existingImagesContainer = document.getElementById('existing-images-container');
    if (existingImagesContainer) {
        existingImagesContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-delete-review-image');
            if (!btn) return;

            if (!confirm('Ảnh này sẽ bị xóa vĩnh viễn. Tiếp tục?')) return;

            const imageName = btn.dataset.image;
            const reviewId = btn.dataset.id;
            const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/reviews/' + reviewId + '/image';
            form.style.display = 'none';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const imageInput = document.createElement('input');
            imageInput.type = 'hidden';
            imageInput.name = 'image';
            imageInput.value = imageName;
            form.appendChild(imageInput);

            document.body.appendChild(form);
            form.submit();
        });
    }

    const commentTextarea = document.getElementById('review-comment');
    const commentCounter = document.getElementById('comment-counter');
    const commentError = document.getElementById('comment-error');

    if (commentTextarea && commentCounter) {
        const updateCounter = () => {
            const len = commentTextarea.value.length;
            commentCounter.textContent = `${len}/200`;
            
            if (len >= 200) {
                commentCounter.classList.remove('text-gray-400');
                commentCounter.classList.add('text-red-500');
                if(commentError) commentError.classList.remove('hidden');
            } else {
                commentCounter.classList.remove('text-red-500');
                commentCounter.classList.add('text-gray-400');
                if(commentError) commentError.classList.add('hidden');
            }
        };
        
        updateCounter();
        commentTextarea.addEventListener('input', updateCounter);
        commentTextarea.addEventListener('paste', function(e) {
            setTimeout(updateCounter, 10);
        });
    }
});
</script>
@endpush

