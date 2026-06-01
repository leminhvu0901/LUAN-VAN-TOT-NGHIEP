<!doctype html>
<html lang="vi">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    @include('partials.head')

    <title>Trang chủ</title>
</head>

<body>
    {{-- navbar --}}
    @include('partials.navbar')
    <main>
        <section class="mt-0">
            <div class="l-hero-banner">
                <img src="{{ asset('images/slider/slider-2.png') }}" class="l-hero-banner-image" alt="Summer Banner">
            </div>
        </section>

        {{-- danh mục nổi bật --}}
        <section class="container" style="padding-top: 2rem;">
            <div class="l-section-header">
                <h2 class="l-section-title">Danh mục nổi bật</h2>
                <a href="/categories" class="l-section-link">XEM TẤT CẢ ></a>
            </div>

            <div class="l-category-list" style="gap: 1.5rem;">
                <a href="/products?category=ca-phe" class="l-category-item">
                    <div class="l-category-circle" style="background-color: #dcfce7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                            stroke="#15803d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 4h10v7a5 5 0 0 1-5 5h0a5 5 0 0 1-5-5V4z"></path>
                            <path d="M16 7h1.5a2.5 2.5 0 0 1 2.5 2.5v0a2.5 2.5 0 0 1-2.5 2.5H16"></path>
                            <line x1="6" y1="9" x2="16" y2="9"></line>
                            <line x1="5" y1="19" x2="19" y2="19"></line>
                        </svg>
                    </div>
                    <span class="l-category-title">Cà phê</span>
                </a>

                <a href="/products?category=tra-sua" class="l-category-item">
                    <div class="l-category-circle" style="background-color: #e5e7eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                            stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="6" width="13" height="11" rx="2" ry="2"></rect>
                            <path d="M9 10l4.5 4.5"></path>
                            <path d="M13.5 10v4.5H9"></path>
                            <circle cx="19" cy="18" r="2.5" fill="#374151" stroke="none"></circle>
                        </svg>
                    </div>
                    <span class="l-category-title">Trà sữa</span>
                </a>

                <a href="/products?category=tra-trai-cay" class="l-category-item">
                    <div class="l-category-circle" style="background-color: #ffedd5;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                            stroke="#431407" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            <path d="M9 12a3 3 0 0 0 3 3"></path>
                        </svg>
                    </div>
                    <span class="l-category-title">Trà trái cây</span>
                </a>

                <a href="/products?category=da-xay" class="l-category-item">
                    <div class="l-category-circle" style="background-color: #f3f4f6;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                            stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="3" x2="12" y2="21"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="5.6" y1="5.6" x2="18.4" y2="18.4"></line>
                            <line x1="18.4" y1="5.6" x2="5.6" y2="18.4"></line>
                            <path d="M10 5l2-2 2 2M10 19l2 2 2-2M5 10l-2 2 2 2M19 10l2 2-2 2"></path>
                        </svg>
                    </div>
                    <span class="l-category-title">Đá xay</span>
                </a>

                <a href="/products?category=banh-ngot" class="l-category-item">
                    <div class="l-category-circle" style="background-color: #dcfce7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                            stroke="#15803d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 13l13-7 2 4z"></path>
                            <path d="M4 13v5l13-7v-5"></path>
                            <path d="M17 11l2 4v-5"></path>
                            <path d="M4 15.5l13-7"></path>
                            <circle cx="14" cy="5" r="1.5" fill="#15803d" stroke="none"></circle>
                            <path d="M14 3.5c-1-1-2.5-1-2.5-1"></path>
                        </svg>
                    </div>
                    <span class="l-category-title">Bánh ngọt</span>
                </a>
            </div>
        </section>

        <!-- Sản phẩm phổ biến -->
        <section class="container" style="padding-top: 0.5rem;">
            <div class="l-section-header">
                <h2 class="l-section-title">Sản phẩm phổ biến</h2>
                <button class="l-filter-btn" aria-label="Lọc sản phẩm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="6" x2="20" y2="6"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                        <line x1="11" y1="18" x2="13" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="l-product-grid">
                {{-- sp1 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #4b5563;">BÁN CHẠY</span>
                        <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" class="l-product-image"
                            alt="Cà phê sữa đá">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Cà phê sữa đá</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.8 (120+)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">29.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp2 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #10b981;">MỚI</span>
                        <img src="{{ asset('images/products/tra-dao.jpg') }}" class="l-product-image"
                            alt="Trà đào miếng">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Trà đào miếng</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.9 (85+)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">35.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp3 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #92400e;">-15%</span>
                        <img src="{{ asset('images/products/ca-phe-kem-sua.jpg') }}" class="l-product-image"
                            alt="Cookie Đá Xay">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Cookie Đá Xay</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.6 (92)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">42.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp4 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #10b981;">MỚI</span>
                        <img src="{{ asset('images/products/matcha-latte.jpg') }}" class="l-product-image"
                            alt="Matcha Latte">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Matcha Latte</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.7 (54+)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">39.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp5 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #10b981;">MỚI</span>
                        <img src="{{ asset('images/products/ca-phe-den-da.jpg') }}" class="l-product-image"
                            alt="Bơ Sáp Mắt">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Bơ Sáp Mắt</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.8 (120+)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">28.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- sp6 --}}
                <div class="l-product-card">
                    <div class="l-product-image-wrapper">
                        <span class="l-product-badge" style="background-color: #92400e;">-15%</span>
                        <img src="{{ asset('images/products/sua-chua-dau.jpg') }}" class="l-product-image"
                            alt="Cookie Đá Xay">
                    </div>
                    <div class="l-product-info">
                        <h3 class="l-product-title">Cookie Đá Xay</h3>
                        <div class="l-product-rating">
                            <svg class="l-product-rating-star w-4 h-4" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <span>4.6 (92)</span>
                        </div>
                        <div class="l-product-price-row">
                            <span class="l-product-price">45.000đ</span>
                            <button class="l-add-to-cart-btn" aria-label="Thêm vào giỏ hàng">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </section>


        <section class="lg:my-14 my-8">
            <div class="container">
                <div class="flex flex-wrap gap-y-6">
                    <div class="md:w-1/2 lg:w-1/4 px-3">
                        <div class="flex flex-col gap-4">
                            <div class="inline-block"><img src="{{ asset('images/icons/clock.svg') }}" alt="" /></div>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-md">Mua đồ uống nhanh tiện lợi</h3>
                                <p>Đơn hàng của bạn sẽ được giao đến tận cửa nhanh chóng và chính xác.</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 lg:w-1/4 px-3">
                        <div class="flex flex-col gap-4">
                            <div class="inline-block"><img src="{{ asset('images/icons/gift.svg') }}" alt="" /></div>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-md">Giá tốt nhất & ưu đãi</h3>
                                <p>Giá hợp lý, kèm theo các ưu đãi hoàn tiền hấp dẫn. Nhận mức giá
                                    &
                                    ưu đãi tốt nhất.</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 lg:w-1/4 px-3">
                        <div class="flex flex-col gap-4">
                            <div class="inline-block"><img src="{{ asset('images/icons/package.svg') }}" alt="" /></div>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-md">Danh mục đa dạng</h3>
                                <p> Chọn từ hơn 50 sản phẩm đồ uống như trà sữa, cà phê, nước ép, trà trái cây, sinh tố,
                                    đá xay và nhiều thức uống hấp dẫn khác..</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 lg:w-1/4 px-3">
                        <div class="flex flex-col gap-4">
                            <div class="inline-block"><img src="{{ asset('images/icons/refresh-cw.svg') }}" alt="" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <h3 class="text-md">Đổi trả dễ dàng</h3>
                                <p>
                                    Nếu đơn hàng bị giao nhầm món hoặc thiếu sản phẩm, bạn có thể liên hệ ngay với chúng
                                    tôi để được hỗ trợ đổi trả hoặc hoàn tiền nhanh chóng.
                                    <a href="#!" class="text-green-600">chính sách</a>
                                    .
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @include('partials.footer')


    @include('partials.modal-product')
    @include('partials.scripts')
    @include('partials.login')
    @include('partials.register')
    @include('partials.forgot-password')

</body>

</html>