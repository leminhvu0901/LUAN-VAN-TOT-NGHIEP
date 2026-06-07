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
                    <form id="filter-form" action="{{ route('products') }}" method="GET">
                        <input type="hidden" id="filter-search" name="search" value="{{ request('search') }}">
                        <!-- Danh mục -->
                        <div class="p-filter-group">
                            <h3 class="p-filter-title">Bộ lọc</h3>
                            @foreach($categories as $category)
                            <label class="p-filter-item">
                                <input type="checkbox" name="category[]" value="{{ $category->id }}"
                                       onchange="clearSearchAndSubmit()"
                                       {{ in_array($category->id, $categoryIds) ? 'checked' : '' }}>
                                <span>{{ $category->name }}</span>
                            </label>
                            @endforeach
                        </div>

                        <!-- Giá -->
                        <div class="p-filter-group">
                            <h3 class="p-filter-title">Giá</h3>
                            <div class="p-price-range-wrap">
                                @php
                                    $sliderMin = 10000;
                                    $sliderMax = 600000;
                                    $sliderPct = round((($maxPrice - $sliderMin) / ($sliderMax - $sliderMin)) * 100, 2);
                                @endphp
                                <input type="range" class="p-price-slider" id="price-slider" name="max_price"
                                       min="{{ $sliderMin }}" max="{{ $sliderMax }}" value="{{ $maxPrice }}" step="10000"
                                       style="background: linear-gradient(to right, #10b981 0%, #10b981 {{ $sliderPct }}%, #d1d5db {{ $sliderPct }}%, #d1d5db 100%);"
                                       oninput="updatePriceLabel(this.value)"
                                       onchange="document.getElementById('filter-form').submit();">
                                <div class="p-price-label" id="price-label">10.000đ – {{ number_format($maxPrice, 0, ',', '.') }}đ</div>
                            </div>
                        </div>

                    <!-- Đánh giá (Rating) -->
                    <div class="p-filter-group">
                        <h3 class="p-filter-title">Đánh giá</h3>
                        <label class="p-rating-item">
                            <input type="radio" id="rating-4" name="rating" value="4" onchange="document.getElementById('filter-form').submit();" {{ request('rating') == '4' ? 'checked' : '' }}>
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">Từ 4 sao</span>
                        </label>
                        <label class="p-rating-item">
                            <input type="radio" id="rating-3" name="rating" value="3" onchange="document.getElementById('filter-form').submit();" {{ request('rating') == '3' ? 'checked' : '' }}>
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">Từ 3 sao</span>
                        </label>
                        <label class="p-rating-item">
                            <input type="radio" id="rating-all" name="rating" value="0" onchange="document.getElementById('filter-form').submit();" {{ request('rating') == '0' || !request()->has('rating') ? 'checked' : '' }}>
                            <span class="p-rating-stars">
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">Tất cả</span>
                        </label>
                    </div>
                    </form>
                </aside>

                <!-- Product Area -->
                <div class="p-product-area">
                    <!-- Sort & Filter Pills -->
                    <div class="p-sort-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div class="home-popular__filter" id="product-pill-filters">
                            <button class="home-popular__filter-btn home-popular__filter-btn--active" data-filter="all">Tất cả</button>
                            <button class="home-popular__filter-btn" data-filter="hot">Bán chạy</button>
                            <button class="home-popular__filter-btn" data-filter="new">Mới nhất</button>
                        </div>
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

                        @forelse($products as $product)
                        @php
                            $isHot = in_array($product->id, $top6HotProductIds); // Bán chạy nếu lọt top 6
                            $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15); // Tạo trong vòng 15 ngày
                        @endphp
                        <div class="p-product-card" 
                             data-sold="{{ $product->total_sold }}" 
                             data-price-val="{{ $product->base_price }}" 
                             data-date="{{ strtotime($product->created_at) }}" 
                             data-rating-val="{{ $product->avg_rating }}"
                             data-is-hot="{{ $isHot ? '1' : '0' }}"
                             data-is-new="{{ $isNew ? '1' : '0' }}">
                            <div class="p-product-img-wrap" 
                                 onclick="window.location.href='{{ route('product.show', $product->slug) }}'"
                                 style="cursor:pointer;"
                                 data-id="{{ $product->id }}"
                                 data-name="{{ $product->name }}"
                                 data-price="{{ number_format($product->base_price, 0, ',', '.') }}đ"
                                 data-category="{{ $product->category_name }}"
                                 data-image="{{ asset('images/' . $product->image) }}"
                                 data-slug="{{ $product->slug }}"
                                 data-rating="{{ number_format($product->avg_rating, 1) }} ({{ $product->review_count }} đánh giá)">
                                
                                @if($isHot) 
                                    <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span> 
                                @endif
                                @if($isNew) 
                                    <span class="home-prod-card__badge home-prod-card__badge--new" style="{{ $isHot ? 'display: none;' : '' }}">✨ Mới</span> 
                                @endif

                                <!-- Wishlist Heart Button (Moved to top-right of image) -->
                                <button class="home-prod-card__wishlist {{ in_array($product->id, $favoriteProductIds) ? 'is-active' : '' }}" 
                                        data-id="{{ $product->id }}" 
                                        onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})" 
                                        aria-label="Yêu thích" 
                                        style="position: absolute; top: 8px; right: 8px; border: none; background: white; border-radius: 50%; padding: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 2; display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="heart-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                </button>

                                <img src="{{ asset('images/' . $product->image) }}" alt="{{ $product->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                            </div>
                            <div class="p-product-body">
                                <a href="{{ route('product.show', $product->slug) }}" class="p-product-name" style="text-decoration:none; color:inherit;">{{ $product->name }}</a>
                                <div class="p-product-stats" style="display: flex; align-items: center; gap: 4px; margin-top: 0.35rem; margin-bottom: 0.5rem; font-size: 13px; color: #64748b;">
                                    <svg style="color: #f59e0b; width: 14px; height: 14px; flex-shrink: 0;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>{{ number_format($product->avg_rating, 1) }} </span>
                                    <span style="color: #cbd5e1;">|</span>
                                    <span>Đã bán @if($product->total_sold >= 1000){{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}@endif</span>
                                </div>
                                <div class="p-product-price-row">
                                    <span class="p-product-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <!-- Add to Cart Button -->
                                        <button class="p-add-btn" aria-label="Thêm vào giỏ" onclick="addToCart({{ $product->id }})" style="width: 32px; height: 32px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;">
                            Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
                        </div>
                        @endforelse

                    </div><!-- end .p-product-grid -->
                </div><!-- end .p-product-area -->
            </div><!-- end .p-main-layout -->
        </div><!-- end .p-page-wrapper -->


    {{-- Quick View Modal đã được include toàn cục tại layouts/app.blade.php --}}

    <script>
        /* ---- Price slider ---- */
        function updatePriceLabel(val) {
            const formatted = parseInt(val).toLocaleString('vi-VN');
            document.getElementById('price-label').textContent = '10.000đ \u2013 ' + formatted + '\u0111';
            const slider = document.getElementById('price-slider');
            const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${pct}%, #d1d5db ${pct}%, #d1d5db 100%)`;
        }

        window.addEventListener('DOMContentLoaded', () => {
            const slider = document.getElementById('price-slider');
            if(slider) {
                // Update label only (background already set via inline style)
                const formatted = parseInt(slider.value).toLocaleString('vi-VN');
                document.getElementById('price-label').textContent = '10.000đ \u2013 ' + formatted + '\u0111';
            }
        });

        /* ---- Quick-view modal ---- */
        function openProductModal(wrap) {
            const name = wrap.getAttribute('data-name');
            const price = wrap.getAttribute('data-price');
            const image = wrap.getAttribute('data-image');
            const category = wrap.getAttribute('data-category');
            const rating = wrap.getAttribute('data-rating');

            // Set data to global modal (partials/modal-product.blade.php)
            document.getElementById('modal-product-name').textContent = name;
            document.getElementById('modal-product-price').textContent = price;
            document.getElementById('modal-product-rating').textContent = rating;
            document.getElementById('modal-product-img').src = image;
            
            // Set Add to Cart button inside modal
            const addToCartBtn = document.getElementById('modal-product-add-cart');
            if (addToCartBtn) {
                const productId = wrap.getAttribute('data-id');
                addToCartBtn.setAttribute('onclick', 'addToCart(' + productId + ', document.getElementById(\'modal-qty\').textContent)');
            }
            
            // Reset quantity to 1
            if (typeof window.qty !== 'undefined') window.qty = 1;
            document.getElementById('modal-qty').textContent = '1';

            // Show global modal overlay
            document.getElementById('modal-product-overlay').style.display = 'flex';
        }

        /* ---- Quantity stepper is already defined globally in modal-product.blade.php ---- */

        /* ---- Khi chọn danh mục: xóa từ khóa tìm kiếm rồi submit ---- */
        function clearSearchAndSubmit() {
            // Xóa từ khóa tìm kiếm khỏi form
            const searchInput = document.getElementById('filter-search');
            if (searchInput) searchInput.value = '';

            // Xóa ô tìm kiếm trên navbar nếu đang hiển thị
            const navSearchInput = document.getElementById('search-input');
            if (navSearchInput) navSearchInput.value = '';

            document.getElementById('filter-form').submit();
        }

        /* ---- Lọc và Sắp xếp Frontend ---- */
        const sortSelect = document.getElementById('sort-select');
        const grid = document.getElementById('product-grid');
        const pillButtons = document.querySelectorAll('#product-pill-filters .home-popular__filter-btn');
        let currentPillFilter = 'all';
        
        function applySortAndFilter() {
            if (!sortSelect || !grid) return;
            
            const sortBy = sortSelect.value;
            const cards = Array.from(grid.querySelectorAll('.p-product-card'));
            
            // 1. Sắp xếp
            cards.sort((a, b) => {
                if (sortBy === 'popular') {
                    return parseInt(b.getAttribute('data-sold') || 0) - parseInt(a.getAttribute('data-sold') || 0);
                } else if (sortBy === 'price-asc') {
                    return parseFloat(a.getAttribute('data-price-val') || 0) - parseFloat(b.getAttribute('data-price-val') || 0);
                } else if (sortBy === 'price-desc') {
                    return parseFloat(b.getAttribute('data-price-val') || 0) - parseFloat(a.getAttribute('data-price-val') || 0);
                } else if (sortBy === 'newest') {
                    return parseInt(b.getAttribute('data-date') || 0) - parseInt(a.getAttribute('data-date') || 0);
                } else if (sortBy === 'rating') {
                    return parseFloat(b.getAttribute('data-rating-val') || 0) - parseFloat(a.getAttribute('data-rating-val') || 0);
                }
                return 0;
            });
            
            // 2. Lọc (Filter) và Cập nhật DOM
            let visibleCount = 0;
            cards.forEach(card => {
                grid.appendChild(card); // Re-order in DOM
                
                // Kiểm tra xem card có thỏa mãn bộ lọc Pill không
                let isMatch = true;
                if (currentPillFilter === 'hot') {
                    isMatch = card.getAttribute('data-is-hot') === '1';
                } else if (currentPillFilter === 'new') {
                    isMatch = card.getAttribute('data-is-new') === '1';
                }
                
                if (isMatch) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
                
                // Logic ẩn/hiện tem dựa trên bộ lọc Sort
                const hotBadge = card.querySelector('.home-prod-card__badge--hot');
                const newBadge = card.querySelector('.home-prod-card__badge--new');
                
                if (sortBy === 'newest') {
                    if (newBadge) {
                        newBadge.style.display = '';
                        if (hotBadge) hotBadge.style.display = 'none';
                    } else if (hotBadge) {
                        hotBadge.style.display = '';
                    }
                } else {
                    if (hotBadge) {
                        hotBadge.style.display = '';
                        if (newBadge) newBadge.style.display = 'none';
                    } else if (newBadge) {
                        newBadge.style.display = '';
                    }
                }
            });

            // Nếu không có sản phẩm nào
            let emptyMsg = document.getElementById('empty-product-msg');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.id = 'empty-product-msg';
                    emptyMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;';
                    emptyMsg.textContent = 'Không tìm thấy sản phẩm nào phù hợp với bộ lọc.';
                    grid.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                    grid.appendChild(emptyMsg); // đưa xuống cuối
                }
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        }

        if (sortSelect && grid) {
            // Sự kiện khi đổi dropdown sắp xếp
            sortSelect.addEventListener('change', applySortAndFilter);
            
            // Sự kiện khi bấm các nút Pill (Tất cả, Bán chạy, Mới nhất)
            pillButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    pillButtons.forEach(b => b.classList.remove('home-popular__filter-btn--active'));
                    this.classList.add('home-popular__filter-btn--active');
                    currentPillFilter = this.getAttribute('data-filter');
                    applySortAndFilter();
                });
            });
            
            // Tự động sắp xếp lần đầu khi tải trang
            applySortAndFilter();
        }
    </script>
@endsection
