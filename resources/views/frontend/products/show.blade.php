{{-- Kế thừa cấu trúc giao diện chính của toàn bộ trang web (Layout App) --}}
@extends('frontend.layouts.app')

@section('content')
    @php
        $discountInfo = $product->discount_info;
        $effectivePrice = $discountInfo ? $discountInfo['sale_price'] : $product->base_price;
    @endphp
    {{-- Thẻ wrapper bao bọc chi tiết sản phẩm, đồng thời truyền id sản phẩm và giá cơ bản qua thuộc tính data để JS đọc --}}
    <div class="pd-wrapper" data-product-id="{{ $product->id }}" data-base-price="{{ $effectivePrice }}">
        {{-- ===== BREADCRUMB (ĐƯỜNG DẪN ĐỊNH VỊ) ===== --}}
        <nav class="pd-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Trang chủ</a>
            <span class="pd-breadcrumb__sep">›</span>
            <a href="/products">{{ $product->category_name }}</a>
            <span class="pd-breadcrumb__sep">›</span>
            <span class="pd-breadcrumb__current">{{ $product->name }}</span>
        </nav>

        {{-- ===== KHU VỰC CHI TIẾT SẢN PHẨM CHÍNH ===== --}}
        <div class="pd-main">
            {{-- BÊN TRÁI: Gallery hình ảnh sản phẩm --}}
            <div class="pd-gallery">
                <div class="pd-gallery__main">
                    {{-- Huy hiệu trạng thái Bán chạy (Hot), Sản phẩm mới (New) hoặc Giảm giá --}}
                    @if ($discountInfo && $product->is_active)
                        <span class="pd-badge pd-badge--sale"
                            style="background: linear-gradient(135deg, #e11d48, #7c3aed); color: #fff; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.35);">🏷️
                            {{ $discountInfo['label'] }}</span>
                    @elseif($isHot && $product->is_active)
                        <span class="pd-badge pd-badge--hot">🔥 BESTSELLER</span>
                    @elseif($isNew && $product->is_active)
                        <span class="pd-badge pd-badge--new">✨ Mới</span>
                    @endif
                    @if (!$product->is_active)
                        <span class="out-of-stock-overlay" style="font-size:1.2rem; padding: 0.6rem 1.6rem;">Hết Hàng</span>
                    @endif

                    {{-- Nút yêu thích sản phẩm (Wishlist). Nếu đã được yêu thích ($isFavorite = true), class 'is-active' sẽ tô màu đỏ cho trái tim --}}
                    <button class="pd-wishlist-btn {{ $isFavorite ? 'is-active' : '' }}" id="pd-wishlist-btn"
                        onclick="toggleFavorite(this, {{ $product->id }})" aria-label="Yêu thích">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="heart-icon">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                            </path>
                        </svg>
                    </button>

                    {{-- Ảnh lớn hiển thị sản phẩm chính. Có sự kiện onerror tải ảnh placeholder nếu ảnh chính bị lỗi --}}
                    <img id="pd-main-img" src="{{ $product->image_url }}" alt="{{ $product->name }}" class="pd-gallery__img"
                        onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>

                {{-- Các ảnh thu nhỏ (Thumbnails) bổ sung ở phía dưới ảnh chính --}}
                <div class="pd-gallery__thumbs" id="pd-thumbs">
                    <div class="pd-gallery__thumb is-active" onclick="switchImage(this, '{{ $product->image_url }}')">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                    </div>

                    @if ($product->images)
                        @foreach ($product->images as $galleryImg)
                            <div class="pd-gallery__thumb" onclick="switchImage(this, '{{ $galleryImg->image_url }}')">
                                <img src="{{ $galleryImg->image_url }}" alt="{{ $product->name }}"
                                    onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- BÊN PHẢI: Thông tin chi tiết sản phẩm --}}
            <div class="pd-info">
                {{-- Tên sản phẩm --}}
                <h1 class="pd-info__name">{{ $product->name }}</h1>

                {{-- Dòng hiển thị đánh giá sao và lượt mua --}}
                <div class="pd-info__rating-row">
                    <div class="pd-stars">
                        {{-- Tính toán làm tròn điểm số để đổ màu ngôi sao (Filled, Half, Empty) --}}
                        @php $avgR = round($product->avg_rating * 2) / 2; @endphp
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= floor($avgR))
                                <svg class="pd-star pd-star--filled" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                        fill="currentColor" />
                                </svg>
                            @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                                <svg class="pd-star pd-star--half" viewBox="0 0 24 24">
                                    <defs>
                                        <linearGradient id="half{{ $i }}">
                                            <stop offset="50%" stop-color="#f59e0b" />
                                            <stop offset="50%" stop-color="#d1d5db" />
                                        </linearGradient>
                                    </defs>
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                        fill="url(#half{{ $i }})" />
                                </svg>
                            @else
                                <svg class="pd-star pd-star--empty" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                        fill="currentColor" />
                                </svg>
                            @endif
                        @endfor
                    </div>
                    <span class="pd-rating-score">{{ number_format($product->avg_rating, 1) }}</span>
                    <span class="pd-rating-count">({{ $product->review_count }} đánh giá)</span>
                    {{-- Lượt mua: viết tắt dạng 1.5k+ nếu >= 1000 lượt bán --}}
                    <span class="pd-sold-count">· Đã bán
                        {{ $product->total_sold >= 1000 ? number_format($product->total_sold / 1000, 1) . 'k+' : $product->total_sold }}</span>
                </div>

                {{-- Giá bán của sản phẩm --}}
                <div class="pd-info__price-row" style="display: flex; align-items: baseline; gap: 10px;">
                    @if ($discountInfo)
                        <span class="pd-info__price-old"
                            style="font-size: 1.2rem; font-weight: 600; color: #9ca3af; text-decoration: line-through;">{{ number_format($discountInfo['old_price'], 0, ',', '.') }}đ</span>
                        <span class="pd-info__price"
                            id="pd-price">{{ number_format($discountInfo['sale_price'], 0, ',', '.') }}đ</span>
                        <span
                            style="background: #ef4444; color: #fff; font-weight: 700; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px;">{{ $discountInfo['label'] }}</span>
                    @else
                        <span class="pd-info__price"
                            id="pd-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                    @endif
                </div>


                {{-- Lựa chọn Kích cỡ (Size). Gắn các thuộc tính data- để JS cộng trừ chênh lệch giá tiền khi click chọn --}}
                @if ($sizes->count() > 0)
                    <div class="pd-option-group">
                        <div class="pd-option-label">CHỌN KÍCH CỠ</div>
                        <div class="pd-option-chips" id="pd-sizes">
                            @foreach ($sizes as $i => $size)
                                <button class="pd-chip {{ $i === 0 ? 'is-active' : '' }}"
                                    data-size-name="{{ $size->size_name }}" data-price-adj="{{ $size->price_adjustment }}"
                                    onclick="selectSize(this)" id="size-btn-{{ $loop->iteration }}">
                                    Size {{ $size->size_name }}
                                    @if ($size->price_adjustment > 0)
                                        <span
                                            class="pd-chip__adj">(+{{ number_format($size->price_adjustment, 0, ',', '.') }}đ)</span>
                                    @else
                                        <span class="pd-chip__adj">(+0đ)</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Lựa chọn mức đường --}}
                <div class="pd-option-group">
                    <div class="pd-option-label">MỨC ĐƯỜNG</div>
                    <div class="pd-option-chips" id="pd-sugar">
                        <button class="pd-chip pd-chip--sm is-active" data-value="100"
                            onclick="selectOption(this, 'pd-sugar')">100%</button>
                        <button class="pd-chip pd-chip--sm" data-value="70"
                            onclick="selectOption(this, 'pd-sugar')">70%</button>
                        <button class="pd-chip pd-chip--sm" data-value="50"
                            onclick="selectOption(this, 'pd-sugar')">50%</button>
                        <button class="pd-chip pd-chip--sm" data-value="0"
                            onclick="selectOption(this, 'pd-sugar')">0%</button>
                    </div>
                </div>

                {{-- Lựa chọn mức đá --}}
                <div class="pd-option-group">
                    <div class="pd-option-label">MỨC ĐÁ</div>
                    <div class="pd-option-chips" id="pd-ice">
                        <button class="pd-chip pd-chip--sm is-active" data-value="normal"
                            onclick="selectOption(this, 'pd-ice')">Đá chung</button>
                        <button class="pd-chip pd-chip--sm" data-value="full" onclick="selectOption(this, 'pd-ice')">Đá
                            riêng</button>
                        <button class="pd-chip pd-chip--sm" data-value="less" onclick="selectOption(this, 'pd-ice')">Ít
                            đá</button>
                        <button class="pd-chip pd-chip--sm" data-value="none"
                            onclick="selectOption(this, 'pd-ice')">Không đá</button>
                    </div>
                </div>

                {{-- Tùy chọn thêm Toppings (Không áp dụng cho danh mục Cà phê để khớp thực đơn) --}}
                @if ($toppings->count() > 0 && mb_stripos($product->category_name, 'cà phê') === false)
                    <div class="pd-option-group">
                        <div class="pd-option-label">THÊM TOPPING (KHÔNG BẮT BUỘC)</div>
                        <div class="dropdown">
                            <button class="btn w-100 text-start topping-dropdown-btn pd-topping-dropdown-btn"
                                type="button" id="toppingDropdown">
                                <span id="topping-summary" class="pd-topping-summary">Chọn topping (không bắt
                                    buộc....)</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="dropdown-chevron pd-dropdown-chevron">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <ul class="dropdown-menu w-100 shadow-lg pd-topping-dropdown-menu"
                                aria-labelledby="toppingDropdown">
                                @foreach ($toppings as $topping)
                                    <li class="pd-topping-item-li">
                                        <label
                                            class="dropdown-item d-flex justify-content-between align-items-center topping-item-label pd-topping-item-label">
                                            <div class="form-check m-0 d-flex align-items-center">
                                                <input
                                                    class="form-check-input topping-checkbox custom-checkbox pd-topping-checkbox"
                                                    type="checkbox" value="{{ $topping->id }}"
                                                    data-topping-price="{{ $topping->price }}"
                                                    data-topping-name="{{ $topping->name }}"
                                                    onchange="handleToppingChange(this)">
                                                <span class="form-check-label ms-3 pd-topping-label-text">
                                                    {{ $topping->name }}
                                                </span>
                                            </div>
                                            <span
                                                class="pd-topping-price-badge">+{{ number_format($topping->price, 0, ',', '.') }}đ</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Chọn Số lượng & Nút Thêm vào giỏ hàng --}}
                <div class="pd-actions">
                    <div class="pd-qty">
                        <button class="pd-qty__btn" id="pd-qty-minus"
                            @if (!$product->is_active) disabled @else onclick="changeQty(-1)" @endif
                            aria-label="Giảm">−</button>
                        <span class="pd-qty__val" id="pd-qty-val">1</span>
                        <button class="pd-qty__btn" id="pd-qty-plus"
                            @if (!$product->is_active) disabled @else onclick="changeQty(1)" @endif
                            aria-label="Tăng">+</button>
                    </div>
                    @if ($product->is_active)
                        <button class="pd-add-cart-btn" id="pd-add-cart" onclick="addToCartFromDetail()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 01-8 0" />
                            </svg>
                            Thêm vào giỏ hàng
                        </button>
                    @else
                        <button class="pd-add-cart-btn pd-add-cart-btn--sold-out" id="pd-add-cart" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 01-8 0" />
                            </svg>
                            Hết hàng
                        </button>
                    @endif
                </div>

                {{-- Nhãn đảm bảo chất lượng và thời gian giao hàng --}}
                <div class="pd-badges-row">
                    <div class="pd-badge-item">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a"
                            stroke-width="2">
                            <rect x="1" y="3" width="15" height="13" rx="2" />
                            <path d="M16 8h4l3 3v5h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                        <div>
                            <div class="pd-badge-item__title">Giao hàng</div>
                            <div class="pd-badge-item__sub">Trong 20–30 phút</div>
                        </div>
                    </div>
                    <div class="pd-badge-item">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a"
                            stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <div>
                            <div class="pd-badge-item__title">Chất lượng</div>
                            <div class="pd-badge-item__sub">Nguyên liệu sạch</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== PHẦN MÔ TẢ CHI TIẾT SẢN PHẨM ===== --}}
        @if ($product->description)
            <div class="pd-desc-section">
                <h2 class="pd-section-title">Mô tả sản phẩm</h2>
                <div class="pd-desc-body" style="word-wrap: break-word; overflow-wrap: break-word; hyphens: auto;">
                    <p>{{ $product->description }}</p>
                </div>
            </div>
        @endif

        {{-- ===== PHẦN ĐÁNH GIÁ TỪ KHÁCH HÀNG ===== --}}
        <div class="pd-reviews-section" id="reviews-section">
            <h2 class="pd-section-title">Đánh giá từ khách hàng</h2>

            {{-- Bảng tóm tắt điểm đánh giá sao trung bình & tỉ lệ phần trăm các mức sao --}}
            <div class="pd-reviews-summary">
                <div class="pd-reviews-score">
                    <div class="pd-reviews-score__num">{{ number_format($product->avg_rating, 1) }}</div>
                    <div class="pd-reviews-score__stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="pd-star {{ $i <= round($product->avg_rating) ? 'pd-star--filled' : 'pd-star--empty' }}"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                    fill="currentColor" />
                            </svg>
                        @endfor
                    </div>
                    <div class="pd-reviews-score__total">{{ $product->review_count }} đánh giá</div>
                </div>

                <div class="pd-rating-bars">
                    @php $totalReviews = $product->review_count ?: 1; @endphp
                    @for ($star = 5; $star >= 1; $star--)
                        @php $cnt = $ratingDistribution[$star] ?? 0; @endphp
                        <div class="pd-rating-bar-row">
                            <span class="pd-rating-bar-label">{{ $star }} ★</span>
                            <div class="pd-rating-bar-track">
                                <div class="pd-rating-bar-fill"
                                    style="width: {{ round(($cnt / $totalReviews) * 100) }}%"></div>
                            </div>
                            <span class="pd-rating-bar-count">{{ $cnt }}</span>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Danh sách các bình luận của khách hàng — nút lọc giờ là link GET thật (tải lại trang),
        trạng thái "đang chọn" tính thẳng từ request() hiện tại thay vì JS. --}}
            <div class="reviews-app">
                <div class="pd-review-filters">
                    <a href="{{ route('product.show', $product->slug) }}#reviews-section"
                        class="pd-review-filter-btn review-filter-btn {{ !request('rating') && !request('has_image') ? 'is-active' : '' }}">Tất
                        cả</a>
                    @for ($star = 5; $star >= 1; $star--)
                        <a href="{{ route('product.show', $product->slug) }}?rating={{ $star }}#reviews-section"
                            class="pd-review-filter-btn review-filter-btn {{ request('rating') == $star ? 'is-active' : '' }}">{{ $star }}
                            sao ({{ $ratingDistribution[$star] ?? 0 }})</a>
                    @endfor
                    <a href="{{ route('product.show', $product->slug) }}?has_image=1#reviews-section"
                        class="pd-review-filter-btn review-filter-btn {{ request('has_image') ? 'is-active' : '' }}">Có
                        hình ảnh ({{ $hasImageCount }})</a>
                </div>

                <div class="pd-review-list">
                    @include('frontend.products.partials.reviews-list-full', [
                        'reviews' => $reviews,
                        'isFiltered' => $isFiltered,
                    ])
                </div>
            </div>
        </div>

        {{-- ===== SẢN PHẨM TƯƠNG TỰ (GỢI Ý) ===== --}}
        @if ($relatedProducts->count() > 0)
            <div class="pd-related-section">
                <h2 class="pd-section-title">Sản phẩm tương tự</h2>
                <div class="pd-related-grid">
                    @foreach ($relatedProducts as $rel)
                        @php
                            // Cùng logic gắn huy hiệu Hot/Mới/Giảm giá như thẻ ở trang danh sách sản phẩm
                            $relIsHot = in_array($rel->id, $top6HotProductIds);
                            $relIsNew = \Carbon\Carbon::parse($rel->created_at)->diffInDays(now()) <= 15;
                            $relIsOos = !$rel->is_active;
                            $relDiscount = $rel->discount_info;
                        @endphp
                        <a href="{{ route('product.show', $rel->slug) }}" class="pd-rel-card">
                            <div class="pd-rel-card__img-wrap">
                                {{-- Nhãn (Badge) trạng thái HOT, NEW hoặc DISCOUNT - ưu tiên hiện Sale > Hot > New --}}
                                @if ($relDiscount && !$relIsOos)
                                    <span class="home-prod-card__badge home-prod-card__badge--sale">🏷️
                                        {{ $relDiscount['label'] }}</span>
                                @elseif($relIsHot && !$relIsOos)
                                    <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                                @elseif($relIsNew && !$relIsOos)
                                    <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                                @endif
                                @if ($relIsOos)
                                    <span class="out-of-stock-overlay">Hết Hàng</span>
                                @endif
                                <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="pd-rel-card__img"
                                    onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                            </div>
                            <div class="pd-rel-card__body">
                                <p class="pd-rel-card__name">{{ $rel->name }}</p>
                                <div class="pd-rel-card__stats">
                                    <svg class="pd-rel-rating-star" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    <span>{{ number_format($rel->avg_rating, 1) }}</span>
                                    <span style="opacity: 0.5;">|</span>
                                    <span>Đã bán
                                        @if ($rel->total_sold >= 1000)
                                            {{ number_format($rel->total_sold / 1000, 1) }}k+
                                        @else
                                            {{ $rel->total_sold }}
                                        @endif
                                    </span>
                                </div>
                                @if ($relDiscount)
                                    <span class="home-prod-card__price-old"
                                        style="font-size: 11px;">{{ number_format($relDiscount['old_price'], 0, ',', '.') }}đ</span>
                                    <span
                                        class="pd-rel-card__price">{{ number_format($relDiscount['sale_price'], 0, ',', '.') }}đ</span>
                                @else
                                    <span
                                        class="pd-rel-card__price">{{ number_format($rel->base_price, 0, ',', '.') }}đ</span>
                                @endif
                                {{-- Nút thêm nhanh sản phẩm tương tự vào giỏ hàng --}}
                                <button class="pd-rel-card__add"
                                    onclick="event.preventDefault(); addToCart({{ $rel->id }})"
                                    aria-label="Thêm vào giỏ">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="3">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                </button>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            // TRANG CHI TIẾT SẢN PHẨM: tính giá đơn vị theo lựa chọn Size/Topping của khách, thêm vào
            (function() {
                let qty = 1;
                const wrapper = document.querySelector('.pd-wrapper');
                let basePrice = wrapper ? (parseFloat(wrapper.getAttribute('data-base-price')) || 0) : 0;
                let sizeAdj = 0;
                let toppingAdj = 0;
                const productId = wrapper ? (parseInt(wrapper.getAttribute('data-product-id')) || 0) : 0;

                // Tính lại giá 1 ly theo công thức: giá gốc + phụ phí size + tổng phụ phí topping,
                function updatePrice() {
                    const unitPrice = basePrice + sizeAdj + toppingAdj;
                    document.getElementById('pd-price').textContent = unitPrice.toLocaleString('vi-VN') + 'đ';
                }

                // Tăng/giảm số lượng ly muốn mua (nút +/-), không cho xuống dưới 1; kèm hiệu ứng "nảy
                window.changeQty = function(delta) {
                    qty = Math.max(1, qty + delta);
                    document.getElementById('pd-qty-val').textContent = qty;
                    updatePrice();

                    const el = document.getElementById('pd-qty-val');
                    el.classList.remove('pd-qty__val--bump');
                    void el.offsetWidth;
                    el.classList.add('pd-qty__val--bump');
                };

                // Chọn 1 Size: đổi trạng thái "đang chọn" trong nhóm nút Size (chỉ 1 nút active
                // tại 1 thời điểm), đọc phụ phí giá tương ứng từ data-price-adj rồi tính lại giá hiển thị
                window.selectSize = function(btn) {
                    document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');

                    sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
                    updatePrice();
                };

                // Hàm dùng chung cho các nhóm lựa chọn KHÔNG ảnh hưởng tới giá (mức Đường, mức Đá) -
                // chỉ đổi trạng thái active trong đúng nhóm (groupId) chứa nút vừa bấm, không tính lại giá
                window.selectOption = function(btn, groupId) {
                    document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove(
                    'is-active'));
                    btn.classList.add('is-active');
                };

                // Xử lý khi tích/bỏ tích 1 checkbox Topping: cộng dồn lại tổng phụ phí từ TẤT CẢ topping
                // đang được chọn (không chỉ riêng ô vừa đổi), tính lại giá, và cập nhật dòng tóm tắt tên
                // các topping đã chọn hiển thị trên nút dropdown (đổi cả màu viền/nền cho nút khi có chọn)
                window.handleToppingChange = function(inputEl = null) {
                    if (inputEl) {
                        const labelEl = inputEl.closest('.topping-item-label');
                        labelEl.classList.toggle('is-selected', inputEl.checked);
                    }

                    toppingAdj = 0;
                    let selectedNames = [];

                    document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
                        toppingAdj += parseFloat(cb.getAttribute('data-topping-price')) || 0;
                        selectedNames.push(cb.getAttribute('data-topping-name'));
                    });

                    updatePrice();

                    const summary = document.getElementById('topping-summary');
                    const btn = document.getElementById('toppingDropdown');
                    if (selectedNames.length > 0) {
                        summary.innerText = selectedNames.join(', ');
                        summary.style.color = '#065f46';
                        btn.style.borderColor = '#10b981';
                        btn.style.background = '#ecfdf5';
                    } else {
                        summary.innerText = 'Chọn topping (không bắt buộc)...';
                        summary.style.color = '#6b7280';
                        btn.style.borderColor = '#e5e7eb';
                        btn.style.background = '#ffffff';
                    }
                };

                // Đóng/mở menu thả xuống danh sách Topping (dạng dropdown tùy biến, không dùng <select>
                const toppingDropdownBtn = document.getElementById('toppingDropdown');
                if (toppingDropdownBtn) {
                    const dropdownContainer = toppingDropdownBtn.closest('.dropdown');
                    const dropdownMenu = dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null;
                    const chevron = toppingDropdownBtn.querySelector('.dropdown-chevron');

                    if (dropdownMenu) {
                        toppingDropdownBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isOpen = dropdownMenu.classList.contains('show');
                            if (isOpen) {
                                dropdownMenu.classList.remove('show');
                                if (chevron) chevron.style.transform = 'rotate(0deg)';
                            } else {
                                dropdownMenu.classList.add('show');
                                if (chevron) chevron.style.transform = 'rotate(180deg)';
                            }
                        });

                        dropdownMenu.addEventListener('click', (e) => {
                            e.stopPropagation();
                        });

                        document.addEventListener('click', () => {
                            dropdownMenu.classList.remove('show');
                            if (chevron) chevron.style.transform = 'rotate(0deg)';
                        });
                    }
                }

                // Thêm sản phẩm (kèm đầy đủ tùy chọn Size/Đường/Đá/Topping đang chọn) vào giỏ hàng
                window.addToCartFromDetail = function() {
                    const btn = document.getElementById('pd-add-cart');
                    // Tạm khóa nút + đổi icon/label sang "Đang thêm..." để tránh bấm nhiều lần liên tiếp
                    // gây thêm trùng sản phẩm khi mạng chậm; tự khôi phục lại sau 1.2 giây bên dưới
                    btn.disabled = true;
                    btn.innerHTML =
                        '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đang thêm...';

                    // Đọc lại lựa chọn hiện tại của khách trực tiếp từ DOM (nút nào đang có class is-active)
                    const activeSize = document.querySelector('#pd-sizes .pd-chip.is-active');
                    const activeSugar = document.querySelector('#pd-sugar .pd-chip.is-active');
                    const activeIce = document.querySelector('#pd-ice .pd-chip.is-active');
                    const activeToppings = document.querySelectorAll('.topping-checkbox:checked');
                    const toppingIds = Array.from(activeToppings).map(cb => cb.value);

                    const options = {
                        size_name: activeSize ? activeSize.getAttribute('data-size-name') : null,
                        sugar_level: activeSugar ? activeSugar.getAttribute('data-value') : null,
                        ice_level: activeIce ? activeIce.getAttribute('data-value') : null,
                        toppings: toppingIds
                    };

                    addToCart(productId, qty, options);

                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Thêm vào giỏ hàng';
                    }, 1200);
                };

                // Bấm vào 1 ảnh thu nhỏ 
                window.switchImage = function(thumb, url) {
                    document.querySelectorAll('.pd-gallery__thumb').forEach(t => t.classList.remove('is-active'));
                    thumb.classList.add('is-active');

                    const mainImg = document.getElementById('pd-main-img');
                    mainImg.style.opacity = '0';
                    setTimeout(() => {
                        mainImg.src = url;
                        mainImg.style.opacity = '1';
                    }, 180);
                };

                updatePrice(); // Tính giá hiển thị ngay lúc tải trang, khớp với Size mặc định đang active
            })();
        </script>
    @endpush

@endsection
