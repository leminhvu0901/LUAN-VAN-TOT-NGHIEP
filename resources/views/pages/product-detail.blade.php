@extends('layouts.app')

@section('content')
<div class="pd-wrapper">
    {{-- ===== BREADCRUMB ===== --}}
    <nav class="pd-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Trang chủ</a>
        <span class="pd-breadcrumb__sep">›</span>
        <a href="/products">{{ $product->category_name }}</a>
        <span class="pd-breadcrumb__sep">›</span>
        <span class="pd-breadcrumb__current">{{ $product->name }}</span>
    </nav>

    {{-- ===== MAIN PRODUCT SECTION ===== --}}
    <div class="pd-main">
        {{-- LEFT: Image Gallery --}}
        <div class="pd-gallery">
            <div class="pd-gallery__main">
                @if($isHot)
                    <span class="pd-badge pd-badge--hot">🔥 BESTSELLER</span>
                @elseif($isNew)
                    <span class="pd-badge pd-badge--new">✨ Mới</span>
                @endif

                {{-- Wishlist --}}
                <button class="pd-wishlist-btn {{ $isFavorite ? 'is-active' : '' }}"
                        id="pd-wishlist-btn"
                        onclick="toggleFavorite(this, {{ $product->id }})"
                        aria-label="Yêu thích">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="heart-icon">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                <img id="pd-main-img"
                     src="{{ asset('images/' . $product->image) }}"
                     alt="{{ $product->name }}"
                     class="pd-gallery__img"
                     onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
            </div>

            {{-- Thumbnails --}}
            <div class="pd-gallery__thumbs" id="pd-thumbs">
                <div class="pd-gallery__thumb is-active" onclick="switchImage(this, '{{ asset('images/' . $product->image) }}')">
                    <img src="{{ asset('images/' . $product->image) }}"
                         alt="{{ $product->name }}"
                         onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>

            </div>
        </div>

        {{-- RIGHT: Product Info --}}
        <div class="pd-info">
            <h1 class="pd-info__name">{{ $product->name }}</h1>

            {{-- Rating Row --}}
            <div class="pd-info__rating-row">
                <div class="pd-stars">
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
                <span class="pd-sold-count">· Đã bán {{ $product->total_sold >= 1000 ? number_format($product->total_sold/1000,1).'k+' : $product->total_sold }}</span>
            </div>

            {{-- Price --}}
            <div class="pd-info__price-row">
                <span class="pd-info__price" id="pd-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
            </div>

            {{-- Description --}}
            @if($product->description)
            <p class="pd-info__desc">{{ $product->description }}</p>
            @endif

            {{-- Sizes --}}
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

            {{-- Sugar Level --}}
            <div class="pd-option-group">
                <div class="pd-option-label">MỨC ĐƯỜNG</div>
                <div class="pd-option-chips" id="pd-sugar">
                    <button class="pd-chip pd-chip--sm is-active" data-value="100" onclick="selectOption(this, 'pd-sugar')">100%</button>
                    <button class="pd-chip pd-chip--sm" data-value="70" onclick="selectOption(this, 'pd-sugar')">70%</button>
                    <button class="pd-chip pd-chip--sm" data-value="50" onclick="selectOption(this, 'pd-sugar')">50%</button>
                    <button class="pd-chip pd-chip--sm" data-value="0" onclick="selectOption(this, 'pd-sugar')">0%</button>
                </div>
            </div>

            {{-- Ice Level --}}
            <div class="pd-option-group">
                <div class="pd-option-label">MỨC ĐÁ</div>
                <div class="pd-option-chips" id="pd-ice">
                    <button class="pd-chip pd-chip--sm is-active" data-value="normal" onclick="selectOption(this, 'pd-ice')">Đá chung</button>
                    <button class="pd-chip pd-chip--sm" data-value="full" onclick="selectOption(this, 'pd-ice')">Đá riêng</button>
                    <button class="pd-chip pd-chip--sm" data-value="less" onclick="selectOption(this, 'pd-ice')">Ít đá</button>
                    <button class="pd-chip pd-chip--sm" data-value="none" onclick="selectOption(this, 'pd-ice')">Không đá</button>
                </div>
            </div>

            {{-- Toppings --}}
            @if($toppings->count() > 0 && mb_stripos($product->category_name, 'cà phê') === false)

            <div class="pd-option-group">
                <div class="pd-option-label">THÊM TOPPING (KHÔNG BẮT BUỘC)</div>
                <div class="dropdown">
                    <button class="btn w-100 text-start topping-dropdown-btn" type="button" id="toppingDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" style="border-radius: 12px; padding: 14px 18px; border: 1.5px solid #e5e7eb; background: #ffffff; display: flex; justify-content: space-between; align-items: center; color: #6b7280; font-size: 15px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <span id="topping-summary" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%;">Chọn topping (không bắt buộc....)</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af; transition: transform 0.2s ease;" class="dropdown-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="dropdown-menu w-100 shadow-lg" aria-labelledby="toppingDropdown" style="border-radius: 12px; border: 1px solid #f3f4f6; max-height: 280px; overflow-y: auto; padding: 8px; margin-top: 8px;">
                        @foreach($toppings as $topping)
                        <li style="margin-bottom: 4px;">
                            <label class="dropdown-item d-flex justify-content-between align-items-center topping-item-label" style="cursor: pointer; padding: 12px 14px; border-radius: 8px; transition: all 0.2s ease;">
                                <div class="form-check m-0 d-flex align-items-center">
                                    <input class="form-check-input topping-checkbox custom-checkbox" type="checkbox" value="{{ $topping->id }}" data-topping-price="{{ $topping->price }}" data-topping-name="{{ $topping->name }}" onchange="handleToppingChange(this)" style="width: 20px; height: 20px; margin-top: 0; cursor: pointer; border: 1.5px solid #d1d5db; border-radius: 6px;">
                                    <span class="form-check-label ms-3" style="font-size: 15px; color: #374151; font-weight: 500; cursor: pointer;">
                                        {{ $topping->name }}
                                    </span>
                                </div>
                                <span style="font-size: 14px; font-weight: 600; color: #10b981; background: #ecfdf5; padding: 4px 8px; border-radius: 6px;">+{{ number_format($topping->price, 0, ',', '.') }}đ</span>
                            </label>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            {{-- Quantity + Add to Cart --}}
            <div class="pd-actions">
                <div class="pd-qty">
                    <button class="pd-qty__btn" id="pd-qty-minus" onclick="changeQty(-1)" aria-label="Giảm">−</button>
                    <span class="pd-qty__val" id="pd-qty-val">1</span>
                    <button class="pd-qty__btn" id="pd-qty-plus" onclick="changeQty(1)" aria-label="Tăng">+</button>
                </div>
                <button class="pd-add-cart-btn" id="pd-add-cart" onclick="addToCartFromDetail()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    Thêm vào giỏ hàng
                </button>
            </div>

            {{-- Delivery & Quality Badges --}}
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

    {{-- ===== PRODUCT DESCRIPTION ===== --}}
    @if($product->description)
    <div class="pd-desc-section">
        <h2 class="pd-section-title">Mô tả sản phẩm</h2>
        <div class="pd-desc-body">
            <p>{{ $product->description }}</p>
        </div>
    </div>
    @endif

    {{-- ===== REVIEWS ===== --}}
    <div class="pd-reviews-section">
        <h2 class="pd-section-title">Đánh giá từ khách hàng</h2>

        <div class="pd-reviews-summary">
            {{-- Score Overview --}}
            <div class="pd-reviews-score">
                <div class="pd-reviews-score__num">{{ number_format($product->avg_rating, 1) }}</div>
                <div class="pd-reviews-score__stars">
                    @for($i=1;$i<=5;$i++)
                        <svg class="pd-star {{ $i <= round($product->avg_rating) ? 'pd-star--filled' : 'pd-star--empty' }}" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                    @endfor
                </div>
                <div class="pd-reviews-score__total">{{ $product->review_count }} đánh giá</div>
            </div>

            {{-- Rating Bars --}}
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

        {{-- Review List --}}
        <div class="pd-review-list">
            @forelse($reviews as $review)
            <div class="pd-review-item">
                <div class="pd-review-avatar">
                    <span class="pd-review-avatar__initial">{{ mb_substr($review->user_name, 0, 1) }}</span>
                </div>
                <div class="pd-review-body">
                    <div class="pd-review-header">
                        <span class="pd-review-name">{{ $review->user_name }}</span>
                        <div class="pd-review-stars">
                            @for($i=1;$i<=5;$i++)
                                <svg class="pd-star pd-star--sm {{ $i <= $review->rating ? 'pd-star--filled' : 'pd-star--empty' }}" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                            @endfor
                        </div>
                        <span class="pd-review-date">{{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}</span>
                    </div>
                    @if($review->comment)
                    <p class="pd-review-comment">{{ $review->comment }}</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="pd-reviews-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <p>Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ===== RELATED PRODUCTS ===== --}}
    @if($relatedProducts->count() > 0)
    <div class="pd-related-section">
        <h2 class="pd-section-title">Sản phẩm tương tự</h2>
        <div class="pd-related-grid">
            @foreach($relatedProducts as $rel)
            <a href="{{ route('product.show', $rel->slug) }}" class="pd-rel-card">
                <div class="pd-rel-card__img-wrap">
                    <img src="{{ asset('images/' . $rel->image) }}"
                         alt="{{ $rel->name }}"
                         class="pd-rel-card__img"
                         onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>
                <div class="pd-rel-card__body">
                    <p class="pd-rel-card__name">{{ $rel->name }}</p>
                    <div class="pd-rel-card__stats">
                        <svg style="color:#f59e0b;width:12px;height:12px" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span>{{ number_format($rel->avg_rating, 1) }}</span>
                    </div>
                    <span class="pd-rel-card__price">{{ number_format($rel->base_price, 0, ',', '.') }}đ</span>
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

@push('scripts')
<script>
(function() {
    // ---- State ----
    let qty = 1;
    let basePrice = {{ $product->base_price }};
    let sizeAdj = 0;
    let toppingAdj = 0;
    const productId = {{ $product->id }};

    // ---- Price display ----
    function updatePrice() {
        const total = (basePrice + sizeAdj + toppingAdj) * qty;
        const formatted = total.toLocaleString('vi-VN') + 'đ';
        document.getElementById('pd-price').textContent = formatted;
    }

    // ---- Quantity ----
    window.changeQty = function(delta) {
        qty = Math.max(1, qty + delta);
        document.getElementById('pd-qty-val').textContent = qty;
        updatePrice();
        // animate
        const el = document.getElementById('pd-qty-val');
        el.classList.remove('pd-qty__val--bump');
        void el.offsetWidth;
        el.classList.add('pd-qty__val--bump');
    };

    // ---- Size selection ----
    window.selectSize = function(btn) {
        document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
        updatePrice();
    };

    // ---- Generic option (sugar, ice) ----
    window.selectOption = function(btn, groupId) {
        document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    };

    // ---- Topping toggle (Checkbox) ----
    window.handleToppingChange = function(inputEl = null) {
        // Toggle selected background
        if (inputEl) {
            const labelEl = inputEl.closest('.topping-item-label');
            if (inputEl.checked) {
                labelEl.classList.add('is-selected');
            } else {
                labelEl.classList.remove('is-selected');
            }
        }

        toppingAdj = 0;
        let selectedNames = [];
        document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
            toppingAdj += parseFloat(cb.getAttribute('data-topping-price')) || 0;
            selectedNames.push(cb.getAttribute('data-topping-name'));
        });
        updatePrice();
        
        // Update summary text
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

    // Rotate chevron on dropdown open/close
    const toppingDropdownEl = document.getElementById('toppingDropdown');
    if (toppingDropdownEl) {
        toppingDropdownEl.addEventListener('show.bs.dropdown', event => {
            toppingDropdownEl.querySelector('.dropdown-chevron').style.transform = 'rotate(180deg)';
        });
        toppingDropdownEl.addEventListener('hide.bs.dropdown', event => {
            toppingDropdownEl.querySelector('.dropdown-chevron').style.transform = 'rotate(0deg)';
        });
    }

    // ---- Add to cart from detail page ----
    window.addToCartFromDetail = function() {
        const btn = document.getElementById('pd-add-cart');
        btn.disabled = true;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đang thêm...';

        // Đọc các tùy chọn đang active
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
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Thêm vào giỏ hàng';
        }, 1200);
    };

    // ---- Image gallery ----
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

    // Init
    updatePrice();
})();
</script>
@endpush
@endsection
