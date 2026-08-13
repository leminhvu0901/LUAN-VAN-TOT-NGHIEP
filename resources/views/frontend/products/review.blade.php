@extends('frontend.layouts.app')

@section('body_class', 'has-mobile-bottom-nav')

@section('content')
<div class="min-h-screen bg-gray-50/50 py-8 px-4 pb-24 font-body-md text-on-surface">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">

        <!-- Cột trái: Đánh giá sản phẩm -->
        <div class="w-full md:w-[340px] flex-shrink-0 flex flex-col gap-6">

            {{-- Product Info Card --}}
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-3 md:p-4 shadow-sm flex md:block items-center gap-3">
                <div class="w-20 h-20 md:w-full md:h-auto md:aspect-square flex-shrink-0 rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden md:mb-4 border border-outline-variant/30">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                <div class="min-w-0">
                    <h1 class="font-headline-sm text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ $product->name }}</h1>
                    <div class="flex items-center gap-1.5 md:gap-2 flex-wrap">
                        <div class="flex text-yellow-400 text-sm">
                            @php $avgR = round($product->avg_rating * 2) / 2; @endphp
                            @for($i=1; $i<=5; $i++)
                                @if($i <=floor($avgR))
                                <span class="material-symbols-outlined text-sm md:text-base material-filled">star</span>
                                @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                                <span class="material-symbols-outlined text-sm md:text-base material-filled">star_half</span>
                                @else
                                <span class="material-symbols-outlined text-sm md:text-base text-gray-300 material-filled">star</span>
                                @endif
                                @endfor
                        </div>
                        <span class="font-bold text-xs md:text-sm">{{ number_format($product->avg_rating, 1) }}/5</span>
                        <span class="text-xs text-gray-500">({{ $product->review_count }} đánh giá)</span>
                    </div>
                </div>
            </div>

            <!-- Review Form Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-4 md:p-5 shadow-sm">
                @if($existingReview)
                {{-- Đánh giá của khách hàng --}}
                <div id="review-view-mode" class="{{ $errors->hasAny(['rating', 'comment', 'images', 'images.*']) ? 'hidden' : '' }}">
                    {{-- Gộp tiêu đề + số sao lên cùng 1 hàng để tiết kiệm --}}
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">check_circle</span>
                            <h2 class="font-bold text-gray-900 text-sm md:text-base">Đánh giá của bạn</h2>
                        </div>
                        <div class="flex items-center">
                            @for($i=1; $i<=5; $i++)
                                <span class="material-symbols-outlined text-lg {{ $i <= $existingReview->rating ? 'text-yellow-400 material-filled' : 'text-gray-300' }}">star</span>
                                @endfor
                        </div>
                    </div>

                    @if($existingReview->comment)
                    {{-- Break-words: chuỗi dài không khoảng trắng mặc --}}
                    <p class="text-sm text-gray-700 leading-relaxed mb-2 break-words">{{ $existingReview->comment }}</p>
                    @endif

                    @if($existingReview->image)
                    @php
                    $existingImages = [];
                    $decodedExisting = json_decode($existingReview->image, true);
                    if (is_array($decodedExisting)) { $existingImages = $decodedExisting; }
                    else { $existingImages = [$existingReview->image]; }
                    @endphp
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($existingImages as $img)
                        <img src="{{ upload_url($img) }}" class="w-14 h-14 md:w-16 md:h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')">
                        @endforeach
                    </div>
                    @endif

                    {{-- Gộp "đã gửi lúc" + "đã chỉnh sửa lúc" vào 1 dòng, --}}
                    <p class="text-[11px] text-gray-400">
                        Gửi {{ \Carbon\Carbon::parse($existingReview->created_at)->translatedFormat('d/m/Y') }}
                        @if($existingReview->edited_at)
                        · Sửa {{ \Carbon\Carbon::parse($existingReview->edited_at)->translatedFormat('d/m/Y') }}
                        @endif
                    </p>

                    @if(!$canEditReview)
                    <p class="mt-2 text-[11px] text-gray-400 italic">
                        @if($existingReview->edited_at)
                        Bạn chỉ được chỉnh sửa đánh giá này 1 lần và đã sử dụng lượt sửa đó rồi.
                        @else
                        Đã quá {{ $editWindowDays }} ngày kể từ lúc đánh giá, bạn không thể chỉnh sửa nữa.
                        @endif
                    </p>
                    @endif

                    {{-- Nút hành động --}}
                    <div class="flex gap-2 mt-3">
                        <a href="{{ route('orders', ['status' => 'completed']) }}" class="flex-1 text-center border border-gray-200 text-gray-700 font-bold text-sm py-2.5 rounded-xl hover:bg-gray-50 transition-all">
                            Quay lại
                        </a>
                        @if($canEditReview)
                        <button type="button" onclick="toggleReviewEditMode(true)" class="flex-1 flex items-center justify-center gap-1.5 border border-primary text-primary font-bold text-sm py-2.5 rounded-xl hover:bg-primary/5 transition-all">
                            <span class="material-symbols-outlined text-base">edit</span> Chỉnh sửa
                        </button>
                        @endif
                    </div>
                </div>

                @if($canEditReview)
                
                <div id="review-edit-mode" class="{{ $errors->hasAny(['rating', 'comment', 'images', 'images.*']) ? '' : 'hidden' }}">
                    <h2 class="font-bold text-gray-900 text-base mb-4">Chỉnh sửa đánh giá</h2>

                    <form action="{{ route('review.update', ['orderId' => $order->id, 'productId' => $product->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Đánh giá sản phẩm:</label>
                            <div class="flex items-center gap-1 cursor-pointer" id="star-rating-container">
                                @for($i=1; $i<=5; $i++)
                                    <span class="material-symbols-outlined text-3xl {{ $i <= $existingReview->rating ? 'text-yellow-400' : 'text-gray-300' }} hover:text-yellow-400 transition-colors material-filled" data-value="{{ $i }}">star</span>
                                    @endfor
                            </div>
                            <input type="hidden" name="rating" id="rating-input" value="{{ $existingReview->rating }}" required>
                            @error('rating')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Cảm nhận của bạn:</label>
                            <textarea name="comment" id="comment-edit" rows="3" maxlength="150" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none" placeholder="Bạn thấy hương vị thế nào? Chia sẻ cùng Happy nhé...">{{ $existingReview->comment }}</textarea>
                            {{-- Đếm ký tự trực tiếp khi gõ --}}
                            <p class="text-[11px] text-gray-400 mt-1 text-right" id="comment-edit-counter">0/150</p>
                            @error('comment')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="image-upload" class="flex items-center justify-center gap-2 w-full border-2 border-dashed border-gray-300 rounded-xl py-3 cursor-pointer hover:bg-gray-50 hover:border-primary transition-colors text-sm font-medium text-gray-600">
                                <span class="material-symbols-outlined text-xl">add_a_photo</span> Chọn hình ảnh mới (Tối đa 5 ảnh)
                            </label>
                            <input type="file" name="images[]" id="image-upload" class="hidden" accept="image/*" multiple onchange="previewImages(this)">
                            <p class="text-[11px] text-gray-400 mt-1.5">Chọn ảnh mới sẽ thay thế toàn bộ ảnh cũ. Không chọn thì giữ nguyên ảnh hiện có.</p>
                            <div id="image-preview-container" class="mt-3 flex flex-wrap gap-2"></div>
                            @error('images')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('images.*')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="button" onclick="toggleReviewEditMode(false)" class="flex-1 border border-gray-200 text-gray-700 font-bold py-3.5 rounded-xl hover:bg-gray-50 transition-all">
                                Hủy
                            </button>
                            <button type="submit" class="flex-1 bg-[#00a82d] hover:bg-[#009226] text-white font-bold py-3.5 rounded-xl shadow-sm transition-all active:scale-[0.98]">
                                Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
                @endif
                @else
                <h2 class="font-bold text-gray-900 text-base mb-4">Viết đánh giá của bạn</h2>

                <form action="{{ route('review.store', ['orderId' => $order->id, 'productId' => $product->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Đánh giá sản phẩm:</label>
                        <div class="flex items-center gap-1 cursor-pointer" id="star-rating-container">
                            @for($i=1; $i<=5; $i++)
                                <span class="material-symbols-outlined text-3xl text-gray-300 hover:text-yellow-400 transition-colors material-filled" data-value="{{ $i }}">star</span>
                                @endfor
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                        @error('rating')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Cảm nhận của bạn:</label>
                        <textarea name="comment" id="comment-new" rows="3" maxlength="150" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none" placeholder="Bạn thấy hương vị thế nào? Chia sẻ cùng Happy nhé..."></textarea>
                        {{-- Đếm ký tự trực tiếp khi gõ --}}
                        <p class="text-[11px] text-gray-400 mt-1 text-right" id="comment-new-counter">0/150</p>
                        @error('comment')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="image-upload" class="flex items-center justify-center gap-2 w-full border-2 border-dashed border-gray-300 rounded-xl py-3 cursor-pointer hover:bg-gray-50 hover:border-primary transition-colors text-sm font-medium text-gray-600">
                            <span class="material-symbols-outlined text-xl">add_a_photo</span> Chọn hình ảnh (Tối đa 5 ảnh)
                        </label>
                        <input type="file" name="images[]" id="image-upload" class="hidden" accept="image/*" multiple onchange="previewImages(this)">
                        <div id="image-preview-container" class="mt-3 flex flex-wrap gap-2"></div>
                        @error('images')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @error('images.*')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-[#00a82d] hover:bg-[#009226] text-white font-bold py-3.5 rounded-xl shadow-sm transition-all active:scale-[0.98]">
                        Gửi đánh giá
                    </button>
                </form>
                @endif
            </div>

        </div>

        <!-- Cột phải: Danh sách đánh giá -->
        <div class="flex-1 bg-white rounded-2xl border border-outline-variant/60 p-4 md:p-6 shadow-sm">
            <h2 class="font-headline-md text-lg md:text-2xl font-bold text-gray-900 mb-3 md:mb-6">Đánh giá từ khách hàng</h2>

            {{-- Nút lọc giờ là link get thật --}}
            @php
                $activeFilterClass = 'flex-shrink-0 whitespace-nowrap px-3.5 py-1.5 bg-[#00a82d] text-white text-xs md:text-sm font-bold rounded-full border border-[#00a82d]';
                $inactiveFilterClass = 'flex-shrink-0 whitespace-nowrap px-3.5 py-1.5 bg-gray-100 text-gray-700 text-xs md:text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200';
                $reviewFilterBaseUrl = route('review.create', ['orderId' => $order->id, 'productId' => $product->id]);
            @endphp
            <div class="reviews-app">
                {{-- Filters: cuộn ngang 1 hàng thay vì xuống dòng thành 3 hàng --}}
                <div class="flex gap-2 overflow-x-auto hide-scrollbar pb-1 md:flex-wrap md:overflow-visible" id="review-filters-track">
                    <a href="{{ $reviewFilterBaseUrl }}#reviews-list" class="{{ !request('rating') && !request('has_image') ? $activeFilterClass : $inactiveFilterClass }}">Tất cả</a>
                    @for($star = 5; $star >= 1; $star--)
                    <a href="{{ $reviewFilterBaseUrl }}?rating={{ $star }}#reviews-list" class="{{ request('rating') == $star ? $activeFilterClass : $inactiveFilterClass }}">{{ $star }} sao ({{ $ratingDistribution[$star] ?? 0 }})</a>
                    @endfor
                    <a href="{{ $reviewFilterBaseUrl }}?has_image=1#reviews-list" class="{{ request('has_image') ? $activeFilterClass : $inactiveFilterClass }}">Có hình ảnh ({{ $hasImageCount }})</a>
                </div>
                {{-- Thanh chỉ báo vị trí cuộn ngang --}}
                <div class="review-filters-scrollbar mb-4 md:mb-8" id="review-filters-scrollbar" aria-hidden="true">
                    <div class="review-filters-scrollbar__thumb" id="review-filters-scrollbar-thumb"></div>
                </div>

                <!-- Reviews List -->
                <div id="reviews-list">
                    @include('frontend.products.partials.reviews-list-compact', ['reviews' => $reviews, 'isFiltered' => $isFiltered])
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('#star-rating-container span');
    const ratingInput = document.getElementById('rating-input');

    let currentRating = (ratingInput && ratingInput.value) ? parseInt(ratingInput.value, 10) : 0;

    stars.forEach(star => {
        star.addEventListener('mouseover', function() { 
            highlightStars(this.getAttribute('data-value')); 
        });

        star.addEventListener('mouseout', function() { 
            highlightStars(currentRating); 
        });

        star.addEventListener('click', function() {
            currentRating = this.getAttribute('data-value');
            ratingInput.value = currentRating;
            highlightStars(currentRating);
            stars.forEach(s => s.classList.replace('text-gray-300', 'text-yellow-400'));
        });
    });

    // Tô sáng số sao khi khách rê chuột hoặc bấm chọn
    function highlightStars(val) {
        stars.forEach(star => {
            if (star.getAttribute('data-value') <= val) {
                star.style.fontVariationSettings = "'FILL' 1";
                if(currentRating == 0) {
                    star.classList.replace('text-gray-300', 'text-yellow-400');
                }
            } else {
                star.style.fontVariationSettings = "'FILL' 0";
                if(currentRating == 0) {
                    star.classList.replace('text-yellow-400', 'text-gray-300');
                }
            }
        });
    }

    // Chuyển giữa chế độ xem và chế độ sửa đánh giá đã gửi
    function toggleReviewEditMode(showEdit) {
        const viewMode = document.getElementById('review-view-mode');
        const editMode = document.getElementById('review-edit-mode');
        if (!viewMode || !editMode) return;
        viewMode.classList.toggle('hidden', showEdit);
        editMode.classList.toggle('hidden', !showEdit);
    }
    window.toggleReviewEditMode = toggleReviewEditMode;

    // Xem trước các ảnh vừa chọn để đính kèm đánh giá
    function previewImages(input) {
        const previewContainer = document.getElementById('image-preview-container');
        previewContainer.innerHTML = '';

        if (input.files && input.files.length > 0) {
            if (input.files.length > 5) {
                const errorMsg = 'Chỉ được phép chọn tối đa 5 hình ảnh.';
                if (window.FrontendAlert) window.FrontendAlert.error(errorMsg); else alert(errorMsg);
                input.value = '';
                return;
            }

            let hasLargeFile = false;
            Array.from(input.files).forEach(file => { 
                if (file.size > 2 * 1024 * 1024) hasLargeFile = true; 
            });
            if (hasLargeFile) {
                const errorSizeMsg = 'Dung lượng mỗi hình ảnh không được vượt quá 2MB.';
                if (window.FrontendAlert) window.FrontendAlert.error(errorSizeMsg); else alert(errorSizeMsg);
                input.value = '';
                return;
            }

            Array.from(input.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200';
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });

            const clearDiv = document.createElement('div');
            clearDiv.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 border-dashed flex items-center justify-center cursor-pointer hover:bg-red-50 text-error transition-colors';
            clearDiv.onclick = () => {
                input.value = '';
                previewContainer.innerHTML = '';
            };
            clearDiv.innerHTML = '<span class="material-symbols-outlined text-2xl">delete</span>';
            previewContainer.appendChild(clearDiv);
        }
    }
    window.previewImages = previewImages;
});

(function () {
    const track = document.getElementById('review-filters-track');
    const scrollbar = document.getElementById('review-filters-scrollbar');
    const thumb = document.getElementById('review-filters-scrollbar-thumb');
    if (!track || !scrollbar || !thumb) return;

    // Cập nhật chỉ báo cuộn cho dải ảnh đính kèm
    function updateScrollIndicator() {
        const scrollWidth = track.scrollWidth;
        const clientWidth = track.clientWidth;
        const scrollLeft = track.scrollLeft;

        if (scrollWidth <= clientWidth) {
            scrollbar.style.display = 'none';
            return;
        }

        scrollbar.style.display = 'block';

        const thumbWidthRatio = clientWidth / scrollWidth;
        const thumbWidth = Math.max(20, clientWidth * thumbWidthRatio);
        thumb.style.width = thumbWidth + 'px';

        const maxScrollLeft = scrollWidth - clientWidth;
        const maxThumbLeft = clientWidth - thumbWidth;
        const thumbLeft = (scrollLeft / maxScrollLeft) * maxThumbLeft;
        thumb.style.transform = `translateX(${thumbLeft}px)`;
    }

    track.addEventListener('scroll', updateScrollIndicator);
    window.addEventListener('resize', updateScrollIndicator);
    updateScrollIndicator();
})();
</script>
@endpush

@include('frontend.components.bottom-nav')
@endsection