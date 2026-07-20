{{-- Kế thừa cấu trúc giao diện chính của toàn bộ trang web (Layout App) --}}
@extends('frontend.layouts.app')

{{-- Chừa khoảng trống phía dưới trên mobile để nội dung không bị thanh điều hướng dưới cùng che mất --}}
@section('body_class', 'has-mobile-bottom-nav')

@section('content')
        <div class="p-page-wrapper">
            <!-- Breadcrumb (Đường dẫn định vị thanh tiêu đề) -->
            <nav class="p-breadcrumb" aria-label="Breadcrumb">
                <a href="/">Trang chủ</a>
                <span class="p-breadcrumb-sep">/</span>
                <span class="p-breadcrumb-current">Sản phẩm</span>
            </nav>

            <div class="p-main-layout">
                <!-- Sidebar Filters (Thanh bộ lọc bên sườn trái) -->
                <aside class="p-sidebar">
                    {{-- Nút bấm mở/đóng bộ lọc trên thiết bị di động --}}
                    <button type="button" class="p-sidebar-toggle" onclick="toggleFilter()">
                        <div class="p-sidebar-toggle-content">
                            <span class="material-symbols-outlined">filter_list</span>
                            <span>Bộ lọc sản phẩm</span>
                        </div>
                        <span class="material-symbols-outlined toggle-icon p-sidebar-toggle-arrow">expand_more</span>
                    </button>
                    
                    {{-- Biểu mẫu (Form) lọc sản phẩm gửi các thông số qua phương thức GET --}}
                    <form id="filter-form" action="{{ route('products') }}" method="GET" class="p-filter-form">
                        {{-- Ô ẩn để truyền giá trị tìm kiếm hiện tại khi thực hiện lọc --}}
                        <input type="hidden" id="filter-search" name="search" value="{{ request('search') }}">
                        
                        <!-- Nhóm lọc: Danh mục sản phẩm (Loại đồ uống) -->
                        <div class="p-filter-group">
                            <h3 class="p-filter-title">Bộ lọc</h3>
                            @foreach($categories as $category)
                            <label class="p-filter-item">
                                {{-- Checkbox danh mục, khi thay đổi sẽ tự động xóa từ khóa tìm kiếm và gửi form --}}
                                <input type="checkbox" name="category[]" value="{{ $category->id }}"
                                       onchange="clearSearchAndSubmit()"
                                       {{ in_array($category->id, $categoryIds) ? 'checked' : '' }}>
                                <span>{{ $category->name }}</span>
                            </label>
                            @endforeach
                        </div>

                        <!-- Nhóm lọc: Khoảng giá sản phẩm (Sử dụng thanh kéo range slider) -->
                        <div class="p-filter-group">
                            <h3 class="p-filter-title">Giá</h3>
                            <div class="p-price-range-wrap">
                                @php
                                    // Giá trị nhỏ nhất và lớn nhất của thanh kéo
                                    $sliderMin = 0;
                                    $sliderMax = 600000;
                                    // Tính toán phần trăm thanh kéo hiện tại để đổ màu nền thanh slider (màu xanh lá)
                                    $sliderPct = round((($maxPrice - $sliderMin) / ($sliderMax - $sliderMin)) * 100, 2);
                                @endphp
                                <input type="range" class="p-price-slider" id="price-slider" name="max_price"
                                       min="{{ $sliderMin }}" max="{{ $sliderMax }}" value="{{ $maxPrice }}" step="10000"
                                       style="background: linear-gradient(to right, #10b981 0%, #10b981 {{ $sliderPct }}%, #d1d5db {{ $sliderPct }}%, #d1d5db 100%);"
                                       oninput="updatePriceLabel(this.value)"
                                       onchange="submitFilterForm();">
                                {{-- Hiển thị khoảng giá trị hiện tại dạng văn bản --}}
                                <div class="p-price-label" id="price-label">0đ – {{ number_format($maxPrice, 0, ',', '.') }}đ</div>
                            </div>
                        </div>

                    <!-- Nhóm lọc: Đánh giá sao (Rating) -->
                    <div class="p-filter-group">
                        <h3 class="p-filter-title">Đánh giá</h3>
                        {{-- Tùy chọn lọc: Từ 4 sao trở lên --}}
                        <label class="p-rating-item">
                            <input type="radio" id="rating-4" name="rating" value="4" onchange="submitFilterForm();" {{ request('rating') == '4' ? 'checked' : '' }}>
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">Từ 4 sao</span>
                        </label>
                        {{-- Tùy chọn lọc: Từ 3 sao trở lên --}}
                        <label class="p-rating-item">
                            <input type="radio" id="rating-3" name="rating" value="3" onchange="submitFilterForm();" {{ request('rating') == '3' ? 'checked' : '' }}>
                            <span class="p-rating-stars">
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-filled">★</span>
                                <span class="p-star-empty">★</span>
                                <span class="p-star-empty">★</span>
                            </span>
                            <span class="p-rating-label">Từ 3 sao</span>
                        </label>
                        {{-- Tùy chọn lọc: Tất cả đánh giá (không lọc) --}}
                        <label class="p-rating-item">
                            <input type="radio" id="rating-all" name="rating" value="0" onchange="submitFilterForm();" {{ request('rating') == '0' || !request()->has('rating') ? 'checked' : '' }}>
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

                    <!-- Nút Áp dụng trên thiết bị di động (ở Desktop được ẩn đi) -->
                    <button type="submit" class="p-filter-submit-btn">Áp dụng bộ lọc</button>
                    </form>
                </aside>

                <!-- Product Area (Khu vực danh sách sản phẩm) -->
                <div class="p-product-area">
                    <!-- Sort & Filter Pills (Thanh sắp xếp và nút lọc thẻ nhanh) -->
                    <div class="p-sort-bar p-sort-bar-flex">
                        {{-- Các nút tab lọc nhanh: Tất cả, Bán chạy, Mới nhất --}}
                        <div class="home-popular__filter" id="product-pill-filters">
                            <button class="home-popular__filter-btn home-popular__filter-btn--active" data-filter="all">Tất cả</button>
                            <button class="home-popular__filter-btn" data-filter="hot">Bán chạy</button>
                            <button class="home-popular__filter-btn" data-filter="new">Mới nhất</button>
                        </div>
                        {{-- Dropdown lựa chọn tiêu chí sắp xếp --}}
                        <select class="p-sort-select" id="sort-select" aria-label="Sắp xếp">
                            <option value="popular">Sắp xếp theo: Phổ biến nhất</option>
                            <option value="price-asc">Giá: Thấp đến cao</option>
                            <option value="price-desc">Giá: Cao đến thấp</option>
                            <option value="newest">Mới nhất</option>
                            <option value="rating">Đánh giá cao nhất</option>
                        </select>
                    </div>

                    <!-- Grid chứa danh sách sản phẩm -->
                    <div class="p-product-grid" id="product-grid">

                        @forelse($products as $product)
                        @php
                            // Kiểm tra trạng thái HOT (Bán chạy) và NEW (Mới nhất) phục vụ gắn huy hiệu và bộ lọc JS
                            $isHot = in_array($product->id, $top6HotProductIds);
                            $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15);
                            $isOos = !$product->is_active; // Hết hàng khi is_active = 0
                        @endphp
                        {{-- Thẻ sản phẩm. Các thuộc tính data-* đóng vai trò truyền dữ liệu để JS lọc/sắp xếp nhanh tại client --}}
                        <div class="p-product-card {{ $isOos ? 'p-product-card--out-of-stock' : '' }}"
                             data-sold="{{ $product->total_sold }}"
                             data-price-val="{{ $product->base_price }}"
                             data-date="{{ strtotime($product->created_at) }}"
                             data-rating-val="{{ $product->avg_rating }}"
                             data-is-hot="{{ $isHot ? '1' : '0' }}"
                             data-is-new="{{ $isNew ? '1' : '0' }}">
                            
                            {{-- Vùng ảnh sản phẩm, nhấp vào sẽ chuyển hướng sang trang chi tiết sản phẩm --}}
                            <div class="p-product-img-wrap p-product-img-wrap-pointer"
                                 onclick="window.location.href='{{ route('product.show', $product->slug) }}'"
                                 data-id="{{ $product->id }}"
                                 data-name="{{ $product->name }}"
                                 data-price="{{ number_format($product->base_price, 0, ',', '.') }}đ"
                                 data-category="{{ $product->category_name }}"
                                 data-image="{{ $product->image_url }}"
                                 data-slug="{{ $product->slug }}"
                                 data-rating="{{ number_format($product->avg_rating, 1) }} ({{ $product->review_count }} đánh giá)">

                                {{-- Nhãn (Badge) trạng thái HOT hoặc NEW --}}
                                @if($isHot && !$isOos)
                                    <span class="home-prod-card__badge home-prod-card__badge--hot">🔥 Bán chạy</span>
                                @endif
                                @if($isNew && !$isOos)
                                    <span class="home-prod-card__badge home-prod-card__badge--new" style="{{ $isHot ? 'display: none;' : '' }}">✨ Mới</span>
                                @endif
                                @if($isOos)
                                    <span class="out-of-stock-overlay">Hết Hàng</span>
                                @endif

                                {{-- Nút thả tim yêu thích sản phẩm --}}
                                <button class="home-prod-card__wishlist p-product-card-wishlist {{ in_array($product->id, $favoriteProductIds) ? 'is-active' : '' }}"
                                        data-id="{{ $product->id }}"
                                        onclick="event.stopPropagation(); toggleFavorite(this, {{ $product->id }})"
                                        aria-label="Yêu thích">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="heart-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                </button>

                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                            </div>

                            {{-- Phần thân thông tin sản phẩm (Tên, Số sao, Số lượng đã bán, Giá cả, Nút thêm giỏ hàng) --}}
                            <div class="p-product-body">
                                <a href="{{ route('product.show', $product->slug) }}" class="p-product-name p-product-name-link">{{ $product->name }}</a>
                                
                                {{-- Số sao đánh giá và tổng lượt bán ra của món này --}}
                                <div class="p-product-stats p-product-stats-layout">
                                    <svg class="p-product-star-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span>{{ number_format($product->avg_rating, 1) }} </span>
                                    <span class="p-product-stat-divider">|</span>
                                    <span>Đã bán @if($product->total_sold >= 1000){{ number_format($product->total_sold / 1000, 1) }}k+@else{{ $product->total_sold }}@endif</span>
                                </div>
                                
                                {{-- Giá bán sản phẩm và nút thêm giỏ hàng nhanh --}}
                                <div class="p-product-price-row">
                                    <span class="p-product-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</span>
                                    <div class="p-product-price-actions">
                                        <button class="p-add-btn p-product-add-btn-size" aria-label="Thêm vào giỏ"
                                            @if(!$isOos) onclick="addToCart({{ $product->id }})" @else disabled @endif>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        {{-- Hiển thị khi danh sách kết quả rỗng --}}
                        <div class="p-product-grid-empty">
                            Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
                        </div>
                        @endforelse

                    </div><!-- end .p-product-grid -->
                </div><!-- end .p-product-area -->
            </div><!-- end .p-main-layout -->
        </div><!-- end .p-page-wrapper -->

    @include('frontend.components.bottom-nav')

    {{-- Nhúng tệp tin Script JS điều khiển sắp xếp sản phẩm phía Client --}}
    <script src="{{ asset('js/frontend/products/index.js') }}"></script>
@endsection
