{{-- Kế thừa cấu trúc giao diện chính của toàn bộ trang web --}}
@extends('frontend.layouts.app')

@section('content')
    @php
        $discountInfo = $product->discount_info;
        $effectivePrice = $discountInfo ? $discountInfo['sale_price'] : $product->base_price;
    @endphp
    {{-- Chi tiết sản phẩm --}}
    <div class="pd-wrapper" data-product-id="{{ $product->id }}" data-base-price="{{ $effectivePrice }}">
        {{-- Breadcrumb --}}
        <nav class="pd-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Trang chủ</a>
            <span class="pd-breadcrumb__sep">›</span>
            <a href="/products">{{ $product->category_name }}</a>
            <span class="pd-breadcrumb__sep">›</span>
            <span class="pd-breadcrumb__current">{{ $product->name }}</span>
        </nav>

        {{-- Chi tiết sản phẩm --}}
        <div class="pd-main">
            {{-- Gallery hình ảnh sản phẩm --}}
            <div class="pd-gallery">
                <div class="pd-gallery__main">
                    {{-- Huy hiệu trạng thái Bán chạy, Sản phẩm mới hoặc Giảm giá --}}
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

                    {{-- Nút yêu thích --}}
                    <button class="pd-wishlist-btn {{ $isFavorite ? 'is-active' : '' }}" id="pd-wishlist-btn"
                        onclick="toggleFavorite(this, {{ $product->id }})" aria-label="Yêu thích">
                        <i class="fa-solid fa-heart heart-icon"></i>
                    </button>

                    {{-- Ảnh lớn hiển thị sản phẩm chính --}}
                    <img id="pd-main-img" src="{{ $product->image_url }}" alt="{{ $product->name }}" class="pd-gallery__img"
                        onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                </div>

                {{-- Các ảnh thu nhỏ bổ sung ở phía dưới ảnh chính --}}
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

            {{-- Bên PHẢI: Thông tin chi tiết sản phẩm --}}
            <div class="pd-info">
                {{-- Tên sản phẩm --}}
                <h1 class="pd-info__name">{{ $product->name }}</h1>

                {{-- Dòng hiển thị đánh giá sao và lượt mua --}}
                <div class="pd-info__rating-row">
                    <div class="pd-stars">
                        {{-- Tính toán làm tròn điểm số để đổ màu ngôi sao --}}
                        @php $avgR = round($product->avg_rating * 2) / 2; @endphp
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= floor($avgR))
                                <i class="fa-solid fa-star text-sm" style="color: #f59e0b;"></i>
                            @elseif($i == ceil($avgR) && $avgR != floor($avgR))
                                <i class="fa-solid fa-star-half-stroke text-sm" style="color: #f59e0b;"></i>
                            @else
                                <i class="fa-solid fa-star text-sm" style="color: #d1d5db;"></i>
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

                {{-- Lựa chọn Kích cỡ --}}
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

                {{-- Tùy chọn thêm Toppings --}}
                @if ($toppings->count() > 0 && mb_stripos($product->category_name, 'cà phê') === false)
                    <div class="pd-option-group">
                        <div class="pd-option-label">THÊM TOPPING (KHÔNG BẮT BUỘC)</div>
                        <div class="dropdown">
                            <button class="btn w-100 text-start topping-dropdown-btn pd-topping-dropdown-btn"
                                type="button" id="toppingDropdown">
                                <span id="topping-summary" class="pd-topping-summary">Chọn topping (không bắt
                                    buộc....)</span>
                                <i class="fa-solid fa-chevron-down dropdown-chevron pd-dropdown-chevron text-xs"></i>
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
                            <i class="fa-solid fa-cart-shopping mr-2"></i>
                            Thêm vào giỏ hàng
                        </button>
                    @else
                        <button class="pd-add-cart-btn pd-add-cart-btn--sold-out" id="pd-add-cart" disabled>
                            <i class="fa-solid fa-ban mr-2"></i>
                            Hết hàng
                        </button>
                    @endif
                </div>

                {{-- Nhãn đảm bảo chất lượng và thời gian giao hàng --}}
                <div class="pd-badges-row">
                    <div class="pd-badge-item">
                        <i class="fa-solid fa-truck-fast text-emerald-600 text-xl"></i>
                        <div>
                            <div class="pd-badge-item__title">Giao hàng</div>
                            <div class="pd-badge-item__sub">Giao sớm nhất có thể</div>
                        </div>
                    </div>
                    <div class="pd-badge-item">
                        <i class="fa-solid fa-shield-halved text-emerald-600 text-xl"></i>
                        <div>
                            <div class="pd-badge-item__title">Chất lượng</div>
                            <div class="pd-badge-item__sub">Nguyên liệu sạch</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mô tả chi tiết sản phẩm --}}
        @if ($product->description)
            <div class="pd-desc-section">
                <h2 class="pd-section-title">Mô tả sản phẩm</h2>
                <div class="pd-desc-body" style="word-wrap: break-word; overflow-wrap: break-word; hyphens: auto;">
                    <p>{{ $product->description }}</p>
                </div>
            </div>
        @endif

        {{-- Đánh giá từ khách hàng --}}
        <div class="pd-reviews-section" id="reviews-section">
            <h2 class="pd-section-title">Đánh giá từ khách hàng</h2>

            {{-- Đánh giá của khách hàng --}}
            <div class="pd-reviews-summary">
                <div class="pd-reviews-score">
                    <div class="pd-reviews-score__num">{{ number_format($product->avg_rating, 1) }}</div>
                    <div class="pd-reviews-score__stars">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star text-sm" style="color: {{ $i <= round($product->avg_rating) ? '#f59e0b' : '#d1d5db' }};"></i>
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

            {{-- Đánh giá của khách hàng --}}
            <div class="reviews-app">
                <div class="pd-review-filters">
                    <a href="{{ route('product.show', $product->slug) }}#reviews-section"
                        class="pd-review-filter-btn review-filter-btn {{ !request('rating') && !request('has_image') ? 'is-active' : '' }}">Tất
                        cả</a>
                    @for ($star = 5; $star >= 1; $star--)
                        <a href="{{ route('product.show', $product->slug) }}?rating={{ $star }}#reviews-section"
                            class="pd-review-filter-btn review-filter-btn {{ request('rating') == $star ? 'is-active' : '' }}">{{ $star }} sao ({{ $ratingDistribution[$star] ?? 0 }})</a>
                    @endfor
                    <a href="{{ route('product.show', $product->slug) }}?has_image=1#reviews-section"
                        class="pd-review-filter-btn review-filter-btn {{ request('has_image') ? 'is-active' : '' }}">Có hình ảnh ({{ $hasImageCount }})</a>
                </div>

                <div class="pd-review-list">
                    @include('frontend.products.partials.reviews-list-full', [
                        'reviews' => $reviews,
                        'isFiltered' => $isFiltered,
                    ])
                </div>
            </div>
        </div>

        {{-- Sản phẩm tương tự --}}
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
                                {{-- Nhãn trạng thái hot, new hoặc discount, ưu tiên --}}
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
                                    <i class="fa-solid fa-star text-amber-400 text-xs"></i>
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
                                    <i class="fa-solid fa-plus text-xs"></i>
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
            // Tính giá đơn vị theo lựa chọn size và topping
            (function() {
                let qty = 1;
                const wrapper = document.querySelector('.pd-wrapper');
                let basePrice = wrapper ? (parseFloat(wrapper.getAttribute('data-base-price')) || 0) : 0;
                let sizeAdj = 0;
                let toppingAdj = 0;
                const productId = wrapper ? (parseInt(wrapper.getAttribute('data-product-id')) || 0) : 0;

                // Tính lại giá 1 ly theo công thức: giá gốc + phụ phí size + tổng phụ phí topping
                function updatePrice() {
                    const unitPrice = basePrice + sizeAdj + toppingAdj;
                    document.getElementById('pd-price').textContent = unitPrice.toLocaleString('vi-VN') + 'đ';
                }

                // Tăng/giảm số lượng ly muốn mua, nút +/-, không cho xuống dưới 1; kèm hiệu ứng "nảy
                window.changeQty = function(delta) {
                    qty = Math.max(1, qty + delta);
                    document.getElementById('pd-qty-val').textContent = qty;
                    updatePrice();

                    const el = document.getElementById('pd-qty-val');
                    el.classList.remove('pd-qty__val--bump');
                    void el.offsetWidth;
                    el.classList.add('pd-qty__val--bump');
                };

                // Chọn 1 size thì đổi nút đang active, đọc phụ phí từ data-price-adj rồi tính lại giá hiển thị
                window.selectSize = function(btn) {
                    document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');

                    sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
                    updatePrice();
                };

                // Hàm dùng chung cho nhóm lựa chọn không ảnh hưởng giá như mức đường và mức đá
                window.selectOption = function(btn, groupId) {
                    document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove(
                    'is-active'));
                    btn.classList.add('is-active');
                };

                // Tích hoặc bỏ tích topping thì cộng dồn lại tổng phụ phí, tính lại giá và cập nhật dòng tóm tắt
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

                // Đóng/mở menu thả xuống danh sách Topping dạng dropdown tùy biến, không dùng <select>
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

                // Thêm sản phẩm vào giỏ hàng
                window.addToCartFromDetail = function() {
                    const btn = document.getElementById('pd-add-cart');
                                    // Tạm khóa nút để tránh bấm nhiều lần gây thêm trùng sản phẩm khi mạng chậm
                    btn.disabled = true;
                    btn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang thêm...';

                    // Đọc lại lựa chọn hiện tại của khách trực tiếp từ DOM
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
                            '<i class="fa-solid fa-cart-shopping mr-2"></i> Thêm vào giỏ hàng';
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
