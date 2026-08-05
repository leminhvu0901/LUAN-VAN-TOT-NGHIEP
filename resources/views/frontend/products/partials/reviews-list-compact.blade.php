{{-- Danh sách đánh giá kiểu "compact" — dùng cho products/review.blade.php. Nút "Xem thêm" là link
     phân trang GET thật (paginator tự giữ nguyên các tham số lọc rating/has_image nhờ withQueryString()). --}}
@php $isFiltered = $isFiltered ?? false; @endphp
<div class="review-items-fragment space-y-6">
    @forelse($reviews as $review)
        @include('frontend.products.partials.review-item-compact', ['review' => $review])
    @empty
        <div class="text-center py-10">
            @if($isFiltered)
                <p class="text-gray-500">Chưa có đánh giá nào phù hợp với bộ lọc đã chọn.</p>
            @else
                <p class="text-gray-500">Chưa có đánh giá nào cho sản phẩm này.</p>
            @endif
        </div>
    @endforelse
</div>
<div class="review-loadmore-fragment mt-8 text-center">
    @if($reviews->hasMorePages())
        <a href="{{ $reviews->nextPageUrl() }}#reviews-list" class="review-loadmore-btn text-[#00a82d] font-bold text-sm hover:underline">Xem thêm đánh giá</a>
    @endif
</div>
