@extends('frontend.layouts.app')

@section('body_class', 'has-mobile-bottom-nav')

@section('content')
<div class="min-h-screen bg-gray-50/50 py-8 px-4 pb-24 font-body-md text-on-surface">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row gap-6">

        <!-- LEFT COLUMN: Product & Review Form -->
        <div class="w-full md:w-[340px] flex-shrink-0 flex flex-col gap-6">

            <!-- Product Info Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-4 shadow-sm">
                <div class="w-full aspect-square rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden mb-4 border border-outline-variant/30">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                <h1 class="font-headline-sm text-lg font-bold text-gray-900 mb-2">{{ $product->name }}</h1>
                <div class="flex items-center gap-2">
                    <div class="flex text-yellow-400 text-sm">
                        @php $avgR = round($product->avg_rating * 2) / 2; @endphp
                        @for($i=1; $i<=5; $i++)
                            @if($i <= floor($avgR))
                                <span class="material-symbols-outlined text-base material-filled">star</span>
                            @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                                <span class="material-symbols-outlined text-base material-filled">star_half</span>
                            @else
                                <span class="material-symbols-outlined text-base text-gray-300 material-filled">star</span>
                            @endif
                        @endfor
                    </div>
                    <span class="font-bold text-sm">{{ number_format($product->avg_rating, 1) }}/5</span>
                    <span class="text-xs text-gray-500">({{ $product->review_count }} reviews)</span>
                </div>
            </div>

            <!-- Review Form Card -->
            <div class="bg-white rounded-2xl border border-outline-variant/60 p-5 shadow-sm">
                @if($existingReview)
                    {{-- Đã đánh giá rồi -> mặc định chỉ xem lại nội dung đã gửi; nếu còn trong hạn 7
                    ngày (canEditReview) thì có thêm nút "Chỉnh sửa đánh giá" chuyển sang form sửa. --}}
                    <div id="review-view-mode">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-primary">check_circle</span>
                            <h2 class="font-bold text-gray-900 text-base">Đánh giá của bạn</h2>
                        </div>

                        <div class="flex items-center gap-1 mb-3">
                            @for($i=1; $i<=5; $i++)
                                <span class="material-symbols-outlined text-2xl {{ $i <= $existingReview->rating ? 'text-yellow-400 material-filled' : 'text-gray-300' }}">star</span>
                            @endfor
                        </div>

                        @if($existingReview->comment)
                            <p class="text-sm text-gray-700 leading-relaxed mb-3">{{ $existingReview->comment }}</p>
                        @endif

                        @if($existingReview->image)
                            @php
                                $existingImages = [];
                                $decodedExisting = json_decode($existingReview->image, true);
                                if (is_array($decodedExisting)) { $existingImages = $decodedExisting; }
                                else { $existingImages = [$existingReview->image]; }
                            @endphp
                            <div class="flex flex-wrap gap-2 mb-3">
                                @foreach($existingImages as $img)
                                    <img src="{{ asset('images/' . $img) }}" class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')">
                                @endforeach
                            </div>
                        @endif

                        <p class="text-[11px] text-gray-400">Đã gửi lúc {{ \Carbon\Carbon::parse($existingReview->created_at)->translatedFormat('d \T\h\á\n\g m, Y') }}</p>
                        @if($existingReview->edited_at)
                            <p class="text-[11px] text-gray-400">Đã chỉnh sửa lúc {{ \Carbon\Carbon::parse($existingReview->edited_at)->translatedFormat('d \T\h\á\n\g m, Y') }}</p>
                        @endif

                        @if($canEditReview)
                            <button type="button" onclick="toggleReviewEditMode(true)" class="mt-4 flex items-center justify-center gap-2 w-full border border-primary text-primary font-bold py-3 rounded-xl hover:bg-primary/5 transition-all">
                                <span class="material-symbols-outlined text-lg">edit</span> Chỉnh sửa đánh giá
                            </button>
                        @elseif($existingReview->edited_at)
                            <p class="mt-4 text-xs text-gray-400 italic">Bạn chỉ được chỉnh sửa đánh giá này 1 lần và đã sử dụng lượt sửa đó rồi.</p>
                        @else
                            <p class="mt-4 text-xs text-gray-400 italic">Đã quá {{ $editWindowDays }} ngày kể từ lúc đánh giá, bạn không thể chỉnh sửa nữa.</p>
                        @endif

                        <a href="{{ route('orders', ['status' => 'completed']) }}" class="mt-3 block text-center w-full border border-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-50 transition-all">
                            Quay lại đơn hàng
                        </a>
                    </div>

                    @if($canEditReview)
                        <div id="review-edit-mode" class="hidden">
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
                                    <textarea name="comment" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none" placeholder="Bạn thấy hương vị thế nào? Chia sẻ cùng Happy nhé...">{{ $existingReview->comment }}</textarea>
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
                @endif
            </div>

        </div>

        <!-- RIGHT COLUMN: Customer Reviews -->
        <div class="flex-1 bg-white rounded-2xl border border-outline-variant/60 p-6 shadow-sm">
            <h2 class="font-headline-md text-2xl font-bold text-gray-900 mb-6">Đánh giá từ khách hàng</h2>

            {{-- Bọc trong .reviews-app để reviews-filter.js nhận diện — nút lọc + khung danh sách/nút
            "Xem thêm" dùng chung 1 bộ class với trang chi tiết sản phẩm (xem
            public/js/frontend/products/reviews-filter.js). --}}
            <div class="reviews-app" data-product-id="{{ $product->id }}" data-view="compact">
                <!-- Filters -->
                <div class="flex flex-wrap gap-2 mb-8">
                    <button type="button" class="review-filter-btn is-active px-4 py-1.5 bg-[#00a82d] text-white text-sm font-bold rounded-full border border-[#00a82d]" data-rating="" data-has-image="">Tất cả</button>
                    @for($star = 5; $star >= 1; $star--)
                        <button type="button" class="review-filter-btn px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200" data-rating="{{ $star }}" data-has-image="">{{ $star }} sao ({{ $ratingDistribution[$star] ?? 0 }})</button>
                    @endfor
                    <button type="button" class="review-filter-btn px-4 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors border border-gray-200" data-rating="" data-has-image="1">Có hình ảnh ({{ $hasImageCount }})</button>
                </div>

                <!-- Reviews List -->
                @include('frontend.products.partials.reviews-list-compact', ['reviews' => $reviews])
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="{{ asset('js/frontend/products/review.js') }}"></script>
<script src="{{ asset('js/frontend/products/reviews-filter.js') }}"></script>
@endpush

@include('frontend.components.bottom-nav')
@endsection
