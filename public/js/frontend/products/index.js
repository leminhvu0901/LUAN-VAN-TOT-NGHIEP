/**
 * File Javascript điều khiển tương tác trên trang Danh sách sản phẩm
 * Hỗ trợ bộ lọc động bên client (phân loại HOT/NEW, sắp xếp, lọc giá, tìm kiếm)
 */

/**
 * Bật/tắt thanh bộ lọc bên sườn (Sidebar) trên giao diện di động
 */
function toggleFilter() {
    const sidebar = document.querySelector('.p-sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

/**
 * Cập nhật nhãn văn bản hiển thị giá tiền và đổ màu nền động cho thanh kéo (slider)
 * @param {string|number} val - Giá trị hiện tại của thanh kéo giá
 */
function updatePriceLabel(val) {
    // Định dạng số tiền theo chuẩn Việt Nam (ví dụ: 100.000)
    const formatted = parseInt(val).toLocaleString('vi-VN');
    document.getElementById('price-label').textContent = '0đ – ' + formatted + 'đ';
    
    // Tính toán tỷ lệ phần trăm thanh kéo hiện tại để vẽ màu nền (xanh lá và xám)
    const slider = document.getElementById('price-slider');
    if (slider) {
        const pct = ((val - slider.min) / (slider.max - slider.min)) * 100;
        slider.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${pct}%, #d1d5db ${pct}%, #d1d5db 100%)`;
    }
}

// Khởi chạy khi tài liệu HTML đã tải xong cấu trúc DOM
window.addEventListener('DOMContentLoaded', () => {
    const slider = document.getElementById('price-slider');
    if (slider) {
        // Đồng bộ nhãn giá trị mặc định lúc trang vừa load xong
        updatePriceLabel(slider.value);
    }
});

/**
 * Xóa từ khóa tìm kiếm và thực hiện gửi biểu mẫu bộ lọc lên server
 */
function clearSearchAndSubmit() {
    const searchInput = document.getElementById('filter-search');
    if (searchInput) searchInput.value = '';
    const navSearchInput = document.getElementById('search-input');
    if (navSearchInput) navSearchInput.value = '';
    // Trên màn hình máy tính (> 640px) -> tự động gửi form khi thay đổi tích chọn danh mục.
    // Dùng requestSubmit() (không phải submit()) vì submit() KHÔNG bắn sự kiện 'submit' — nếu dùng
    // submit() thì listener AJAX gắn ở dưới (chặn submit để gọi fetch) sẽ không bao giờ được gọi tới.
    if (window.innerWidth > 640) document.getElementById('filter-form').requestSubmit();
}

/**
 * Gửi dữ liệu biểu mẫu lọc lên máy chủ
 */
function submitFilterForm() {
    // Trên máy tính -> Tự động submit. Trên điện thoại -> Người dùng phải bấm nút Áp dụng
    if (window.innerWidth > 640) document.getElementById('filter-form').requestSubmit();
}

// Tham chiếu các phần tử điều khiển sắp xếp và lưới sản phẩm
const sortSelect = document.getElementById('sort-select');
// "let" (không phải const) vì #product-grid bị THAY THẾ hoàn toàn (không chỉ sửa nội dung) mỗi lần
// chuyển trang qua AJAX bên dưới -> node cũ bị gỡ khỏi DOM, phải gán lại biến này trỏ sang node MỚI,
// nếu không applySortAndFilter() sẽ thao tác nhầm lên node đã "chết", không còn hiển thị trên trang.
let grid = document.getElementById('product-grid');

/**
 * Thực thi sắp xếp (Sort) sản phẩm ngay tại client
 */
function applySortAndFilter() {
    if (!sortSelect || !grid) return;
    const sortBy = sortSelect.value;
    // Chuyển danh sách NodeList các thẻ sản phẩm thành Mảng (Array) để sắp xếp
    const cards = Array.from(grid.querySelectorAll('.p-product-card'));

    // Sắp xếp thứ tự các thẻ sản phẩm dựa vào tiêu chí được chọn
    cards.sort((a, b) => {
        if (sortBy === 'popular') return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0); // Bán chạy nhất
        if (sortBy === 'discount') {
            const hasSaleB = b.querySelector('.home-prod-card__badge--sale') ? 1 : 0;
            const hasSaleA = a.querySelector('.home-prod-card__badge--sale') ? 1 : 0;
            if (hasSaleB !== hasSaleA) return hasSaleB - hasSaleA;
            return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0);
        }
        if (sortBy === 'price-asc') return parseFloat(a.dataset.priceVal || 0) - parseFloat(b.dataset.priceVal || 0); // Giá thấp đến cao
        if (sortBy === 'price-desc') return parseFloat(b.dataset.priceVal || 0) - parseFloat(a.dataset.priceVal || 0); // Giá cao đến thấp
        if (sortBy === 'newest') return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0); // Mới nhất
        if (sortBy === 'rating') return parseFloat(b.dataset.ratingVal || 0) - parseFloat(a.dataset.ratingVal || 0); // Điểm sao đánh giá cao nhất
        return 0;
    });

    // Duyệt qua từng thẻ sản phẩm để sắp xếp lại vị trí trong DOM
    cards.forEach(card => {
        grid.appendChild(card); // Đắp lại phần tử vào lưới theo thứ tự mới đã sort

        // Điều chỉnh nhãn (badge) hiển thị tối ưu theo kiểu sắp xếp
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

    // Không cần tự dựng thông báo "không tìm thấy sản phẩm" ở đây nữa: sau khi bỏ bộ lọc tag nhanh,
    // JS không còn ẩn thẻ nào, nên lưới rỗng chỉ xảy ra khi bộ lọc phía server không ra kết quả —
    // trường hợp đó grid.blade.php đã tự render sẵn khối .p-product-grid-empty rồi (khối JS cũ còn
    // gây hiện thông báo LẶP 2 lần trong đúng tình huống này).
}

// Đăng ký sự kiện lắng nghe tương tác nếu tồn tại các phần tử điều khiển trên trang
if (sortSelect && grid) {
    // Lắng nghe sự thay đổi của hộp chọn Sắp xếp
    sortSelect.addEventListener('change', applySortAndFilter);

    // Tự động chạy sắp xếp mặc định lần đầu khi tải trang
    applySortAndFilter();
}

/**
 * Dropdown "Sắp xếp theo" tự dựng — chỉ đóng vai trò GIAO DIỆN, mọi thay đổi đều ghi vào <select> ẩn
 * (#sort-select) rồi bắn sự kiện 'change' để logic sắp xếp sẵn có ở trên chạy y nguyên, không phải
 * sửa gì. Lý do không dùng thẳng <select>: popup của nó do trình duyệt/hệ điều hành tự vẽ, không giới
 * hạn được chiều rộng bằng CSS nên tràn ra ngoài khung, vỡ layout trên màn hình hẹp.
 */
(function () {
    const dropdown = document.getElementById('sort-dropdown');
    const toggle = document.getElementById('sort-dropdown-toggle');
    const menu = document.getElementById('sort-dropdown-menu');
    const label = document.getElementById('sort-dropdown-label');
    if (!dropdown || !toggle || !menu || !label || !sortSelect) return;

    // Tiền tố cố định của nhãn nút — chữ trong danh sách chọn không kèm sẵn (xem chú thích ở index.blade.php)
    const SORT_LABEL_PREFIX = 'Sắp xếp theo: ';

    function openMenu() {
        menu.hidden = false;
        dropdown.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
        menu.hidden = true;
        dropdown.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        if (menu.hidden) openMenu(); else closeMenu();
    });

    menu.addEventListener('click', function (event) {
        const option = event.target.closest('.p-sort-dropdown__option');
        if (!option) return;

        menu.querySelectorAll('.p-sort-dropdown__option').forEach(function (el) {
            el.classList.remove('is-selected');
            el.setAttribute('aria-selected', 'false');
        });
        option.classList.add('is-selected');
        option.setAttribute('aria-selected', 'true');
        // Luôn ghép tiền tố để nút giữ nguyên ngữ cảnh ("Sắp xếp theo: Mới nhất" thay vì chỉ "Mới nhất")
        // và không bị co lại quá ngắn mỗi lần đổi tiêu chí.
        label.textContent = SORT_LABEL_PREFIX + option.textContent.trim();

        sortSelect.value = option.dataset.value;
        sortSelect.dispatchEvent(new Event('change'));
        closeMenu();
    });

    // Bấm ra ngoài hoặc nhấn Escape thì đóng — cùng cách các dropdown khác trên site đang làm.
    document.addEventListener('click', function (event) {
        if (!menu.hidden && !dropdown.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !menu.hidden) closeMenu();
    });
})();

/**
 * Phân trang qua AJAX (mục tiêu: tránh tải lại cả trang gây giật/nhấp nháy khi bấm chuyển trang).
 * Chặn click vào .p-pagination__btn, gọi fetch lấy đúng partial lưới sản phẩm + phân trang mới,
 * thay nội dung tại chỗ, cập nhật URL trên thanh địa chỉ (không tải lại trang), rồi chạy lại sort/filter
 * phía client trên bộ thẻ vừa nhận về. Gắn listener trên document (không phải trên #ajax-product-area
 * trực tiếp) vì vùng đó bị thay thế hoàn toàn mỗi lần chuyển trang, gắn trực tiếp sẽ mất listener.
 */
document.addEventListener('click', function (event) {
    const link = event.target.closest('.p-pagination__btn');
    if (!link) return;
    if (link.classList.contains('p-pagination__btn--disabled')) {
        event.preventDefault();
        return;
    }

    const url = link.getAttribute('href');
    if (!url) return;

    event.preventDefault();
    // Truyền context 'pagination' để loadProductsPage biết đây là chuyển trang
    // (khác với đổi bộ lọc) và cuộn về đầu danh sách sau khi load xong.
    loadProductsPage(url, true, 'pagination');
});

function loadProductsPage(url, updateHistory, context) {
    if (updateHistory === undefined) updateHistory = true;
    if (context === undefined) context = 'filter';

    const wrapper = document.getElementById('ajax-product-area');
    if (!wrapper) {
        window.location.href = url; // Không tìm thấy vùng AJAX (không nên xảy ra) -> điều hướng thật để không kẹt trang
        return;
    }
    const outerParent = wrapper.parentElement;

    wrapper.classList.add('p-product-area-loading');

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.text();
        })
        .then(function (html) {
            outerParent.innerHTML = html;
            // Chỉ đẩy thêm lịch sử khi người dùng CHỦ ĐỘNG bấm số trang mới — khi hàm này được gọi lại
            // do sự kiện popstate (bấm nút Back/Forward của trình duyệt) thì KHÔNG đẩy thêm, nếu không
            // sẽ phá vỡ ngăn xếp lịch sử, bấm Back nhiều lần sẽ không thoát được khỏi trang sản phẩm.
            if (updateHistory) history.pushState({}, '', url);

            // #product-grid vừa bị thay bằng node MỚI (do outerParent.innerHTML ghi đè toàn bộ) -> phải
            // gán lại tham chiếu, nếu không applySortAndFilter() sẽ thao tác nhầm lên node cũ đã gỡ khỏi DOM.
            grid = document.getElementById('product-grid');
            if (sortSelect && grid) applySortAndFilter();

            // Khi chuyển trang phân trang (context === 'pagination'): cuộn viewport về đúng đầu vùng
            // danh sách sản phẩm để người dùng (đặc biệt trên mobile) thấy ngay sản phẩm đầu tiên của
            // trang mới thay vì phải tự cuộn lên. Dùng smooth để tránh cảm giác "nảy" đột ngột.
            // Khi đổi bộ lọc (context === 'filter'): KHÔNG cuộn, giữ nguyên vị trí đang xem.
            if (context === 'pagination') {
                const productArea = document.getElementById('ajax-product-area');
                if (productArea) {
                    // Lấy vị trí thực của vùng danh sách sản phẩm, trừ thêm 12px padding trên để
                    // không bị thanh navbar che khuất sản phẩm đầu tiên.
                    const offsetTop = productArea.getBoundingClientRect().top + window.pageYOffset - 12;
                    window.scrollTo({ top: offsetTop, behavior: 'smooth' });
                }
            }
        })
        .catch(function () {
            // Fetch lỗi (mất mạng, server lỗi...) -> điều hướng thật như link bình thường, không kẹt trang
            window.location.href = url;
        });
}

// Bấm nút Back/Forward của trình duyệt sau khi đã chuyển trang qua AJAX -> tải lại đúng nội dung
// tương ứng URL hiện tại thay vì chỉ đổi URL trên thanh địa chỉ mà nội dung trang không đổi theo.
window.addEventListener('popstate', function () {
    if (document.getElementById('ajax-product-area')) {
        loadProductsPage(window.location.href, false, 'pagination');
    }
});

/**
 * Đổi bộ lọc (danh mục/giá/đánh giá/tìm kiếm) qua AJAX thay vì tải lại cả trang — tái sử dụng đúng
 * loadProductsPage() đã dùng cho phân trang, tránh trùng lặp logic thay DOM/chống lỗi tham chiếu cũ.
 * Đổi bộ lọc luôn quay về trang 1 (không giữ tham số page cũ, khớp hành vi form GET thông thường).
 */
const filterForm = document.getElementById('filter-form');
if (filterForm) {
    filterForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const params = new URLSearchParams(new FormData(filterForm));
        const url = filterForm.action + (params.toString() ? '?' + params.toString() : '');
        // context = 'filter': không cuộn trang, chỉ thay nội dung tại chỗ
        loadProductsPage(url, true, 'filter');

        // Trên di động, bộ lọc dạng sidebar trượt ra — đóng lại sau khi áp dụng để thấy ngay kết quả.
        const sidebar = document.querySelector('.p-sidebar');
        if (sidebar) sidebar.classList.remove('open');
    });
}
