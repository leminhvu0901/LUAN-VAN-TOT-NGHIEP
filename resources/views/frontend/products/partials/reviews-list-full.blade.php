{{-- Đánh giá của khách hàng --}}
@php $isFiltered = $isFiltered ?? false; @endphp
<div class="review-items-fragment">
    @forelse($reviews as $review)
        @include('frontend.products.partials.review-item-full', ['review' => $review])
    @empty
        <div class="pd-reviews-empty">
            <i class="fa-regular fa-comment-dots text-gray-300 text-5xl mb-2"></i>
            @if($isFiltered)
                <p>Chưa có đánh giá nào phù hợp với bộ lọc đã chọn.</p>
            @else
                <p>Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
            @endif
        </div>
    @endforelse
</div>
<div class="review-loadmore-fragment pd-review-loadmore-wrap">
    @if($reviews->hasMorePages())
        <a href="{{ $reviews->nextPageUrl() }}#reviews-section" class="review-loadmore-btn pd-review-loadmore-btn">Xem thêm đánh giá</a>
    @endif
</div>
