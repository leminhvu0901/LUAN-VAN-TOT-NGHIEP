/**
 * File Javascript điều khiển tương tác trên trang Danh sách sản phẩm
 * Sắp xếp (client-side, không AJAX) trên bộ thẻ sản phẩm của trang hiện tại.
 * Lọc theo danh mục/giá/đánh giá/tìm kiếm và phân trang nay là form GET/link thường (tải lại trang).
 */

/**
 * Bật/tắt thanh bộ lọc bên sườn (Sidebar) trên giao diện di động
 */
function toggleFilter() {
    const sidebar = document.querySelector('.p-sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

/**
 * Cập nhật nhãn văn bản hiển thị giá tiền và đổ màu nền động cho thanh kéo (slider)  */
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

// Phân trang và bộ lọc (danh mục/giá/đánh giá/tìm kiếm) nay là link/form GET thường (tải lại trang),
// không còn AJAX. Trên di động, đóng sidebar lọc lại sau khi bấm "Áp dụng" để thấy ngay kết quả.
const filterForm = document.getElementById('filter-form');
if (filterForm) {
    filterForm.addEventListener('submit', function () {
        const sidebar = document.querySelector('.p-sidebar');
        if (sidebar) sidebar.classList.remove('open');
    });
}
