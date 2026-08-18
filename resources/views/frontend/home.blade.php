@extends('frontend.layouts.app')

{{-- Chừa khoảng trống phía dưới trên mobile để nội --}}
@section('body_class', 'has-mobile-bottom-nav')

@section('content')

    {{-- Dựng URL ảnh banner --}}
    @php
        if (!function_exists('getBannerUrl')) {
            function getBannerUrl($path)
            {
                return upload_url($path) ?? '';
            }
        }
    @endphp

    <section class="hero-banner home-hero">
        <div class="hero-banner__inner home-hero__inner" id="hero-slider">
            @if (isset($banners) && $banners->count() > 0)
                @foreach ($banners as $index => $banner)
                    <picture class="hero-banner__media hero-slide-img {{ $index === 0 ? 'active' : '' }}"
                        data-title="{{ $banner->title }}" data-title-tag="{{ $banner->title_tag }}"
                        data-link="{{ $banner->link_url }}">
                        @if (!empty($banner->mobile_image_url))
                            <source media="(max-width: 640px)" srcset="{{ getBannerUrl($banner->mobile_image_url) }}">
                        @endif
                        <img src="{{ getBannerUrl($banner->image_url) }}" alt="{{ $banner->title ?? 'Banner' }}"
                            onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-1.png') }}'">
                    </picture>
                @endforeach
                @if (isset($banners) && $banners->count() > 0)
                    @php
                        $firstBanner = $banners->first();
                        $firstBannerUrl = getBannerUrl($firstBanner->image_url);
                        $firstBannerMobileUrl = $firstBanner->mobile_image_url
                            ? getBannerUrl($firstBanner->mobile_image_url)
                            : $firstBannerUrl;
                    @endphp
                    <picture class="hero-banner__tracker home-hero__tracker">
                        @if (!empty($firstBanner->mobile_image_url))
                            <source media="(max-width: 640px)" srcset="{{ $firstBannerMobileUrl }}">
                        @endif
                        <img src="{{ $firstBannerUrl }}" alt="">
                    </picture>
                @endif
            @else
                <picture class="hero-banner__media hero-slide-img active" data-title="Thưởng thức hương vị tuyệt vời"
                    data-title-tag="🌿 Đồ uống tươi ngon">
                    <img src="{{ asset('images/banners/slider-1.png') }}" alt="Banner 1"
                        onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-1.png') }}'">
                </picture>
                <picture class="hero-banner__media hero-slide-img" data-title="Khuyến mãi mùa hè"
                    data-title-tag="🌿 Đồ uống tươi ngon">
                    <img src="{{ asset('images/banners/slider-2.png') }}" alt="Banner 2"
                        onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-2.png') }}'">
                </picture>
                <picture class="hero-banner__tracker home-hero__tracker">
                    <img src="{{ asset('images/banners/slider-1.png') }}" alt="">
                </picture>
            @endif

            {{-- Navigation Dots --}}
            @if (isset($banners) && $banners->count() > 1)
                <div class="hero-banner__dots">
                    @foreach ($banners as $index => $banner)
                        <button class="hero-banner__dot {{ $index === 0 ? 'active' : '' }}"
                            data-slide-index="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @elseif(!isset($banners) || $banners->count() === 0)
                <div class="hero-banner__dots">
                    <button class="hero-banner__dot active" data-slide-index="0" aria-label="Slide 1"></button>
                    <button class="hero-banner__dot" data-slide-index="1" aria-label="Slide 2"></button>
                </div>
            @endif

            <div class="hero-banner__overlay home-hero__overlay"></div>
            <div class="hero-banner__content home-hero__content">
                <div class="hero-banner__content-inner home-hero__content-inner">
                    @php
                        $firstBanner = isset($banners) && $banners->count() > 0 ? $banners->first() : null;
                        $heroTitleTag = $firstBanner ? $firstBanner->title_tag : '🌿 Đồ uống tươi ngon';
                        $heroTitle = $firstBanner ? $firstBanner->title : 'Thưởng thức hương vị tuyệt vời';
                        $heroLink = $firstBanner ? $firstBanner->link_url : '/products';
                    @endphp
                    <div class="hero-banner__top home-hero__top" id="hero-tag-container"
                        style="{{ empty($heroTitleTag) ? 'display: none;' : '' }}">
                        <span class="hero-banner__badge home-hero__tag" id="hero-title-tag">{{ $heroTitleTag }}</span>
                    </div>

                    <div class="hero-banner__middle home-hero__middle">
                        <h1 class="hero-banner__title home-hero__title" id="hero-title">
                            {{ $heroTitle }}
                        </h1>
                    </div>

                    <div class="hero-banner__bottom home-hero__bottom">
                        <div class="hero-banner__actions home-hero__actions">
                            <a href="{{ $heroLink ?: '/products' }}"
                                class="hero-banner__btn hero-banner__btn--primary home-hero__btn home-hero__btn--primary">
                                <i class="fa-solid fa-cart-shopping"></i>
                                Đặt ngay
                            </a>
                            <a href="/products"
                                class="hero-banner__btn hero-banner__btn--ghost home-hero__btn home-hero__btn--ghost">Xem
                                menu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-banner__stats home-hero__stats" style="z-index: 10;">
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">{{ $productCount }}+</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Món đồ uống</span>
                </div>
                <div class="hero-banner__stat-divider home-hero__stat-divider"></div>
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">{{ number_format($avgRating, 1) }}★</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Đánh giá</span>
                </div>
                <div class="hero-banner__stat-divider home-hero__stat-divider"></div>
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">{{ number_format($todayVisitCount) }}</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Lượt truy cập hôm nay</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Danh mục nổi bật --}}
    <section class="home-section container">
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Khám phá</p>
                <h2 class="home-section__title">Danh mục nổi bật</h2>
            </div>
            <!-- <a href="/categories" -->
        </div>

        <div class="home-categories" id="home-categories">
            @php
                // Map icons and colors for specific category IDs
                $catStyles = [
                    1 => [
                        'bg' => '#dcfce7',
                        'color' => '#15803d',
                        'icon' => 'fa-solid fa-mug-hot', // Cà phê
                    ],
                    2 => [
                        'bg' => '#ede9fe',
                        'color' => '#6d28d9',
                        'icon' => 'fa-solid fa-wine-glass', // Trà sữa
                    ],
                    4 => [
                        'bg' => '#ffedd5',
                        'color' => '#c2410c',
                        'icon' => 'fa-solid fa-lemon', // Nước ép / Sinh tố
                    ],
                    3 => [
                        'bg' => '#fce7f3',
                        'color' => '#be185d',
                        'icon' => 'fa-solid fa-bowl-food', // Sữa chua
                    ],
                    5 => [
                        'bg' => '#dbeafe',
                        'color' => '#1d4ed8',
                        'icon' => 'fa-solid fa-blender', // Đồ uống khác
                    ],
                ];
                $defaultStyle = [
                    'bg' => '#f3f4f6',
                    'color' => '#4b5563',
                    'icon' => 'fa-regular fa-circle-question',
                ];
            @endphp

            @foreach ($categories as $cat)
                @php
                    $style = $catStyles[$cat->id] ?? $defaultStyle;
                @endphp
                <a href="/products?category[]={{ $cat->id }}" class="home-cat-card">
                    <div class="home-cat-card__icon"
                        style="--cat-bg: {{ $style['bg'] }}; --cat-color: {{ $style['color'] }};">
                        <i class="{{ $style['icon'] }}"></i>
                    </div>
                    <span class="home-cat-card__name">{{ $cat->name }}</span>
                    <span class="home-cat-card__count">{{ $cat->product_count }} món</span>
                </a>
            @endforeach
        </div>

        {{-- Thanh chỉ báo vị trí cuộn ngang --}}
        <div class="home-categories-scrollbar" id="home-categories-scrollbar" aria-hidden="true">
            <div class="home-categories-scrollbar__thumb" id="home-categories-scrollbar-thumb"></div>
        </div>
    </section>

    {{-- Sản phẩm phổ biến --}}
    <section class="home-section home-popular container" id="popular">
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Được yêu thích nhất</p>
                <h2 class="home-section__title">Sản phẩm phổ biến</h2>
            </div>
            <div class="home-popular__filters" id="home-pill-filters">
                <button class="home-popular__filter-btn home-popular__filter-btn--active" data-filter="all">Tất
                    cả</button>
                <button class="home-popular__filter-btn" data-filter="hot">Bán chạy</button>
                <button class="home-popular__filter-btn" data-filter="new">Mới nhất</button>
                <button class="home-popular__filter-btn" data-filter="sale">Giảm giá</button>
            </div>
        </div>

        {{-- Ds san pham pho bien --}}
        <div class="home-products-grid">
            @php
                $popularProducts = \App\Models\Product::query()
                    ->select(
                        'products.*',
                        \Illuminate\Support\Facades\DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(r.review_count, 0) as review_count'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(o.total_sold, 0) as total_sold'),
                        \Illuminate\Support\Facades\DB::raw(
                            '(COALESCE(o.total_sold, 0) * 0.6 + COALESCE(r.avg_rating, 0) * 10 * 0.4) as score',
                        ),
                    )
                    ->leftJoin(
                        \Illuminate\Support\Facades\DB::raw(
                            '(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r',
                        ),
                        'products.id',
                        '=',
                        'r.product_id',
                    )
                    ->leftJoin(
                        \Illuminate\Support\Facades\DB::raw(
                            '(SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id) as o',
                        ),
                        'products.id',
                        '=',
                        'o.product_id',
                    )
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('categories.is_active', 1)
                    ->orderByDesc('products.is_active')
                    ->orderByDesc('score')
                    ->get();

                $userFavorites = [];
                if (Auth::check()) {
                    $userFavorites = \App\Models\Favorite::query()
                        ->where('user_id', Auth::id())
                        ->pluck('product_id')
                        ->toArray();
                }

                $top6HotProductIds = \App\Models\OrderItem::query()
                    ->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_sold'))
                    ->groupBy('product_id')
                    ->orderByDesc('total_sold')
                    ->limit(6)
                    ->pluck('product_id')
                    ->toArray();
            @endphp

            @foreach ($popularProducts as $product)
                @php
                    $isHot = in_array($product->id, $top6HotProductIds); // Nằm trong top 6 bán chạy
                    $isNew = \Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15; // Tạo trong vòng 15 ngày
                    $isOos = !$product->is_active; // Hết hàng khi is_active = 0
                    $discountInfo = $product->discount_info;
                @endphp
                <div class="home-prod-card {{ $isOos ? 'home-prod-card--out-of-stock' : '' }}"
                    data-sold="{{ $product->total_sold }}" data-date="{{ strtotime($product->created_at) }}"
                    data-original-order="{{ $loop->iteration }}" data-score="{{ round($product->score, 2) }}"
                    data-is-hot="{{ $isHot ? '1' : '0' }}" data-is-new="{{ $isNew ? '1' : '0' }}"
                    data-is-sale="{{ $discountInfo ? '1' : '0' }}">
                    <div class="home-prod-card__img-wrap"
                        @if (!$isOos) onclick="window.location.href='{{ route('product.show', $product->slug) }}'" style="cursor:pointer;" @else style="cursor:default;" @endif>
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

                        <img src="{{ $product->image_url }}" class="home-prod-card__img" alt="{{ $product->name }}"
                            onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">

                        <button
                            class="home-prod-card__wishlist {{ in_array($product->id, $userFavorites) ? 'is-active' : '' }}"
                            aria-label="Yêu thích" data-id="{{ $product->id }}"
                            onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <a href="{{ route('product.show', $product->slug) }}"
                            style="text-decoration: none; color: inherit;">
                            <h3 class="home-prod-card__name">{{ $product->name }}</h3>
                        </a>
                        <div class="p-product-stats"
                            style="display: flex; align-items: center; gap: 4px; margin-top: 0.35rem; margin-bottom: 0.5rem; font-size: 13px; color: #64748b;">
                            <i class="fa-solid fa-star home-prod-card__star"></i>
                            <span>{{ number_format($product->avg_rating, 1) }} </span>
                            <span style="color: #cbd5e1;">|</span>
                            <span>Đã bán
                                @if ($product->total_sold >= 1000)
                                    {{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}
                                @endif
                            </span>
                        </div>
                        <div class="home-prod-card__footer">
                            <div>
                                @if ($discountInfo)
                                    <span
                                        class="home-prod-card__price-old">{{ number_format($discountInfo['old_price'], 0, ',', '.') }}đ</span>
                                    <span
                                        class="home-prod-card__price">{{ number_format($discountInfo['sale_price'], 0, ',', '.') }}đ</span>
                                @else
                                    <span
                                        class="home-prod-card__price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                                @endif
                            </div>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng"
                                @if (!$isOos) onclick="addToCart({{ $product->id }})" @else disabled @endif>
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="home-popular__view-all">
            <a href="/products" class="home-popular__view-all-btn">
                Xem tất cả sản phẩm
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>

    {{-- Tại sao chọn chúng tôi --}}
    <section class="home-features container">
        
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Tại sao chọn chúng tôi</p>
                <h2 class="home-section__title">Trải nghiệm Happy khác biệt</h2>
            </div>
        </div>
        <div class="home-features__grid">
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--green">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h3 class="home-feat-card__title">Giao hàng nhanh</h3>
                <p class="home-feat-card__desc">Đơn hàng của bạn sẽ được giao đến tận cửa trong vòng 30 phút, nhanh
                    chóng và chính xác.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--yellow">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <h3 class="home-feat-card__title">Giá tốt & ưu đãi</h3>
                <p class="home-feat-card__desc">Giá hợp lý kèm theo các ưu đãi hoàn tiền hấp dẫn. Nhận mức giá tốt
                    nhất mỗi ngày.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--blue">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h3 class="home-feat-card__title">Đa dạng lựa chọn</h3>
                <p class="home-feat-card__desc">Chọn từ hơn 50 món đồ uống: trà sữa, cà phê, nước ép, sinh tố và
                    nhiều thức uống khác.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--red">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h3 class="home-feat-card__title">Đổi trả dễ dàng</h3>
                <p class="home-feat-card__desc">Giao nhầm món hay thiếu sản phẩm? Liên hệ ngay để được hỗ trợ đổi
                    trả hoặc hoàn tiền nhanh chóng.</p>
            </div>
        </div>
    </section>

    @include('frontend.components.bottom-nav')

    @push('scripts')
        <script>
            // Xử lý bộ lọc sản phẩm nổi bật trên trang chủ
            (function() {
                var pillButtons = document.querySelectorAll('#home-pill-filters .home-popular__filter-btn');
                var grid = document.querySelector('.home-products-grid');
                var currentFilter = 'all';

                // Lọc và sắp xếp thẻ sản phẩm theo tiêu chí đã chọn
                function applyHomeFilter() {
                    if (!grid) return;

                    var cards = Array.from(grid.querySelectorAll('.home-prod-card'));
                    var visibleCount = 0;

                    // Sắp xếp danh sách sản phẩm
                    cards.sort(function(a, b) {
                        if (currentFilter === 'hot') {
                            return parseInt(b.getAttribute('data-sold') || 0) - parseInt(a.getAttribute('data-sold') || 0);
                        } else if (currentFilter === 'new') {
                            return parseInt(b.getAttribute('data-date') || 0) - parseInt(a.getAttribute('data-date') || 0);
                        } else if (currentFilter === 'sale') {
                            return parseInt(b.getAttribute('data-is-sale') || 0) - parseInt(a.getAttribute('data-is-sale') || 0);
                        } else {
                            return parseFloat(b.getAttribute('data-score') || 0) - parseFloat(a.getAttribute('data-score') || 0);
                        }
                    });

                    // Cập nhật thứ tự và trạng thái hiển thị
                    var shown = 0;
                    cards.forEach(function(card) {
                        grid.appendChild(card);

                        var isMatch = true;
                        if (currentFilter === 'hot') {
                            isMatch = card.getAttribute('data-is-hot') === '1';
                        } else if (currentFilter === 'new') {
                            isMatch = card.getAttribute('data-is-new') === '1';
                        } else if (currentFilter === 'sale') {
                            isMatch = card.getAttribute('data-is-sale') === '1';
                        }

                        if (isMatch && shown < 7) {
                            card.style.display = '';
                            shown++;
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }

                        // Cập nhật huy hiệu trên từng thẻ sản phẩm
                        var hotBadge = card.querySelector('.home-prod-card__badge--hot');
                        var newBadge = card.querySelector('.home-prod-card__badge--new');
                        var saleBadge = card.querySelector('.home-prod-card__badge--sale');
                        if (currentFilter === 'new') {
                            if (hotBadge) hotBadge.style.display = 'none';
                            if (saleBadge) saleBadge.style.display = 'none';
                            if (newBadge) newBadge.style.display = '';
                        } else if (currentFilter === 'sale') {
                            if (hotBadge) hotBadge.style.display = 'none';
                            if (newBadge) newBadge.style.display = 'none';
                            if (saleBadge) saleBadge.style.display = '';
                        } else if (currentFilter === 'hot') {
                            if (saleBadge) saleBadge.style.display = 'none';
                            if (newBadge) newBadge.style.display = 'none';
                            if (hotBadge) hotBadge.style.display = '';
                        } else {
                            if (saleBadge) saleBadge.style.display = '';
                            if (hotBadge) hotBadge.style.display = saleBadge ? 'none' : '';
                            if (newBadge) newBadge.style.display = (saleBadge || hotBadge) ? 'none' : '';
                        }
                    });

                    // Hiển thị thông báo khi không có sản phẩm nào khớp
                    var emptyMsg = document.getElementById('home-empty-msg');
                    if (visibleCount === 0) {
                        if (!emptyMsg) {
                            emptyMsg = document.createElement('div');
                            emptyMsg.id = 'home-empty-msg';
                            emptyMsg.style.cssText =
                                'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;';
                            emptyMsg.textContent = 'Không có sản phẩm nào phù hợp.';
                            grid.appendChild(emptyMsg);
                        } else {
                            emptyMsg.style.display = '';
                            grid.appendChild(emptyMsg);
                        }
                    } else if (emptyMsg) {
                        emptyMsg.style.display = 'none';
                    }
                }

                // Gắn sự kiện click cho các nút chuyển đổi bộ lọc
                pillButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        pillButtons.forEach(function(b) {
                            b.classList.remove('home-popular__filter-btn--active');
                        });
                        this.classList.add('home-popular__filter-btn--active');
                        currentFilter = this.getAttribute('data-filter');
                        applyHomeFilter();
                    });
                });

                applyHomeFilter();
            })();

            // Đổi kiểu hiển thị thanh điều hướng khi cuộn trang
            const navbar = document.querySelector('.happy-navbar');
            if (navbar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 20) {
                        navbar.classList.add('navbar--scrolled');
                    } else {
                        navbar.classList.remove('navbar--scrolled');
                    }
                }, {
                    passive: true
                });
            }

            // Xử lý chuyển đổi banner slider trang chủ
            (function() {
                var sliderImgs = document.querySelectorAll('#hero-slider .hero-slide-img');
                var heroTitle = document.getElementById('hero-title');
                var dots = document.querySelectorAll('#hero-slider .hero-banner__dot');
                if (sliderImgs.length === 0) return;
                var currentIdx = 0;
                var slideInterval = null;

                // Chuyển tới một banner cụ thể trong slider
                function showSlide(nextIdx) {
                    if (nextIdx === currentIdx) return;
                    var prevIdx = currentIdx;

                    sliderImgs[prevIdx].classList.remove('active');
                    sliderImgs[prevIdx].classList.add('prev-slide');

                    currentIdx = nextIdx;

                    sliderImgs[currentIdx].classList.remove('prev-slide');
                    sliderImgs[currentIdx].classList.add('active');

                    dots.forEach(function(dot, idx) {
                        if (idx === currentIdx) {
                            dot.classList.add('active');
                        } else {
                            dot.classList.remove('active');
                        }
                    });

                    setTimeout(function() {
                        sliderImgs[prevIdx].classList.remove('prev-slide');
                    }, 800);

                    if (heroTitle) {
                        heroTitle.innerText = sliderImgs[currentIdx].dataset.title || '';
                    }

                    var heroTitleTag = document.getElementById('hero-title-tag');
                    var heroTagContainer = document.getElementById('hero-tag-container');
                    var titleTagText = sliderImgs[currentIdx].dataset.titleTag;
                    if (heroTitleTag && heroTagContainer) {
                        if (titleTagText && titleTagText.trim() !== '') {
                            heroTitleTag.innerText = titleTagText;
                            heroTagContainer.style.display = '';
                        } else {
                            heroTitleTag.innerText = '';
                            heroTagContainer.style.display = 'none';
                        }
                    }

                    var primaryBtn = document.querySelector('.home-hero__btn--primary');
                    if (primaryBtn) {
                        var slideLink = sliderImgs[currentIdx].dataset.link;
                        primaryBtn.href = (slideLink && slideLink.trim() !== '') ? slideLink : '/products';
                    }
                }

                // Hẹn giờ tự động chuyển banner tiếp theo
                function startAutoSlide() {
                    stopAutoSlide();
                    slideInterval = setInterval(function() {
                        var next = (currentIdx + 1) % sliderImgs.length;
                        showSlide(next);
                    }, 4000);
                }

                // Dừng tự động chuyển banner khi cần
                function stopAutoSlide() {
                    if (slideInterval) {
                        clearInterval(slideInterval);
                    }
                }

                // Gắn sự kiện click vào các dấu chấm chọn slide
                dots.forEach(function(dot) {
                    dot.addEventListener('click', function() {
                        var targetIdx = parseInt(this.getAttribute('data-slide-index'));
                        showSlide(targetIdx);
                        startAutoSlide();
                    });
                });

                startAutoSlide();
            })();

            // Xử lý thanh cuộn tùy biến danh mục sản phẩm
            (function() {
                var track = document.getElementById('home-categories');
                var scrollbar = document.getElementById('home-categories-scrollbar');
                var thumb = document.getElementById('home-categories-scrollbar-thumb');
                if (!track || !scrollbar || !thumb) return;

                // Cập nhật kích thước và vị trí thanh cuộn danh mục
                function updateThumb() {
                    var scrollableWidth = track.scrollWidth - track.clientWidth;

                    if (scrollableWidth <= 0) {
                        scrollbar.style.visibility = 'hidden';
                        return;
                    }
                    scrollbar.style.visibility = 'visible';

                    var thumbWidthPct = Math.max(15, Math.min(100, (track.clientWidth / track.scrollWidth) * 100));
                    var maxTranslatePct = 100 - thumbWidthPct;
                    var scrolledPct = (track.scrollLeft / scrollableWidth) * maxTranslatePct;

                    thumb.style.width = thumbWidthPct + '%';
                    thumb.style.transform = 'translateX(' + (scrolledPct / thumbWidthPct * 100) + '%)';
                }

                track.addEventListener('scroll', updateThumb, {
                    passive: true
                });
                window.addEventListener('resize', updateThumb);
                updateThumb();
            })();
        </script>
    @endpush
@endsection
