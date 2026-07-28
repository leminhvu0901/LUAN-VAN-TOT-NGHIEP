{{-- Danh sách đánh giá kiểu "compact" + nút "Xem thêm đánh giá" — dùng chung cho lần tải trang đầu
     tiên (products/review.blade.php @include) VÀ mỗi lần fetch qua reviews-filter.js (lọc/xem thêm). --}}
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
        <button type="button" class="review-loadmore-btn text-[#00a82d] font-bold text-sm hover:underline" data-next-page="{{ $reviews->currentPage() + 1 }}">Xem thêm đánh giá</button>
    @endif
</div>
