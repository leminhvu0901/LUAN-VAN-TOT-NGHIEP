@extends('layouts.app')

@section('content')

    {{-- ===== HERO BANNER ===== --}}
    <section class="home-hero">
        <div class="home-hero__inner" id="hero-slider">
            @if(isset($banners) && $banners->count() > 0)
                @foreach($banners as $index => $banner)
                    <img src="{{ asset($banner->image_url) }}"
                        class="home-hero__img hero-slide-img {{ $index === 0 ? 'active' : '' }}" data-title="{{ $banner->title }}"
                        alt="{{ $banner->title ?? 'Banner' }}">
                @endforeach
            @else
                <img src="{{ asset('images/slider/slider-1.png') }}" class="home-hero__img hero-slide-img active"
                    data-title="Thưởng thức hương vị tuyệt vời" alt="Banner 1">
                <img src="{{ asset('images/slider/slider-2.png') }}" class="home-hero__img hero-slide-img"
                    data-title="Khuyến mãi mùa hè" alt="Banner 2">
            @endif
            <div class="home-hero__overlay"></div>
            <div class="home-hero__content">
                <span class="home-hero__tag">🌿 Đồ uống tươi ngon</span>
                <h1 class="home-hero__title" id="hero-title">
                    {{ isset($banners) && $banners->count() > 0 ? $banners->first()->title : 'Thưởng thức hương vị tuyệt vời' }}
                </h1>
                <p class="home-hero__desc">Khám phá hơn 50+ món đồ uống thủ công, từ cà phê rang xay đến trà trái
                    cây tươi mát.</p>
                <div class="home-hero__actions">
                    <a href="/products" class="home-hero__btn home-hero__btn--primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
                        </svg>
                        Đặt ngay
                    </a>
                    <a href="/products" class="home-hero__btn home-hero__btn--ghost">Xem menu</a>
                </div>
            </div>
            <div class="home-hero__stats">
                <div class="home-hero__stat">
                    <span class="home-hero__stat-num">50+</span>
                    <span class="home-hero__stat-label">Món đồ uống</span>
                </div>
                <div class="home-hero__stat-divider"></div>
                <div class="home-hero__stat">
                    <span class="home-hero__stat-num">4.9★</span>
                    <span class="home-hero__stat-label">Đánh giá</span>
                </div>
                <div class="home-hero__stat-divider"></div>
                <div class="home-hero__stat">
                    <span class="home-hero__stat-num">30'</span>
                    <span class="home-hero__stat-label">Giao hàng</span>
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

        <div class="home-categories">
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
    </section>

    {{-- ===== SẢN PHẨM PHỔ BIẾN ===== --}}
    <section class="home-section home-popular container" id="popular">
        <div class="home-section__header">
            <div>
                <p class="home-section__subtitle">Được yêu thích nhất</p>
                <h2 class="home-section__title">Sản phẩm phổ biến</h2>
            </div>
            <div class="home-popular__filters">
                <button class="home-popular__filter-btn home-popular__filter-btn--active">Tất cả</button>
                <button class="home-popular__filter-btn">Bán chạy</button>
                <button class="home-popular__filter-btn">Mới nhất</button>
            </div>
        </div>

        <div class="home-products-grid">
            @php
                $popularProducts = \Illuminate\Support\Facades\DB::table('products')
                    ->select(
                        'products.*',
                        \Illuminate\Support\Facades\DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                        \Illuminate\Support\Facades\DB::raw('COUNT(reviews.id) as review_count'),
                        \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
                    )
                    ->leftJoin('reviews', function ($join) {
                        $join->on('products.id', '=', 'reviews.product_id')
                            ->where('reviews.is_visible', 1);
                    })
                    ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                    ->where('products.is_active', 1)
                    ->groupBy(
                        'products.id',
                        'products.sku',
                        'products.slug',
                        'products.name',
                        'products.base_price',
                        'products.image',
                        'products.description',
                        'products.is_active',
                        'products.category_id',
                        'products.created_at',
                        'products.updated_at'
                    )
                    ->orderByDesc('total_sold')
                    ->orderByDesc('products.created_at')
                    ->limit(6)
                    ->get();

                $userFavorites = [];
                if (Auth::check()) {
                    $userFavorites = \Illuminate\Support\Facades\DB::table('favorites')
                        ->where('user_id', Auth::id())
                        ->pluck('product_id')->toArray();
                }
            @endphp

            @foreach($popularProducts as $product)
                @php
                    $isHot = ($product->total_sold > 0); // Bán chạy nếu có đơn
                    $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15); // Tạo trong vòng 15 ngày
                @endphp
                <div class="home-prod-card" data-sold="{{ $product->total_sold }}" data-date="{{ strtotime($product->created_at) }}" data-original-order="{{ $loop->iteration }}" style="{{ $loop->iteration > 6 ? 'display: none;' : '' }}">
                    <div class="home-prod-card__img-wrap">
                        @if($isHot) 
                            <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span> 
                        @endif
                        @if($isNew) 
                            <span class="home-prod-card__badge home-prod-card__badge--new" style="{{ $isHot ? 'display: none;' : '' }}">✨ Mới</span> 
                        @endif

                        <img src="{{ asset('images/' . $product->image) }}" class="home-prod-card__img"
                            alt="{{ $product->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">

                        <button class="home-prod-card__wishlist {{ in_array($product->id, $userFavorites) ? 'is-active' : '' }}"
                            aria-label="Yêu thích" data-id="{{ $product->id }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">{{ $product->name }}</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★★</span>
                            <span class="home-prod-card__rating-val">{{ number_format($product->avg_rating, 1) }}</span>
                            <span class="home-prod-card__reviews">({{ $product->review_count }})</span>
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
                                onclick="addToCart({{ $product->id }})">
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

    {{-- ===== PROMO BANNER ===== --}}
    <section class="home-promo-section container" id="promo">
        <div class="home-promo">
            <div class="home-promo__deco home-promo__deco--1"></div>
            <div class="home-promo__deco home-promo__deco--2"></div>
            <div class="home-promo__body">
                <span class="home-promo__chip">🎉 Ưu đãi đặc biệt</span>
                <h2 class="home-promo__title">Giảm 20% cho đơn hàng đầu tiên</h2>
                <p class="home-promo__desc">Dùng mã <strong>HAPPY20</strong> khi thanh toán. Áp dụng cho tất cả sản
                    phẩm, không giới hạn số lượng.</p>
                <div class="home-promo__actions">
                    <a href="/products" class="home-promo__btn">Đặt hàng ngay</a>
                    <span class="home-promo__expire">⏰ Kết thúc sau 2 ngày</span>
                </div>
            </div>
            <div class="home-promo__visual">
                <div class="home-promo__emoji">☕</div>
                <div class="home-promo__emoji home-promo__emoji--2">🧋</div>
                <div class="home-promo__emoji home-promo__emoji--3">🍵</div>
            </div>
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

    <script src="{{ asset('js/home.js') }}"></script>
@endsection