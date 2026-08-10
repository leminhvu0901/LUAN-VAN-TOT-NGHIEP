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

    <script>
    // Đóng/mở thanh sidebar Bộ lọc trên di động (gắn class "open" để CSS trượt sidebar ra/vào),
    // trên desktop sidebar luôn hiện sẵn nên hàm này thường chỉ được gọi khi ở màn hình nhỏ.
    function toggleFilter() {
        const sidebar = document.querySelector('.p-sidebar');
        if (sidebar) sidebar.classList.toggle('open');
    }

    // Cập nhật nhãn hiển thị khoảng giá (VD "0đ – 250.000đ") và tô màu phần thanh trượt giá đã kéo
    // qua (bên trái con trượt tô xanh, bên phải để màu xám) mỗi khi người dùng kéo thanh <input type="range">
    function updatePriceLabel(val) {
        const formatted = parseInt(val).toLocaleString('vi-VN');
        document.getElementById('price-label').textContent = '0đ – ' + formatted + 'đ';

        const slider = document.getElementById('price-slider');
        if (slider) {
            const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${pct}%, #d1d5db ${pct}%, #d1d5db 100%)`;
        }
    }

    // Khởi tạo nhãn + màu thanh trượt giá đúng theo giá trị đã chọn từ trước (VD sau khi submit
    // filter và tải lại trang, giá trị slider được server giữ lại qua query string)
    window.addEventListener('DOMContentLoaded', () => {
        const slider = document.getElementById('price-slider');
        if (slider) {
            updatePriceLabel(slider.value);
        }
    });

    // Xóa nội dung ô tìm kiếm (cả ô tìm kiếm trong sidebar lẫn ô tìm kiếm trên navbar) rồi submit
    // lại form lọc ngay lập tức - dùng cho nút "x" xóa nhanh từ khóa tìm kiếm đang áp dụng
    function clearSearchAndSubmit() {
        const searchInput = document.getElementById('filter-search');
        if (searchInput) searchInput.value = '';
        const navSearchInput = document.getElementById('search-input');
        if (navSearchInput) navSearchInput.value = '';
        if (window.innerWidth > 640) document.getElementById('filter-form').requestSubmit();
    }

    // Submit form lọc (GET) ngay khi người dùng đổi 1 lựa chọn trong sidebar (tích danh mục, chọn
    // sao đánh giá...) - chỉ tự submit trên màn hình > 640px (desktop/tablet); trên di động người
    // dùng phải bấm nút "Áp dụng" thủ công, tránh submit liên tục gây giật khi đang thao tác trên sidebar trượt
    function submitFilterForm() {
        if (window.innerWidth > 640) document.getElementById('filter-form').requestSubmit();
    }

    const sortSelect = document.getElementById('sort-select');
    let grid = document.getElementById('product-grid');

    // =========================================================================
    // SẮP XẾP LẠI LƯỚI SẢN PHẨM PHÍA CLIENT (không gọi lại server) khi đổi dropdown "Sắp xếp theo".
    // Toàn bộ sản phẩm của TRANG hiện tại (đã phân trang từ backend) đã có sẵn trong DOM kèm các
    // data-* attribute (data-sold, data-price-val, data-date, data-rating-val...); hàm này chỉ sắp
    // xếp lại thứ tự các thẻ đã có, không lọc/phân trang lại - tương tự cơ chế bộ lọc pill ở trang chủ.
    // =========================================================================
    function applySortAndFilter() {
        if (!sortSelect || !grid) return;
        const sortBy = sortSelect.value;
        const cards = Array.from(grid.querySelectorAll('.p-product-card'));

        // Tiêu chí sắp xếp tương ứng từng lựa chọn trong dropdown: phổ biến (theo lượt bán), giảm
        // giá (ưu tiên sản phẩm đang sale trước, cùng loại thì so lượt bán), giá tăng/giảm dần, mới
        // nhất (theo ngày tạo) và đánh giá cao nhất
        cards.sort((a, b) => {
            if (sortBy === 'popular') return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0);
            if (sortBy === 'discount') {
                const hasSaleB = b.querySelector('.home-prod-card__badge--sale') ? 1 : 0;
                const hasSaleA = a.querySelector('.home-prod-card__badge--sale') ? 1 : 0;
                if (hasSaleB !== hasSaleA) return hasSaleB - hasSaleA;
                return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0);
            }
            if (sortBy === 'price-asc') return parseFloat(a.dataset.priceVal || 0) - parseFloat(b.dataset.priceVal || 0);
            if (sortBy === 'price-desc') return parseFloat(b.dataset.priceVal || 0) - parseFloat(a.dataset.priceVal || 0);
            if (sortBy === 'newest') return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0);
            if (sortBy === 'rating') return parseFloat(b.dataset.ratingVal || 0) - parseFloat(a.dataset.ratingVal || 0);
            return 0;
        });

        // appendChild trên 1 phần tử đã có sẵn trong DOM sẽ DI CHUYỂN nó tới cuối, không tạo bản
        // sao - lặp theo đúng thứ tự vừa sort() sẽ đưa toàn bộ thẻ về đúng thứ tự hiển thị mới
        cards.forEach(card => {
            grid.appendChild(card);

            // Đổi badge nào được hiển thị trên thẻ sản phẩm cho khớp với tiêu chí đang sắp xếp (VD
            // sort theo "Mới nhất" thì ưu tiên hiện badge "Mới", sort theo "Giảm giá" thì ưu tiên
            // hiện badge "Giảm giá") - mỗi thẻ chỉ hiện tối đa 1 badge tại 1 thời điểm để đỡ rối mắt
            const hotBadge = card.querySelector('.home-prod-card__badge--hot');
            const newBadge = card.querySelector('.home-prod-card__badge--new');
            const saleBadge = card.querySelector('.home-prod-card__badge--sale');
            if (sortBy === 'newest') {
                if (newBadge) { newBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; if (saleBadge) saleBadge.style.display = 'none'; }
                else if (saleBadge) { saleBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; }
                else if (hotBadge) hotBadge.style.display = '';
            } else if (sortBy === 'discount') {
                if (saleBadge) { saleBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; if (newBadge) newBadge.style.display = 'none'; }
                else if (hotBadge) { hotBadge.style.display = ''; if (newBadge) newBadge.style.display = 'none'; }
                else if (newBadge) newBadge.style.display = '';
            } else {
                if (saleBadge) { saleBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; if (newBadge) newBadge.style.display = 'none'; }
                else if (hotBadge) { hotBadge.style.display = ''; if (newBadge) newBadge.style.display = 'none'; }
                else if (newBadge) newBadge.style.display = '';
            }
        });
    }

    if (sortSelect && grid) {
        sortSelect.addEventListener('change', applySortAndFilter);
        applySortAndFilter(); // Chạy 1 lần lúc tải trang để áp dụng đúng lựa chọn sắp xếp mặc định
    }

    // =========================================================================
    // DROPDOWN "SẮP XẾP THEO" TÙY BIẾN GIAO DIỆN
    // Thẻ <select> gốc (#sort-select) được ẩn đi và thay bằng 1 dropdown tự vẽ bằng div/button để
    // dễ tùy biến giao diện hơn <select> mặc định của trình duyệt. Khối này đồng bộ 2 chiều: chọn
    // trong dropdown tùy biến sẽ cập nhật lại giá trị + bắn sự kiện "change" trên <select> gốc, để
    // applySortAndFilter() ở trên (đang lắng nghe sự kiện change của select gốc) vẫn chạy bình thường.
    // =========================================================================
    (function () {
        const dropdown = document.getElementById('sort-dropdown');
        const toggle = document.getElementById('sort-dropdown-toggle');
        const menu = document.getElementById('sort-dropdown-menu');
        const label = document.getElementById('sort-dropdown-label');
        if (!dropdown || !toggle || !menu || !label || !sortSelect) return;

        const SORT_LABEL_PREFIX = 'Sắp xếp theo: ';

        // Mở khung bộ lọc dạng trượt trên màn hình nhỏ
        function openMenu() {
            menu.hidden = false;
            dropdown.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }

        // Đóng khung bộ lọc dạng trượt
        function closeMenu() {
            menu.hidden = true;
            dropdown.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation(); // Chặn không cho lan lên listener "click ra ngoài" bên dưới
            if (menu.hidden) openMenu(); else closeMenu();
        });

        // Bấm chọn 1 lựa chọn trong menu: đổi trạng thái "đang chọn", đổi nhãn hiển thị, đồng bộ giá
        // trị + bắn sự kiện change sang <select> gốc rồi đóng menu lại
        menu.addEventListener('click', function (event) {
            const option = event.target.closest('.p-sort-dropdown__option');
            if (!option) return;

            menu.querySelectorAll('.p-sort-dropdown__option').forEach(function (el) {
                el.classList.remove('is-selected');
                el.setAttribute('aria-selected', 'false');
            });
            option.classList.add('is-selected');
            option.setAttribute('aria-selected', 'true');
            label.textContent = SORT_LABEL_PREFIX + option.textContent.trim();

            sortSelect.value = option.dataset.value;
            sortSelect.dispatchEvent(new Event('change'));
            closeMenu();
        });

        // Đóng menu khi bấm ra ngoài vùng dropdown, hoặc khi bấm phím Escape
        document.addEventListener('click', function (event) {
            if (!menu.hidden && !dropdown.contains(event.target)) closeMenu();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !menu.hidden) closeMenu();
        });
    })();

    // Khi submit form lọc (nút "Áp dụng" trên di động), tự đóng sidebar bộ lọc lại trước khi trang
    // tải lại - tránh trường hợp trang mới tải xong mà sidebar vẫn đang ở trạng thái mở đè lên nội dung
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            const sidebar = document.querySelector('.p-sidebar');
            if (sidebar) sidebar.classList.remove('open');
        });
    }
    </script>
@endsection

