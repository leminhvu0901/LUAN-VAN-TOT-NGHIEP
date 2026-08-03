{{-- Lưới sản phẩm + phân trang — tách riêng để tái sử dụng cho cả lần tải trang đầu tiên lẫn phản
hồi AJAX khi bấm chuyển trang (ProductController::index() trả về đúng partial này khi $request->ajax()). --}}
<div id="ajax-product-area">
    <!-- Grid chứa danh sách sản phẩm -->
    <div class="p-product-grid" id="product-grid">

        @forelse($products as $product)
        @php
        // Kiểm tra trạng thái HOT (Bán chạy) và NEW (Mới nhất) phục vụ gắn huy hiệu và bộ lọc JS
        $isHot = in_array($product->id, $top6HotProductIds);
        $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15);
            $isOos=!$product->is_active; // Hết hàng khi is_active = 0
            $discountInfo = $product->discount_info;
            $displayPrice = $discountInfo ? $discountInfo['sale_price'] : $product->base_price;
            @endphp
            {{-- Thẻ sản phẩm. Các thuộc tính data-* đóng vai trò truyền dữ liệu để JS lọc/sắp xếp nhanh tại client --}}
            <div class="p-product-card {{ $isOos ? 'p-product-card--out-of-stock' : '' }}"
                data-sold="{{ $product->total_sold }}"
                data-price-val="{{ $displayPrice }}"
                data-date="{{ strtotime($product->created_at) }}"
                data-rating-val="{{ $product->avg_rating }}"
                data-is-hot="{{ $isHot ? '1' : '0' }}"
                data-is-new="{{ $isNew ? '1' : '0' }}">

                {{-- Vùng ảnh sản phẩm, nhấp vào sẽ chuyển hướng sang trang chi tiết sản phẩm --}}
                <div class="p-product-img-wrap p-product-img-wrap-pointer"
                    onclick="window.location.href='{{ route('product.show', $product->slug) }}'"
                    data-id="{{ $product->id }}"
                    data-name="{{ $product->name }}"
                    data-price="{{ number_format($displayPrice, 0, ',', '.') }}đ"
                    data-category="{{ $product->category_name }}"
                    data-image="{{ $product->image_url }}"
                    data-slug="{{ $product->slug }}"
                    data-rating="{{ number_format($product->avg_rating, 1) }} ({{ $product->review_count }} đánh giá)">

                    {{-- Nhãn (Badge) trạng thái HOT, NEW hoặc DISCOUNT --}}
                    @if($discountInfo && !$isOos)
                    <span class="home-prod-card__badge home-prod-card__badge--sale">🏷️ {{ $discountInfo['label'] }}</span>
                    @elseif($isHot && !$isOos)
                    <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                    @elseif($isNew && !$isOos)
                    <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                    @endif
                    @if($isOos)
                    <span class="out-of-stock-overlay">Hết Hàng</span>
                    @endif

                    {{-- Nút thả tim yêu thích sản phẩm --}}
                    <button class="home-prod-card__wishlist p-product-card-wishlist {{ in_array($product->id, $favoriteProductIds) ? 'is-active' : '' }}"
                        data-id="{{ $product->id }}"
                        onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})"
                        aria-label="Yêu thích">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="heart-icon">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>

                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>

                {{-- Phần thân thông tin sản phẩm (Tên, Số sao, Số lượng đã bán, Giá cả, Nút thêm giỏ hàng) --}}
                <div class="p-product-body">
                    <a href="{{ route('product.show', $product->slug) }}" class="p-product-name p-product-name-link">{{ $product->name }}</a>

                    {{-- Số sao đánh giá và tổng lượt bán ra của món này --}}
                    <div class="p-product-stats p-product-stats-layout">
                        <svg class="p-product-star-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <span>{{ number_format($product->avg_rating, 1) }} </span>
                        <span class="p-product-stat-divider">|</span>
                        <span>Đã bán @if($product->total_sold >= 1000){{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}@endif</span>
                    </div>

                    {{-- Giá bán sản phẩm và nút thêm giỏ hàng nhanh --}}
                    <div class="p-product-price-row">
                        <div>
                            @if($discountInfo)
                            <span class="home-prod-card__price-old">{{ number_format($discountInfo['old_price'], 0, ',', '.') }}đ</span>
                            <span class="p-product-price">{{ number_format($discountInfo['sale_price'], 0, ',', '.') }}đ</span>
                            @else
                            <span class="p-product-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                            @endif
                        </div>
                        <div class="p-product-price-actions">
                            <button class="p-add-btn p-product-add-btn-size" aria-label="Thêm vào giỏ"
                                @if(!$isOos) onclick="addToCart({{ $product->id }})" @else disabled @endif>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            {{-- Hiển thị khi danh sách kết quả rỗng --}}
            <div class="p-product-grid-empty">
                Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
            </div>
            @endforelse

    </div><!-- end .p-product-grid -->

    {{-- Phân trang — giữ nguyên các tham số lọc hiện tại (category/max_price/rating/search) nhờ
    withQueryString() ở ProductController::index(). Tự viết markup thay vì dùng $products->links()
    mặc định của Laravel vì trang này không nạp Tailwind CSS. --}}
    @if($products->hasPages())
    <nav class="p-pagination" aria-label="Phân trang sản phẩm">
        <a href="{{ $products->previousPageUrl() }}"
            class="p-pagination__btn {{ $products->onFirstPage() ? 'p-pagination__btn--disabled' : '' }}"
            @if($products->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
            <span class="material-symbols-outlined">chevron_left</span>
        </a>

        @foreach(range(1, $products->lastPage()) as $page)
        <a href="{{ $products->url($page) }}"
            class="p-pagination__btn {{ $page === $products->currentPage() ? 'p-pagination__btn--active' : '' }}">
            {{ $page }}
        </a>
        @endforeach

        <a href="{{ $products->nextPageUrl() }}"
            class="p-pagination__btn {{ !$products->hasMorePages() ? 'p-pagination__btn--disabled' : '' }}"
            @if(!$products->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
            <span class="material-symbols-outlined">chevron_right</span>
        </a>
    </nav>
    @endif
</div><!-- end #ajax-product-area -->