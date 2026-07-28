@extends('frontend.layouts.app')

{{-- Chừa khoảng trống phía dưới trên mobile để nội dung không bị thanh điều hướng dưới cùng che mất --}}
@section('body_class', 'has-mobile-bottom-nav')

@section('content')

    {{-- Helper function to safely resolve banner URL --}}
    @php
        if (!function_exists('getBannerUrl')) {
            function getBannerUrl($path) {
                if (empty($path)) return '';
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }
                if (file_exists(public_path($path))) {
                    return asset($path);
                }
                if (file_exists(public_path('storage/' . $path))) {
                    return asset('storage/' . $path);
                }
                if (strpos($path, 'banners/') === 0) {
                    return asset('images/banners/' . basename($path));
                }
                return asset($path);
            }
        }
    @endphp

    <section class="hero-banner home-hero">
        <div class="hero-banner__inner home-hero__inner" id="hero-slider">
            @if(isset($banners) && $banners->count() > 0)
                @foreach($banners as $index => $banner)
                    <picture class="hero-banner__media hero-slide-img {{ $index === 0 ? 'active' : '' }}" 
                        data-title="{{ $banner->title }}"
                        data-title-tag="{{ $banner->title_tag }}"
                        data-link="{{ $banner->link_url }}">
                        @if(!empty($banner->mobile_image_url))
                            <source media="(max-width: 640px)" srcset="{{ getBannerUrl($banner->mobile_image_url) }}">
                        @endif
                        <img src="{{ getBannerUrl($banner->image_url) }}"
                            alt="{{ $banner->title ?? 'Banner' }}"
                            onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-1.png') }}'">
                    </picture>
                @endforeach
                @if(isset($banners) && $banners->count() > 0)
                    @php
                        $firstBanner = $banners->first();
                        $firstBannerUrl = getBannerUrl($firstBanner->image_url);
                        $firstBannerMobileUrl = $firstBanner->mobile_image_url ? getBannerUrl($firstBanner->mobile_image_url) : $firstBannerUrl;
                    @endphp
                    <picture class="hero-banner__tracker home-hero__tracker">
                        @if(!empty($firstBanner->mobile_image_url))
                            <source media="(max-width: 640px)" srcset="{{ $firstBannerMobileUrl }}">
                        @endif
                        <img src="{{ $firstBannerUrl }}" alt="">
                    </picture>
                @endif
            @else
                <picture class="hero-banner__media hero-slide-img active"
                    data-title="Thưởng thức hương vị tuyệt vời" 
                    data-title-tag="🌿 Đồ uống tươi ngon">
                    <img src="{{ asset('images/banners/slider-1.png') }}" alt="Banner 1" onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-1.png') }}'">
                </picture>
                <picture class="hero-banner__media hero-slide-img"
                    data-title="Khuyến mãi mùa hè" 
                    data-title-tag="🌿 Đồ uống tươi ngon">
                    <img src="{{ asset('images/banners/slider-2.png') }}" alt="Banner 2" onerror="this.onerror=null; this.src='{{ asset('images/banners/slider-2.png') }}'">
                </picture>
                <picture class="hero-banner__tracker home-hero__tracker">
                    <img src="{{ asset('images/banners/slider-1.png') }}" alt="">
                </picture>
            @endif

            {{-- Navigation Dots --}}
            @if(isset($banners) && $banners->count() > 1)
                <div class="hero-banner__dots">
                    @foreach($banners as $index => $banner)
                        <button class="hero-banner__dot {{ $index === 0 ? 'active' : '' }}" data-slide-index="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
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
                    <div class="hero-banner__top home-hero__top" id="hero-tag-container" style="{{ empty($heroTitleTag) ? 'display: none;' : '' }}">
                        <span class="hero-banner__badge home-hero__tag" id="hero-title-tag">{{ $heroTitleTag }}</span>
                    </div>

                    <div class="hero-banner__middle home-hero__middle">
                        <h1 class="hero-banner__title home-hero__title" id="hero-title">
                            {{ $heroTitle }}
                        </h1>
                    </div>

                    <div class="hero-banner__bottom home-hero__bottom">
                        <div class="hero-banner__actions home-hero__actions">
                            <a href="{{ $heroLink ?: '/products' }}" class="hero-banner__btn hero-banner__btn--primary home-hero__btn home-hero__btn--primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                                </svg>
                                Đặt ngay
                            </a>
                            <a href="/products" class="hero-banner__btn hero-banner__btn--ghost home-hero__btn home-hero__btn--ghost">Xem menu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-banner__stats home-hero__stats" style="z-index: 10;">
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">50+</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Món đồ uống</span>
                </div>
                <div class="hero-banner__stat-divider home-hero__stat-divider"></div>
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">4.9★</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Đánh giá</span>
                </div>
                <div class="hero-banner__stat-divider home-hero__stat-divider"></div>
                <div class="hero-banner__stat home-hero__stat">
                    <span class="hero-banner__stat-num home-hero__stat-num">30'</span>
                    <span class="hero-banner__stat-label home-hero__stat-label">Giao hàng</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== DANH MỤC NỔI BẬT ===== --}}
    <section class="home-section container">
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Khám phá</p>
                <h2 class="home-section__title">Danh mục nổi bật</h2>
            </div>
            <!-- <a href="/categories" class="home-section__link">Xem tất cả →</a> -->
        </div>

        <div class="home-categories" id="home-categories">
            @php
                // Map icons and colors for specific category IDs (1: Cà phê, 2: Trà sữa, 3: Sữa chua, 4: Trà trái cây, 5: Đồ uống khác)
                $catStyles = [
                    1 => ['bg' => '#dcfce7', 'color' => '#15803d', 'icon' => '<path d="M6 4h10v7a5 5 0 0 1-5 5h0a5 5 0 0 1-5-5V4z"></path><path d="M16 7h1.5a2.5 2.5 0 0 1 2.5 2.5v0a2.5 2.5 0 0 1-2.5 2.5H16"></path><line x1="6" y1="9" x2="16" y2="9"></line><line x1="5" y1="19" x2="19" y2="19"></line>'],
                    2 => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'icon' => '<rect x="4" y="6" width="13" height="11" rx="2" ry="2"></rect><path d="M9 10l4.5 4.5"></path><path d="M13.5 10v4.5H9"></path><circle cx="19" cy="18" r="2.5" fill="currentColor" stroke="none"></circle>'],
                    4 => ['bg' => '#ffedd5', 'color' => '#c2410c', 'icon' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path><path d="M9 12a3 3 0 0 0 3 3"></path>'],
                    3 => ['bg' => '#fce7f3', 'color' => '#be185d', 'icon' => '<path d="M18 6l-9 6v-5l9-6v5z"></path><path d="M9 12v5l9 6v-5z"></path><path d="M18 18v-5"></path>'], // Sữa chua using cake icon for now
                    5 => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'icon' => '<path d="M8 2h8l1 8H7L8 2z"></path><path d="M7 10l1 10h8l1-10"></path><line x1="12" y1="6" x2="12" y2="10"></line>'], // Đồ uống khác using blender icon
                ];
                $defaultStyle = ['bg' => '#f3f4f6', 'color' => '#4b5563', 'icon' => '<circle cx="12" cy="12" r="10"></circle><path d="M12 8v4l3 3"></path>'];
            @endphp

            @foreach ($categories as $cat)
                @php
                    $style = $catStyles[$cat->id] ?? $defaultStyle;
                @endphp
                <a href="/products?category[]={{ $cat->id }}" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: {{ $style['bg'] }}; --cat-color: {{ $style['color'] }};">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            {!! $style['icon'] !!}
                        </svg>
                    </div>
                    <span class="home-cat-card__name">{{ $cat->name }}</span>
                    <span class="home-cat-card__count">{{ $cat->product_count }} món</span>
                </a>
            @endforeach
        </div>

        {{-- Thanh chỉ báo vị trí cuộn ngang — chỉ hiện trên mobile/tablet (xem CSS .home-categories-scrollbar,
        scoped trong @media max-width:1024px), vì .home-categories ẩn thanh cuộn gốc của trình duyệt
        (scrollbar-width:none) nên không còn gợi ý nào cho biết khối này kéo/vuốt được sang phải. --}}
        <div class="home-categories-scrollbar" id="home-categories-scrollbar" aria-hidden="true">
            <div class="home-categories-scrollbar__thumb" id="home-categories-scrollbar-thumb"></div>
        </div>
    </section>

    {{-- ===== SẢN PHẨM PHỔ BIẾN ===== --}}
    <section class="home-section home-popular container" id="popular">
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Được yêu thích nhất</p>
                <h2 class="home-section__title">Sản phẩm phổ biến</h2>
            </div>
            <div class="home-popular__filters" id="home-pill-filters">
                <button class="home-popular__filter-btn home-popular__filter-btn--active" data-filter="all">Tất cả</button>
                <button class="home-popular__filter-btn" data-filter="hot">Bán chạy</button>
                <button class="home-popular__filter-btn" data-filter="new">Mới nhất</button>
            </div>
        </div>

        <div class="home-products-grid">
            @php
                $popularProducts = \App\Models\Product::query()
                    ->select(
                        'products.*',
                        \Illuminate\Support\Facades\DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(r.review_count, 0) as review_count'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(o.total_sold, 0) as total_sold'),
                        \Illuminate\Support\Facades\DB::raw('(COALESCE(o.total_sold, 0) * 0.6 + COALESCE(r.avg_rating, 0) * 10 * 0.4) as score')
                    )
                    ->leftJoin(\Illuminate\Support\Facades\DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
                    ->leftJoin(\Illuminate\Support\Facades\DB::raw('(SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id) as o'), 'products.id', '=', 'o.product_id')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->where('categories.is_active', 1)
                    ->orderByDesc('products.is_active')
                    ->orderByDesc('score')
                    ->get();

                $userFavorites = [];
                if (Auth::check()) {
                    $userFavorites = \App\Models\Favorite::query()
                        ->where('user_id', Auth::id())
                        ->pluck('product_id')->toArray();
                }

                $top6HotProductIds = \App\Models\OrderItem::query()
                    ->select('product_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_sold'))
                    ->groupBy('product_id')
                    ->orderByDesc('total_sold')
                    ->limit(6)
                    ->pluck('product_id')->toArray();
            @endphp

            @foreach($popularProducts as $product)
                @php
                    $isHot = in_array($product->id, $top6HotProductIds); // Nằm trong top 6 bán chạy
                    $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15); // Tạo trong vòng 15 ngày
                    $isOos = !$product->is_active; // Hết hàng khi is_active = 0
                @endphp
                <div class="home-prod-card {{ $isOos ? 'home-prod-card--out-of-stock' : '' }}" data-sold="{{ $product->total_sold }}"
                    data-date="{{ strtotime($product->created_at) }}" data-original-order="{{ $loop->iteration }}"
                    data-score="{{ round($product->score, 2) }}" data-is-hot="{{ $isHot ? '1' : '0' }}"
                    data-is-new="{{ $isNew ? '1' : '0' }}">
                    <div class="home-prod-card__img-wrap"
                        @if(!$isOos) onclick="window.location.href='{{ route('product.show', $product->slug) }}'" style="cursor:pointer;" @else style="cursor:default;" @endif>
                        @if($isHot && !$isOos)
                            <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                        @endif
                        @if($isNew && !$isOos)
                            <span class="home-prod-card__badge home-prod-card__badge--new"
                                style="{{ $isHot ? 'display: none;' : '' }}">✨ Mới</span>
                        @endif
                        @if($isOos)
                            <span class="out-of-stock-overlay">Hết Hàng</span>
                        @endif

                        <img src="{{ $product->image_url }}" class="home-prod-card__img"
                            alt="{{ $product->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">

                        <button class="home-prod-card__wishlist {{ in_array($product->id, $userFavorites) ? 'is-active' : '' }}"
                            aria-label="Yêu thích" data-id="{{ $product->id }}"
                            onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <a href="{{ route('product.show', $product->slug) }}" style="text-decoration: none; color: inherit;">
                            <h3 class="home-prod-card__name">{{ $product->name }}</h3>
                        </a>
                        <div class="p-product-stats"
                            style="display: flex; align-items: center; gap: 4px; margin-top: 0.35rem; margin-bottom: 0.5rem; font-size: 13px; color: #64748b;">
                            <svg style="color: #f59e0b; width: 14px; height: 14px; flex-shrink: 0;"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>{{ number_format($product->avg_rating, 1) }} </span>
                            <span style="color: #cbd5e1;">|</span>
                            <span>Đã bán
                                @if($product->total_sold >= 1000){{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}@endif</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <div>
                                <span
                                    class="home-prod-card__price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                                @if($loop->iteration == 3 || $loop->iteration == 6)
                                    <span
                                        class="home-prod-card__price-old">{{ number_format($product->base_price * 1.15, 0, ',', '.') }}đ</span>
                                @endif
                            </div>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng"
                                @if(!$isOos) onclick="addToCart({{ $product->id }})" @else disabled @endif>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="home-popular__view-all">
            <a href="/products" class="home-popular__view-all-btn">
                Xem tất cả sản phẩm
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </a>
        </div>
    </section>


    {{-- ===== TẠI SAO CHỌN CHÚNG TÔI ===== --}}
    <section class="home-features container">
        <div class="home-section__header" style="margin-bottom: 2.5rem;">
            <div>
                <p class="home-section__subtitle">Tại sao chọn chúng tôi</p>
                <h2 class="home-section__title">Trải nghiệm Happy khác biệt</h2>
            </div>
        </div>
        <div class="home-features__grid">
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
                <h3 class="home-feat-card__title">Giao hàng nhanh</h3>
                <p class="home-feat-card__desc">Đơn hàng của bạn sẽ được giao đến tận cửa trong vòng 30 phút, nhanh
                    chóng và chính xác.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--yellow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 12 20 22 4 22 4 12" />
                        <rect x="2" y="7" width="20" height="5" />
                        <path d="M12 22V7" />
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                    </svg>
                </div>
                <h3 class="home-feat-card__title">Giá tốt & ưu đãi</h3>
                <p class="home-feat-card__desc">Giá hợp lý kèm theo các ưu đãi hoàn tiền hấp dẫn. Nhận mức giá tốt
                    nhất mỗi ngày.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </div>
                <h3 class="home-feat-card__title">Đa dạng lựa chọn</h3>
                <p class="home-feat-card__desc">Chọn từ hơn 50 món đồ uống: trà sữa, cà phê, nước ép, sinh tố và
                    nhiều thức uống khác.</p>
            </div>
            <div class="home-feat-card">
                <div class="home-feat-card__icon home-feat-card__icon--red">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10" />
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                    </svg>
                </div>
                <h3 class="home-feat-card__title">Đổi trả dễ dàng</h3>
                <p class="home-feat-card__desc">Giao nhầm món hay thiếu sản phẩm? Liên hệ ngay để được hỗ trợ đổi
                    trả hoặc hoàn tiền nhanh chóng.</p>
            </div>
        </div>
    </section>

    @include('frontend.components.bottom-nav')

    @push('scripts')
        <script src="{{ asset('js/frontend/home.js') }}"></script>
    @endpush
@endsection