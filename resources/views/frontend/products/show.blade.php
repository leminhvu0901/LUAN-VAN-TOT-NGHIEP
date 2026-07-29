{{-- Kế thừa cấu trúc giao diện chính của toàn bộ trang web (Layout App) --}}
@extends('frontend.layouts.app')

@section('content')
{{-- Thẻ wrapper bao bọc chi tiết sản phẩm, đồng thời truyền id sản phẩm và giá cơ bản qua thuộc tính data để JS đọc --}}
<div class="pd-wrapper" data-product-id="{{ $product->id }}" data-base-price="{{ $product->base_price }}">
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
                {{-- Huy hiệu trạng thái Bán chạy (Hot) hoặc Sản phẩm mới (New) --}}
                @if($isHot && $product->is_active)
                    <span class="pd-badge pd-badge--hot">🔥 BESTSELLER</span>
                @elseif($isNew && $product->is_active)
                    <span class="pd-badge pd-badge--new">✨ Mới</span>
                @endif
                @if(!$product->is_active)
                    <span class="out-of-stock-overlay" style="font-size:1.2rem; padding: 0.6rem 1.6rem;">Hết Hàng</span>
                @endif

                {{-- Nút yêu thích sản phẩm (Wishlist). Nếu đã được yêu thích ($isFavorite = true), class 'is-active' sẽ tô màu đỏ cho trái tim --}}
                <button class="pd-wishlist-btn {{ $isFavorite ? 'is-active' : '' }}"
                        id="pd-wishlist-btn"
                        onclick="toggleFavorite(this, {{ $product->id }})"
                        aria-label="Yêu thích">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="heart-icon">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                {{-- Ảnh lớn hiển thị sản phẩm chính. Có sự kiện onerror tải ảnh placeholder nếu ảnh chính bị lỗi --}}
                <img id="pd-main-img"
                     src="{{ $product->image_url }}"
                     alt="{{ $product->name }}"
                     class="pd-gallery__img"
                     onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
            </div>

            {{-- Các ảnh thu nhỏ (Thumbnails) bổ sung ở phía dưới ảnh chính --}}
            <div class="pd-gallery__thumbs" id="pd-thumbs">
                <div class="pd-gallery__thumb is-active" onclick="switchImage(this, '{{ $product->image_url }}')">
                    <img src="{{ $product->image_url }}"
                         alt="{{ $product->name }}"
                         onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                
                @if($product->images)
                    @foreach($product->images as $galleryImg)
                        <div class="pd-gallery__thumb" onclick="switchImage(this, '{{ $galleryImg->image_url }}')">
                            <img src="{{ $galleryImg->image_url }}"
                                 alt="{{ $product->name }}"
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
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($avgR))
                            <svg class="pd-star pd-star--filled" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                        @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                            <svg class="pd-star pd-star--half" viewBox="0 0 24 24"><defs><linearGradient id="half{{ $i }}"><stop offset="50%" stop-color="#f59e0b"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="url(#half{{ $i }})"/></svg>
                        @else
                            <svg class="pd-star pd-star--empty" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                        @endif
                    @endfor
                </div>
                <span class="pd-rating-score">{{ number_format($product->avg_rating, 1) }}</span>
                <span class="pd-rating-count">({{ $product->review_count }} đánh giá)</span>
                {{-- Lượt mua: viết tắt dạng 1.5k+ nếu >= 1000 lượt bán --}}
                <span class="pd-sold-count">· Đã bán {{ $product->total_sold >= 1000 ? number_format($product->total_sold/1000,1).'k+' : $product->total_sold }}</span>
            </div>

            {{-- Giá bán của sản phẩm --}}
            <div class="pd-info__price-row">
                <span class="pd-info__price" id="pd-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
            </div>


            {{-- Lựa chọn Kích cỡ (Size). Gắn các thuộc tính data- để JS cộng trừ chênh lệch giá tiền khi click chọn --}}
            @if($sizes->count() > 0)
            <div class="pd-option-group">
                <div class="pd-option-label">CHỌN KÍCH CỠ</div>
                <div class="pd-option-chips" id="pd-sizes">
                    @foreach($sizes as $i => $size)
                    <button class="pd-chip {{ $i === 0 ? 'is-active' : '' }}"
                            data-size-name="{{ $size->size_name }}"
                            data-price-adj="{{ $size->price_adjustment }}"
                            onclick="selectSize(this)"
                            id="size-btn-{{ $loop->iteration }}">
                        Size {{ $size->size_name }}
                        @if($size->price_adjustment > 0)
                            <span class="pd-chip__adj">(+{{ number_format($size->price_adjustment, 0, ',', '.') }}đ)</span>
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
                    <button class="pd-chip pd-chip--sm is-active" data-value="100" onclick="selectOption(this, 'pd-sugar')">100%</button>
                    <button class="pd-chip pd-chip--sm" data-value="70" onclick="selectOption(this, 'pd-sugar')">70%</button>
                    <button class="pd-chip pd-chip--sm" data-value="50" onclick="selectOption(this, 'pd-sugar')">50%</button>
                    <button class="pd-chip pd-chip--sm" data-value="0" onclick="selectOption(this, 'pd-sugar')">0%</button>
                </div>
            </div>

            {{-- Lựa chọn mức đá --}}
            <div class="pd-option-group">
                <div class="pd-option-label">MỨC ĐÁ</div>
                <div class="pd-option-chips" id="pd-ice">
                    <button class="pd-chip pd-chip--sm is-active" data-value="normal" onclick="selectOption(this, 'pd-ice')">Đá chung</button>
                    <button class="pd-chip pd-chip--sm" data-value="full" onclick="selectOption(this, 'pd-ice')">Đá riêng</button>
                    <button class="pd-chip pd-chip--sm" data-value="less" onclick="selectOption(this, 'pd-ice')">Ít đá</button>
                    <button class="pd-chip pd-chip--sm" data-value="none" onclick="selectOption(this, 'pd-ice')">Không đá</button>
                </div>
            </div>

            {{-- Tùy chọn thêm Toppings (Không áp dụng cho danh mục Cà phê để khớp thực đơn) --}}
            @if($toppings->count() > 0 && mb_stripos($product->category_name, 'cà phê') === false)
            <div class="pd-option-group">
                <div class="pd-option-label">THÊM TOPPING (KHÔNG BẮT BUỘC)</div>
                <div class="dropdown">
                    <button class="btn w-100 text-start topping-dropdown-btn pd-topping-dropdown-btn" type="button" id="toppingDropdown">
                        <span id="topping-summary" class="pd-topping-summary">Chọn topping (không bắt buộc....)</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-chevron pd-dropdown-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="dropdown-menu w-100 shadow-lg pd-topping-dropdown-menu" aria-labelledby="toppingDropdown">
                        @foreach($toppings as $topping)
                        <li class="pd-topping-item-li">
                            <label class="dropdown-item d-flex justify-content-between align-items-center topping-item-label pd-topping-item-label">
                                <div class="form-check m-0 d-flex align-items-center">
                                    <input class="form-check-input topping-checkbox custom-checkbox pd-topping-checkbox" type="checkbox" value="{{ $topping->id }}" data-topping-price="{{ $topping->price }}" data-topping-name="{{ $topping->name }}" onchange="handleToppingChange(this)">
                                    <span class="form-check-label ms-3 pd-topping-label-text">
                                        {{ $topping->name }}
                                    </span>
                                </div>
                                <span class="pd-topping-price-badge">+{{ number_format($topping->price, 0, ',', '.') }}đ</span>
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
                        @if(!$product->is_active) disabled @else onclick="changeQty(-1)" @endif
                        aria-label="Giảm">−</button>
                    <span class="pd-qty__val" id="pd-qty-val">1</span>
                    <button class="pd-qty__btn" id="pd-qty-plus"
                        @if(!$product->is_active) disabled @else onclick="changeQty(1)" @endif
                        aria-label="Tăng">+</button>
                </div>
                @if($product->is_active)
                <button class="pd-add-cart-btn" id="pd-add-cart" onclick="addToCartFromDetail()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    Thêm vào giỏ hàng
                </button>
                @else
                <button class="pd-add-cart-btn pd-add-cart-btn--sold-out" id="pd-add-cart" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    Hết hàng
                </button>
                @endif
            </div>

            {{-- Nhãn đảm bảo chất lượng và thời gian giao hàng --}}
            <div class="pd-badges-row">
                <div class="pd-badge-item">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <div>
                        <div class="pd-badge-item__title">Giao hàng</div>
                        <div class="pd-badge-item__sub">Trong 20–30 phút</div>
                    </div>
                </div>
                <div class="pd-badge-item">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <div>
                        <div class="pd-badge-item__title">Chất lượng</div>
                        <div class="pd-badge-item__sub">Nguyên liệu sạch</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== PHẦN MÔ TẢ CHI TIẾT SẢN PHẨM ===== --}}
    @if($product->description)
    <div class="pd-desc-section">
        <h2 class="pd-section-title">Mô tả sản phẩm</h2>
        <div class="pd-desc-body" style="word-wrap: break-word; overflow-wrap: break-word; hyphens: auto;">
            <p>{{ $product->description }}</p>
        </div>
    </div>
    @endif

    {{-- ===== PHẦN ĐÁNH GIÁ TỪ KHÁCH HÀNG ===== --}}
    <div class="pd-reviews-section">
        <h2 class="pd-section-title">Đánh giá từ khách hàng</h2>

        {{-- Bảng tóm tắt điểm đánh giá sao trung bình & tỉ lệ phần trăm các mức sao --}}
        <div class="pd-reviews-summary">
            <div class="pd-reviews-score">
                <div class="pd-reviews-score__num">{{ number_format($product->avg_rating, 1) }}</div>
                <div class="pd-reviews-score__stars">
                    @for($i=1;$i<=5;$i++)
                        <svg class="pd-star {{ $i <= round($product->avg_rating) ? 'pd-star--filled' : 'pd-star--empty' }}" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                    @endfor
                </div>
                <div class="pd-reviews-score__total">{{ $product->review_count }} đánh giá</div>
            </div>

            <div class="pd-rating-bars">
                @php $totalReviews = $product->review_count ?: 1; @endphp
                @for($star = 5; $star >= 1; $star--)
                @php $cnt = $ratingDistribution[$star] ?? 0; @endphp
                <div class="pd-rating-bar-row">
                    <span class="pd-rating-bar-label">{{ $star }} ★</span>
                    <div class="pd-rating-bar-track">
                        <div class="pd-rating-bar-fill" style="width: {{ round(($cnt / $totalReviews) * 100) }}%"></div>
                    </div>
                    <span class="pd-rating-bar-count">{{ $cnt }}</span>
                </div>
                @endfor
            </div>
        </div>

        {{-- Danh sách các bình luận của khách hàng — bọc trong .reviews-app để reviews-filter.js nhận
        diện, nút lọc + khung danh sách/nút "Xem thêm" đều dùng chung 1 bộ class với trang "Xem đánh
        giá" (xem public/js/frontend/products/reviews-filter.js). --}}
        <div class="reviews-app" data-product-id="{{ $product->id }}" data-view="full">
            <div class="pd-review-filters">
                <button type="button" class="pd-review-filter-btn review-filter-btn is-active" data-rating="" data-has-image="">Tất cả</button>
                @for($star = 5; $star >= 1; $star--)
                    <button type="button" class="pd-review-filter-btn review-filter-btn" data-rating="{{ $star }}" data-has-image="">{{ $star }} sao ({{ $ratingDistribution[$star] ?? 0 }})</button>
                @endfor
                <button type="button" class="pd-review-filter-btn review-filter-btn" data-rating="" data-has-image="1">Có hình ảnh ({{ $hasImageCount }})</button>
            </div>

            <div class="pd-review-list">
                @include('frontend.products.partials.reviews-list-full', ['reviews' => $reviews])
            </div>
        </div>
    </div>

    {{-- ===== SẢN PHẨM TƯƠNG TỰ (GỢI Ý) ===== --}}
    @if($relatedProducts->count() > 0)
    <div class="pd-related-section">
        <h2 class="pd-section-title">Sản phẩm tương tự</h2>
        <div class="pd-related-grid">
            @foreach($relatedProducts as $rel)
            <a href="{{ route('product.show', $rel->slug) }}" class="pd-rel-card">
                <div class="pd-rel-card__img-wrap">
                    <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="pd-rel-card__img" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                <div class="pd-rel-card__body">
                    <p class="pd-rel-card__name">{{ $rel->name }}</p>
                    <div class="pd-rel-card__stats">
                        <svg class="pd-rel-rating-star" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span>{{ number_format($rel->avg_rating, 1) }}</span>
                    </div>
                    <span class="pd-rel-card__price">{{ number_format($rel->base_price, 0, ',', '.') }}đ</span>
                    {{-- Nút thêm nhanh sản phẩm tương tự vào giỏ hàng --}}
                    <button class="pd-rel-card__add" onclick="event.preventDefault(); addToCart({{ $rel->id }})" aria-label="Thêm vào giỏ">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Đẩy mã JS của trang chi tiết sản phẩm vào ngăn xếp stack của Layout --}}
@push('scripts')
<script src="{{ asset('js/frontend/products/show.js') }}?v={{ filemtime(public_path('js/frontend/products/show.js')) }}"></script>
<script src="{{ asset('js/frontend/products/reviews-filter.js') }}?v={{ filemtime(public_path('js/frontend/products/reviews-filter.js')) }}"></script>
@endpush
@endsection
