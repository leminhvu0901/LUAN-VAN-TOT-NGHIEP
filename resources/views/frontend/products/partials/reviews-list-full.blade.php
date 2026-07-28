{{-- Danh sách đánh giá kiểu "full" + nút "Xem thêm đánh giá" — dùng chung cho lần tải trang đầu tiên
     (products/show.blade.php @include) VÀ mỗi lần fetch qua reviews-filter.js (lọc/xem thêm). --}}
@php $isFiltered = $isFiltered ?? false; @endphp
<div class="review-items-fragment">
    @forelse($reviews as $review)
        @include('frontend.products.partials.review-item-full', ['review' => $review])
    @empty
        <div class="pd-reviews-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
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
        <button type="button" class="review-loadmore-btn pd-review-loadmore-btn" data-next-page="{{ $reviews->currentPage() + 1 }}">Xem thêm đánh giá</button>
    @endif
</div>
