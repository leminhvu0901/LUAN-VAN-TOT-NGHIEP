{{-- Lưới sản phẩm --}}
<div id="ajax-product-area">
    <!-- Grid chứa danh sách sản phẩm -->
    <div class="p-product-grid" id="product-grid">

        @forelse($products as $product)
            @php
                // Kiểm tra trạng thái hot và new phục vụ gắn huy hiệu và bộ lọc JS
                $isHot = in_array($product->id, $top6HotProductIds);
                $isNew = \Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15;
                $isOos = !$product->is_active; // Hết hàng khi is_active = 0
                $discountInfo = $product->discount_info;
                $displayPrice = $discountInfo ? $discountInfo['sale_price'] : $product->base_price;
            @endphp
            {{-- Sắp xếp sản phẩm --}}
            <div class="p-product-card {{ $isOos ? 'p-product-card--out-of-stock' : '' }}"
                data-sold="{{ $product->total_sold }}" data-price-val="{{ $displayPrice }}"
                data-date="{{ strtotime($product->created_at) }}" data-rating-val="{{ $product->avg_rating }}"
                data-is-hot="{{ $isHot ? '1' : '0' }}" data-is-new="{{ $isNew ? '1' : '0' }}">

                {{-- Chi tiết sản phẩm --}}
                <div class="p-product-img-wrap p-product-img-wrap-pointer"
                    onclick="window.location.href='{{ route('product.show', $product->slug) }}'"
                    data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                    data-price="{{ number_format($displayPrice, 0, ',', '.') }}đ"
                    data-category="{{ $product->category_name }}" data-image="{{ $product->image_url }}"
                    data-slug="{{ $product->slug }}"
                    data-rating="{{ number_format($product->avg_rating, 1) }} ({{ $product->review_count }} đánh giá)">

                    {{-- Nhãn trạng thái hot, new hoặc discount --}}
                    @if ($discountInfo && !$isOos)
                        <span class="home-prod-card__badge home-prod-card__badge--sale">🏷️
                            {{ $discountInfo['label'] }}</span>
                    @elseif($isHot && !$isOos)
                        <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                    @elseif($isNew && !$isOos)
                        <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                    @endif
                    @if ($isOos)
                        <span class="out-of-stock-overlay">Hết Hàng</span>
                    @endif

                    {{-- Nút thả tim yêu thích sản phẩm --}}
                    <button
                        class="home-prod-card__wishlist p-product-card-wishlist {{ in_array($product->id, $favoriteProductIds) ? 'is-active' : '' }}"
                        data-id="{{ $product->id }}"
                        onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})"
                        aria-label="Yêu thích">
                        <i class="fa-solid fa-heart heart-icon"></i>
                    </button>

                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                        onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>

                {{-- Phần thân thông tin sản phẩm --}}
                <div class="p-product-body">
                    <a href="{{ route('product.show', $product->slug) }}"
                        class="p-product-name p-product-name-link">{{ $product->name }}</a>

                    {{-- Số sao đánh giá và tổng lượt bán ra của món này --}}
                    <div class="p-product-stats p-product-stats-layout">
                        <i class="fa-solid fa-star p-product-star-svg"></i>
                        <span>{{ number_format($product->avg_rating, 1) }} </span>
                        <span class="p-product-stat-divider">|</span>
                        <span>Đã bán @if ($product->total_sold >= 1000)
                                {{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}
                            @endif
                        </span>
                    </div>

                    {{-- Giá bán sản phẩm và nút thêm giỏ hàng nhanh --}}
                    <div class="p-product-price-row">
                        <div>
                            @if ($discountInfo)
                                <span
                                    class="home-prod-card__price-old">{{ number_format($discountInfo['old_price'], 0, ',', '.') }}đ</span>
                                <span
                                    class="p-product-price">{{ number_format($discountInfo['sale_price'], 0, ',', '.') }}đ</span>
                            @else
                                <span
                                    class="p-product-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                            @endif
                        </div>
                        <div class="p-product-price-actions">
                            <button class="p-add-btn p-product-add-btn-size" aria-label="Thêm vào giỏ"
                                @if (!$isOos) onclick="addToCart({{ $product->id }})" @else disabled @endif>
                                <i class="fa-solid fa-plus"></i>
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

    </div><!-- End .p-product-grid -->

    {{-- Phân trang --}}
    @if ($products->hasPages())
        <nav class="p-pagination" aria-label="Phân trang sản phẩm">
            <a href="{{ $products->previousPageUrl() }}"
                class="p-pagination__btn {{ $products->onFirstPage() ? 'p-pagination__btn--disabled' : '' }}"
                @if ($products->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </a>

            @foreach (range(1, $products->lastPage()) as $page)
                <a href="{{ $products->url($page) }}"
                    class="p-pagination__btn {{ $page === $products->currentPage() ? 'p-pagination__btn--active' : '' }}">
                    {{ $page }}
                </a>
            @endforeach

            <a href="{{ $products->nextPageUrl() }}"
                class="p-pagination__btn {{ !$products->hasMorePages() ? 'p-pagination__btn--disabled' : '' }}"
                @if (!$products->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        </nav>
    @endif
</div><!-- End #ajax-product-area -->
