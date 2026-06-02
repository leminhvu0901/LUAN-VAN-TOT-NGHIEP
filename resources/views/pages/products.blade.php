@extends('layouts.app')

@section('content')
        <div class="p-page-wrapper">
            <!-- Breadcrumb -->
            <nav class="p-breadcrumb" aria-label="Breadcrumb">
                <a href="/">Trang chủ</a>
                <span class="p-breadcrumb-sep">/</span>
                <span class="p-breadcrumb-current">Sản phẩm</span>
            </nav>

            <div class="p-main-layout">
                <!-- Sidebar Filters -->
                <aside class="p-sidebar">
                    <!-- Danh mục -->
                    <div class="p-filter-group">
                        <h3 class="p-filter-title">Bộ lọc</h3>
                        <label class="p-filter-item">
                            <input type="checkbox" id="cat-ca-phe" name="category" value="ca-phe">
                            <span>Cà phê</span>
                        </label>
                        <label class="p-filter-item">
                            <input type="checkbox" id="cat-tra-sua" name="category" value="tra-sua">
                            <span>Trà sữa</span>
                        </label>
                        <label class="p-filter-item">
                            <input type="checkbox" id="cat-tra-trai-cay" name="category" value="tra-trai-cay">
                            <span>Trà trái cây</span>
                        </label>
                        <label class="p-filter-item">
                            <input type="checkbox" id="cat-da-xay" name="category" value="da-xay">
                            <span>Đá xay</span>
                        </label>
                        <label class="p-filter-item">
                            <input type="checkbox" id="cat-banh-ngot" name="category" value="banh-ngot">
                            <span>Bánh ngọt</span>
                        </label>
                    </div>

                    <!-- Giá -->
                    <div class="p-filter-group">
                        <h3 class="p-filter-title">Giá</h3>
                        <div class="p-price-range-wrap">
                            <input type="range" class="p-price-slider" id="price-slider"
                                   min="10000" max="600000" value="390000"
                                   oninput="updatePriceLabel(this.value)">
                            <div class="p-price-label" id="price-label">100.000đ – 390.000đ</div>
                        </div>
                    </div>

                    <!-- Thái cây (Rating) -->
                    <div class="p-filter-group">
                        <h3 class="p-filter-title">Thái cây</h3>
                        <label class="p-rating-item">
                            <input type="checkbox" id="rating-46" name="rating" value="4.6">
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                            </span>
                            <span class="p-rating-label">4.6</span>
                        </label>
                        <label class="p-rating-item">
                            <input type="checkbox" id="rating-4" name="rating" value="4">
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">4.+</span>
                        </label>
                        <label class="p-rating-item">
                            <input type="checkbox" id="rating-0" name="rating" value="0">
                            <span class="p-rating-stars">
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">0</span>
                        </label>
                    </div>
                </aside>

                <!-- Product Area -->
                <div class="p-product-area">
                    <!-- Sort -->
                    <div class="p-sort-bar">
                        <select class="p-sort-select" id="sort-select" aria-label="Sắp xếp">
                            <option value="popular">Sắp xếp theo: Phổ biến nhất</option>
                            <option value="price-asc">Giá: Thấp đến cao</option>
                            <option value="price-desc">Giá: Cao đến thấp</option>
                            <option value="newest">Mới nhất</option>
                            <option value="rating">Đánh giá cao nhất</option>
                        </select>
                    </div>

                    <!-- Grid -->
                    <div class="p-product-grid" id="product-grid">

                        <!-- Row 1 -->
                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" alt="Cà phê sữa đá">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cà phê sữa đá</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.8 (120+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">29.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/tra-dao.jpg') }}" alt="Trà đào miếng">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Trà đào miếng</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.9 (85+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">35.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-sale">-15%</span>
                                <img src="{{ asset('images/products/ca-phe-kem-sua.jpg') }}" alt="Cookie Đá Xay">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cookie Đá Xay</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.6 (92)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">42.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/matcha-latte.jpg') }}" alt="Matcha Latte">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Matcha Latte</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.7 (54+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">39.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/ca-phe-den-da.jpg') }}" alt="Bơ Sáp Mắt">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Bơ Sáp Mắt</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.8 (120+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">28.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/matcha-latte.jpg') }}" alt="Bơ Sáp Mắt">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Bơ Sáp Mắt</p>
                                <p class="p-product-name-secondary">(Hơn)</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.8 (120+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">28.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-sale">-15%</span>
                                <img src="{{ asset('images/products/ca-phe-kem-sua.jpg') }}" alt="Cookie Đá Xay">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cookie Đá Xay</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.6 (92)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">42.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-sale">-15%</span>
                                <img src="{{ asset('images/products/ca-phe-den-da.jpg') }}" alt="Cà phê Đá Xay">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cà phê Đá Xay</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.7 (54+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">39.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <img src="{{ asset('images/products/sua-chua-dau.jpg') }}" alt="Cookie Đá Xay">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cookie Đá Xay</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.6 (92)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">45.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/tra-dao.jpg') }}" alt="Cà phê tơ hàn mắt">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cà phê tơ hàn mắt</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.7 (23+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">35.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <img src="{{ asset('images/products/ca-phe-sua-da.jpg') }}" alt="Cà phê sữa">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Cà phê sữa</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.5 (67)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">25.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/tra-tac.jpg') }}" alt="Trà tắc mật ong">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Trà tắc mật ong</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.4 (31+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">22.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-sale">-18%</span>
                                <img src="{{ asset('images/products/sua-chua-dau.jpg') }}" alt="Sữa chua dâu">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Sữa chua dâu</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.3 (48)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">32.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-hot">BÁN CHẠY</span>
                                <img src="{{ asset('images/products/milo-dam-da.jpg') }}" alt="Milo đậm đà">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Milo đậm đà</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.9 (200+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">30.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-product-card" onclick="openProductModal(this)">
                            <div class="p-product-img-wrap">
                                <span class="p-product-badge p-badge-new">MỚI</span>
                                <img src="{{ asset('images/products/tra-dao.jpg') }}" alt="Trà đào cam sả">
                            </div>
                            <div class="p-product-body">
                                <p class="p-product-name">Trà đào cam sả</p>
                                <div class="p-product-rating">
                                    <svg class="p-rating-star-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>4.6 (78+)</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">38.000đ</span>
                                    <button class="p-add-btn" aria-label="Thêm vào giỏ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div><!-- end .p-product-grid -->
                </div><!-- end .p-product-area -->
            </div><!-- end .p-main-layout -->
        </div><!-- end .p-page-wrapper -->


    {{-- Quick View Modal (tích hợp từ partials/product.blade.php) --}}
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:900px;">
            <div class="modal-content">
                <div class="modal-body" style="padding:2rem;position:relative;">
                    <div style="position:absolute;top:0.75rem;right:0.75rem;">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:1.5rem;">
                        {{-- Hình ảnh sản phẩm --}}
                        <div style="flex:1;min-width:260px;">
                            <img id="modalMainImg" src="" alt=""
                                 style="width:100%;border-radius:10px;object-fit:cover;aspect-ratio:1;" />
                        </div>
                        {{-- Thông tin sản phẩm --}}
                        <div style="flex:1;min-width:240px;">
                            <div style="display:flex;flex-direction:column;gap:0.85rem;">
                                <p id="modal-name"
                                   style="font-size:1.3rem;font-weight:700;color:#111827;margin:0;"></p>
                                <div style="display:flex;align-items:center;gap:0.4rem;">
                                    <span id="modal-stars" style="color:#f59e0b;font-size:1rem;"></span>
                                    <span id="modal-review" style="font-size:0.82rem;color:#6b7280;"></span>
                                </div>
                                <div id="modal-price"
                                     style="font-size:1.5rem;font-weight:800;color:#10b981;"></div>
                                <hr style="margin:0;" />
                                {{-- Số lượng --}}
                                <div>
                                    <p style="font-size:0.85rem;font-weight:600;color:#374151;margin:0 0 0.5rem;">Số lượng</p>
                                    <div style="display:inline-flex;align-items:center;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                        <button onclick="changeQty(-1)"
                                                style="width:36px;height:36px;background:#f9fafb;border:none;cursor:pointer;font-size:1.1rem;">−</button>
                                        <input type="number" id="modal-qty" value="1" min="1"
                                               style="width:48px;height:36px;text-align:center;border:none;outline:none;font-weight:600;" />
                                        <button onclick="changeQty(1)"
                                                style="width:36px;height:36px;background:#f9fafb;border:none;cursor:pointer;font-size:1.1rem;">+</button>
                                    </div>
                                </div>
                                {{-- Nút thêm vào giỏ --}}
                                <button type="button"
                                        style="padding:0.75rem;background:#10b981;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:0.95rem;transition:background 0.2s;"
                                        onmouseover="this.style.background='#059669'"
                                        onmouseout="this.style.background='#10b981'">
                                    + Thêm vào giỏ hàng
                                </button>
                                <hr style="margin:0;" />
                                <table style="font-size:0.84rem;width:100%;border-collapse:collapse;">
                                    <tbody>
                                        <tr>
                                            <td style="padding:0.35rem 0.75rem 0.35rem 0;color:#6b7280;white-space:nowrap;">Danh mục:</td>
                                            <td id="modal-category" style="color:#111827;font-weight:500;"></td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0.35rem 0.75rem 0.35rem 0;color:#6b7280;">Tình trạng:</td>
                                            <td style="color:#10b981;font-weight:600;">Còn hàng</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0.35rem 0.75rem 0.35rem 0;color:#6b7280;">Giao hàng:</td>
                                            <td style="color:#111827;">Miễn phí trong nội thành</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>{{-- /#quickViewModal --}}

    <script>
        /* ---- Price slider ---- */
        function updatePriceLabel(val) {
            const formatted = parseInt(val).toLocaleString('vi-VN');
            document.getElementById('price-label').textContent = '100.000đ \u2013 ' + formatted + '\u0111';
            const slider = document.getElementById('price-slider');
            const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${pct}%, #d1d5db ${pct}%, #d1d5db 100%)`;
        }

        /* ---- Quick-view modal ---- */
        function openProductModal(card) {
            const imgEl   = card.querySelector('.p-product-img-wrap img');
            const nameEl  = card.querySelector('.p-product-name');
            const priceEl = card.querySelector('.p-product-price');
            const ratEl   = card.querySelector('.p-product-rating span');

            document.getElementById('modal-name').textContent  = nameEl  ? nameEl.textContent.trim()  : '';
            document.getElementById('modal-price').textContent = priceEl ? priceEl.textContent.trim() : '';
            document.getElementById('modal-category').textContent = 'Đồ uống';

            const stars = ratEl ? parseFloat(ratEl.textContent) : 0;
            document.getElementById('modal-stars').textContent = '\u2605'.repeat(Math.round(stars)) + '\u2606'.repeat(5 - Math.round(stars));
            document.getElementById('modal-review').textContent = ratEl ? ratEl.textContent.trim() : '';

            const src = imgEl ? imgEl.src : '';
            document.getElementById('modalMainImg').src = src;
            document.getElementById('modal-qty').value = 1;

            new bootstrap.Modal(document.getElementById('quickViewModal')).show();
        }

        /* ---- Quantity stepper ---- */
        function changeQty(delta) {
            const input = document.getElementById('modal-qty');
            input.value = Math.max(1, parseInt(input.value || 1) + delta);
        }
    </script>
@endsection
