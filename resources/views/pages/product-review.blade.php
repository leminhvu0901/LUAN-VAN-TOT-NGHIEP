@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50/50 py-8 px-4 font-body-md text-on-surface">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">
        
        <!-- LEFT COLUMN: Product & Review Form -->
        <div class="w-full md:w-[340px] flex-shrink-0 flex flex-col gap-6">
            
            <!-- Product Info Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-4 shadow-sm">
                <div class="w-full aspect-square rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden mb-4 border border-outline-variant/30">
                    <img src="{{ asset('images/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                <h1 class="font-headline-sm text-lg font-bold text-gray-900 mb-2">{{ $product->name }}</h1>
                <div class="flex items-center gap-2">
                    <div class="flex text-yellow-400 text-sm">
                        @php $avgR = round($product->avg_rating * 2) / 2; @endphp
                        @for($i=1; $i<=5; $i++)
                            @if($i <= floor($avgR))
                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">star</span>
                            @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">star_half</span>
                            @else
                                <span class="material-symbols-outlined text-base text-gray-300" style="font-variation-settings: 'FILL' 1;">star</span>
                            @endif
                        @endfor
                    </div>
                    <span class="font-bold text-sm">{{ number_format($product->avg_rating, 1) }}/5</span>
                    <span class="text-xs text-gray-500">({{ $product->review_count }} reviews)</span>
                </div>
            </div>

            <!-- Review Form Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-5 shadow-sm">
                <h2 class="font-bold text-gray-900 text-base mb-4">Viết đánh giá của bạn</h2>
                
                <form action="{{ route('review.store', ['orderId' => $order->id, 'productId' => $product->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Đánh giá sản phẩm:</label>
                        <div class="flex items-center gap-1 cursor-pointer" id="star-rating-container">
                            @for($i=1; $i<=5; $i++)
                                <span class="material-symbols-outlined text-3xl text-gray-300 hover:text-yellow-400 transition-colors" data-value="{{ $i }}" style="font-variation-settings: 'FILL' 1;">star</span>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                        @error('rating')
                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Cảm nhận của bạn:</label>
                        <textarea name="comment" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none" placeholder="Bạn thấy hương vị thế nào? Chia sẻ cùng Happy nhé..."></textarea>
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
            </div>
            
        </div>

        <!-- RIGHT COLUMN: Customer Reviews -->
        <div class="flex-1 bg-white rounded-2xl border border-outline-variant/60 p-6 shadow-sm">
            <h2 class="font-headline-md text-2xl font-bold text-gray-900 mb-6">Đánh giá từ khách hàng</h2>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-2 mb-8">
                <button class="px-4 py-1.5 bg-[#00a82d] text-white text-sm font-bold rounded-full border border-[#00a82d]">Tất cả</button>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200">5 sao ({{ $ratingDistribution[5] ?? 0 }})</button>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200">4 sao ({{ $ratingDistribution[4] ?? 0 }})</button>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200">Có hình ảnh</button>
            </div>

            <!-- Reviews List -->
            <div class="space-y-6">
                @forelse($reviews as $review)
                <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-800 flex items-center justify-center font-bold text-sm overflow-hidden">
                                @if($review->user_avatar)
                                    @if(\Illuminate\Support\Str::startsWith($review->user_avatar, 'http'))
                                        <img src="{{ $review->user_avatar }}" alt="{{ $review->user_name }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                                    @else
                                        <img src="{{ asset('images/avatars/' . $review->user_avatar) }}" alt="{{ $review->user_name }}" class="w-full h-full object-cover">
                                    @endif
                                @else
                                    {{ mb_substr($review->user_name, 0, 2) }}
                                @endif
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">{{ $review->user_name }}</h4>
                                <div class="flex text-yellow-400 text-xs mt-0.5">
                                    @for($i=1; $i<=5; $i++)
                                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? '1' : '0' }};">{{ $i <= $review->rating ? 'star' : 'star' }}</span>
                                    @endfor
                                </div>
                                <span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($review->created_at)->translatedFormat('d \T\h\á\n\g m, Y') }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] text-[#00a82d] bg-[#00a82d]/10 px-2.5 py-1 rounded-md font-bold uppercase">
                            <span class="material-symbols-outlined text-[12px]">check_circle</span> ĐÃ MUA HÀNG
                        </span>
                    </div>
                    
                    @if($review->comment)
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                    @endif

                    @if($review->image)
                        @php
                            $images = [];
                            $decoded = json_decode($review->image, true);
                            if (is_array($decoded)) {
                                $images = $decoded;
                            } else {
                                $images = [$review->image]; // Fallback for older single string images
                            }
                        @endphp
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($images as $img)
                                <img src="{{ asset('images/' . $img) }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')">
                            @endforeach
                        </div>
                    @endif
                </div>
                @empty
                <div class="text-center py-10">
                    <p class="text-gray-500">Chưa có đánh giá nào cho sản phẩm này.</p>
                </div>
                @endforelse
            </div>

            @if($reviews->count() > 0)
                <div class="mt-8 text-center">
                    <button class="text-[#00a82d] font-bold text-sm hover:underline">Xem thêm đánh giá</button>
                </div>
            @endif
        </div>
        
    </div>
</div>

@push('scripts')
<script>
    // Star Rating Logic
    const stars = document.querySelectorAll('#star-rating-container span');
    const ratingInput = document.getElementById('rating-input');
    let currentRating = 0;

    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const val = this.getAttribute('data-value');
            highlightStars(val);
        });

        star.addEventListener('mouseout', function() {
            highlightStars(currentRating);
        });

        star.addEventListener('click', function() {
            currentRating = this.getAttribute('data-value');
            ratingInput.value = currentRating;
            highlightStars(currentRating);
            // Change color to solid yellow
            stars.forEach(s => s.classList.replace('text-gray-300', 'text-yellow-400'));
        });
    });

    function highlightStars(val) {
        stars.forEach(star => {
            if (star.getAttribute('data-value') <= val) {
                star.style.fontVariationSettings = "'FILL' 1";
                if(currentRating == 0) star.classList.replace('text-gray-300', 'text-yellow-400');
            } else {
                star.style.fontVariationSettings = "'FILL' 0";
                if(currentRating == 0) star.classList.replace('text-yellow-400', 'text-gray-300');
            }
        });
    }

    // Image Preview Logic
    function previewImages(input) {
        const previewContainer = document.getElementById('image-preview-container');
        previewContainer.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            if (input.files.length > 5) {
                alert('Chỉ được phép chọn tối đa 5 hình ảnh.');
                input.value = '';
                return;
            }

            // Client-side file size validation (2MB limit)
            let hasLargeFile = false;
            Array.from(input.files).forEach(file => {
                if (file.size > 2 * 1024 * 1024) {
                    hasLargeFile = true;
                }
            });

            if (hasLargeFile) {
                alert('Dung lượng mỗi hình ảnh không được vượt quá 2MB. Vui lòng chọn ảnh nhẹ hơn.');
                input.value = '';
                return;
            }

            Array.from(input.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200';
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
            
            const clearDiv = document.createElement('div');
            clearDiv.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 border-dashed flex items-center justify-center cursor-pointer hover:bg-red-50 text-error transition-colors';
            clearDiv.onclick = removeImages;
            clearDiv.innerHTML = '<span class="material-symbols-outlined text-2xl">delete</span>';
            previewContainer.appendChild(clearDiv);
        }
    }

    function removeImages() {
        const input = document.getElementById('image-upload');
        const previewContainer = document.getElementById('image-preview-container');
        input.value = '';
        previewContainer.innerHTML = '';
    }
</script>
@endpush
@endsection
