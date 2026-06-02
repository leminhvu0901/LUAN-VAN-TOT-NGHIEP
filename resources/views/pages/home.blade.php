@extends('layouts.app')

@section('content')

        {{-- ===== HERO BANNER ===== --}}
        <section class="home-hero">
            <div class="home-hero__inner" id="hero-slider">
                @if(isset($banners) && $banners->count() > 0)
                    @foreach($banners as $index => $banner)
                        <img src="{{ asset($banner->image_url) }}" class="home-hero__img hero-slide-img {{ $index === 0 ? 'active' : '' }}" data-title="{{ $banner->title }}" alt="{{ $banner->title ?? 'Banner' }}">
                    @endforeach
                @else
                    <img src="{{ asset('images/slider/slider-1.png') }}" class="home-hero__img hero-slide-img active" data-title="Thưởng thức hương vị tuyệt vời" alt="Banner 1">
                    <img src="{{ asset('images/slider/slider-2.png') }}" class="home-hero__img hero-slide-img" data-title="Khuyến mãi mùa hè" alt="Banner 2">
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
                        <a href="#popular" class="home-hero__btn home-hero__btn--ghost">Xem menu</a>
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
                <a href="/categories" class="home-section__link">Xem tất cả →</a>
            </div>

            <div class="home-categories">
                <a href="/products?category=ca-phe" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: #dcfce7; --cat-color: #15803d;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 4h10v7a5 5 0 0 1-5 5h0a5 5 0 0 1-5-5V4z"></path>
                            <path d="M16 7h1.5a2.5 2.5 0 0 1 2.5 2.5v0a2.5 2.5 0 0 1-2.5 2.5H16"></path>
                            <line x1="6" y1="9" x2="16" y2="9"></line>
                            <line x1="5" y1="19" x2="19" y2="19"></line>
                        </svg>
                    </div>
                    <span class="home-cat-card__name">Cà phê</span>
                    <span class="home-cat-card__count">12 món</span>
                </a>

                <a href="/products?category=tra-sua" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: #ede9fe; --cat-color: #6d28d9;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="6" width="13" height="11" rx="2" ry="2"></rect>
                            <path d="M9 10l4.5 4.5"></path>
                            <path d="M13.5 10v4.5H9"></path>
                            <circle cx="19" cy="18" r="2.5" fill="currentColor" stroke="none"></circle>
                        </svg>
                    </div>
                    <span class="home-cat-card__name">Trà sữa</span>
                    <span class="home-cat-card__count">18 món</span>
                </a>

                <a href="/products?category=tra-trai-cay" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: #ffedd5; --cat-color: #c2410c;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            <path d="M9 12a3 3 0 0 0 3 3"></path>
                        </svg>
                    </div>
                    <span class="home-cat-card__name">Trà trái cây</span>
                    <span class="home-cat-card__count">10 món</span>
                </a>

                <a href="/products?category=da-xay" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: #dbeafe; --cat-color: #1d4ed8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2h8l1 8H7L8 2z"></path>
                            <path d="M7 10l1 10h8l1-10"></path>
                            <line x1="12" y1="6" x2="12" y2="10"></line>
                        </svg>
                    </div>
                    <span class="home-cat-card__name">Đá xay</span>
                    <span class="home-cat-card__count">8 món</span>
                </a>

                <a href="/products?category=banh-ngot" class="home-cat-card">
                    <div class="home-cat-card__icon" style="--cat-bg: #fce7f3; --cat-color: #be185d;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6l-9 6v-5l9-6v5z"></path>
                            <path d="M9 12v5l9 6v-5z"></path>
                            <path d="M18 18v-5"></path>
                        </svg>
                    </div>
                    <span class="home-cat-card__name">Bánh ngọt</span>
                    <span class="home-cat-card__count">6 món</span>
                </a>
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
                {{-- sp1 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                        <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" class="home-prod-card__img"
                            alt="Cà phê sữa đá">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Cà phê sữa đá</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★★</span>
                            <span class="home-prod-card__rating-val">4.8</span>
                            <span class="home-prod-card__reviews">(120+)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <span class="home-prod-card__price">29.000đ</span>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp2 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                        <img src="{{ asset('images/products/tra-dao.jpg') }}" class="home-prod-card__img"
                            alt="Trà đào miếng">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Trà đào miếng</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★★</span>
                            <span class="home-prod-card__rating-val">4.9</span>
                            <span class="home-prod-card__reviews">(85+)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <span class="home-prod-card__price">35.000đ</span>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp3 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--sale">-15%</span>
                        <img src="{{ asset('images/products/ca-phe-kem-sua.jpg') }}" class="home-prod-card__img"
                            alt="Cookie Đá Xay">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Cookie Đá Xay</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★☆</span>
                            <span class="home-prod-card__rating-val">4.6</span>
                            <span class="home-prod-card__reviews">(92)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <div>
                                <span class="home-prod-card__price">42.000đ</span>
                                <span class="home-prod-card__price-old">49.000đ</span>
                            </div>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp4 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                        <img src="{{ asset('images/products/matcha-latte.jpg') }}" class="home-prod-card__img"
                            alt="Matcha Latte">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Matcha Latte</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★★</span>
                            <span class="home-prod-card__rating-val">4.7</span>
                            <span class="home-prod-card__reviews">(54+)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <span class="home-prod-card__price">39.000đ</span>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp5 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--new">✨ Mới</span>
                        <img src="{{ asset('images/products/ca-phe-den-da.jpg') }}" class="home-prod-card__img"
                            alt="Cà phê đen đá">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Cà phê đen đá</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★★</span>
                            <span class="home-prod-card__rating-val">4.8</span>
                            <span class="home-prod-card__reviews">(120+)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <span class="home-prod-card__price">28.000đ</span>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp6 --}}
                <div class="home-prod-card">
                    <div class="home-prod-card__img-wrap">
                        <span class="home-prod-card__badge home-prod-card__badge--sale">-15%</span>
                        <img src="{{ asset('images/products/sua-chua-dau.jpg') }}" class="home-prod-card__img"
                            alt="Sữa chua dâu">
                        <button class="home-prod-card__wishlist" aria-label="Yêu thích">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </button>
                    </div>
                    <div class="home-prod-card__body">
                        <h3 class="home-prod-card__name">Sữa chua dâu</h3>
                        <div class="home-prod-card__rating">
                            <span class="home-prod-card__stars">★★★★☆</span>
                            <span class="home-prod-card__rating-val">4.6</span>
                            <span class="home-prod-card__reviews">(92)</span>
                        </div>
                        <div class="home-prod-card__footer">
                            <div>
                                <span class="home-prod-card__price">45.000đ</span>
                                <span class="home-prod-card__price-old">53.000đ</span>
                            </div>
                            <button class="home-prod-card__add-btn" aria-label="Thêm vào giỏ hàng">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="home-popular__view-all">
                <a href="/products" class="home-popular__view-all-btn">
                    Xem tất cả sản phẩm
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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