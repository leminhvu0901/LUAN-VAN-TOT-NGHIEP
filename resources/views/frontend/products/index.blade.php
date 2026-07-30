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
                        {{-- Dropdown lựa chọn tiêu chí sắp xếp.
                        Dùng dropdown tự dựng thay vì <select> hiển thị trực tiếp: popup của <select>
                        do trình duyệt/hệ điều hành tự vẽ, KHÔNG thể giới hạn chiều rộng bằng CSS nên
                        trên màn hình hẹp nó tràn ra ngoài khung, vỡ layout. <select> vẫn được giữ lại
                        (ẩn đi) làm nguồn dữ liệu duy nhất để logic sắp xếp sẵn có trong index.js
                        (đọc sortSelect.value + nghe sự kiện 'change') chạy y nguyên, không phải sửa. --}}
                        <div class="p-sort-dropdown" id="sort-dropdown">
                            <button type="button" class="p-sort-dropdown__toggle" id="sort-dropdown-toggle"
                                aria-haspopup="listbox" aria-expanded="false">
                                <span id="sort-dropdown-label">Sắp xếp theo: Phổ biến nhất</span>
                                <svg class="p-sort-dropdown__arrow" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            {{-- Chữ trong danh sách KHÔNG kèm tiền tố "Sắp xếp theo:" — phần tiền tố do
                            index.js tự ghép vào nhãn của nút. Trước đây chỉ mục đầu tiên có tiền tố nên
                            khi chọn mục khác, nút thu lại chỉ còn vài chữ (vd "Mới nhất"), vừa mất ngữ
                            cảnh vừa làm bảng chọn (rộng theo nút) bị bóp lại khiến chữ xuống dòng lung tung. --}}
                            <ul class="p-sort-dropdown__menu" id="sort-dropdown-menu" role="listbox" hidden>
                                <li class="p-sort-dropdown__option is-selected" role="option" aria-selected="true" data-value="popular">Phổ biến nhất</li>
                                <li class="p-sort-dropdown__option" role="option" aria-selected="false" data-value="discount">Đang giảm giá</li>
                                <li class="p-sort-dropdown__option" role="option" aria-selected="false" data-value="price-asc">Giá thấp đến cao</li>
                                <li class="p-sort-dropdown__option" role="option" aria-selected="false" data-value="price-desc">Giá cao đến thấp</li>
                                <li class="p-sort-dropdown__option" role="option" aria-selected="false" data-value="newest">Mới nhất</li>
                                <li class="p-sort-dropdown__option" role="option" aria-selected="false" data-value="rating">Đánh giá cao nhất</li>
                            </ul>

                            <select class="p-sort-select-hidden" id="sort-select" aria-hidden="true" tabindex="-1">
                                <option value="popular">Phổ biến nhất</option>
                                <option value="discount">Đang giảm giá</option>
                                <option value="price-asc">Giá thấp đến cao</option>
                                <option value="price-desc">Giá cao đến thấp</option>
                                <option value="newest">Mới nhất</option>
                                <option value="rating">Đánh giá cao nhất</option>
                            </select>
                        </div>
                    </div>

                    @include('frontend.products.partials.grid')
                </div><!-- end .p-product-area -->
            </div><!-- end .p-main-layout -->
        </div><!-- end .p-page-wrapper -->

    @include('frontend.components.bottom-nav')

    {{-- Nhúng tệp tin Script JS điều khiển sắp xếp sản phẩm phía Client --}}
    <script src="{{ asset('js/frontend/products/index.js') }}?v={{ filemtime(public_path('js/frontend/products/index.js')) }}"></script>
@endsection
