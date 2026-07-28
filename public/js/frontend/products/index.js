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
    // Trên màn hình máy tính (> 640px) -> tự động gửi form khi thay đổi tích chọn danh mục
    if (window.innerWidth > 640) document.getElementById('filter-form').submit();
}

/**
 * Gửi dữ liệu biểu mẫu lọc lên máy chủ
 */
function submitFilterForm() {
    // Trên máy tính -> Tự động submit. Trên điện thoại -> Người dùng phải bấm nút Áp dụng
    if (window.innerWidth > 640) document.getElementById('filter-form').submit();
}

// Tham chiếu các phần tử điều khiển sắp xếp và lưới sản phẩm
const sortSelect = document.getElementById('sort-select');
// "let" (không phải const) vì #product-grid bị THAY THẾ hoàn toàn (không chỉ sửa nội dung) mỗi lần
// chuyển trang qua AJAX bên dưới -> node cũ bị gỡ khỏi DOM, phải gán lại biến này trỏ sang node MỚI,
// nếu không applySortAndFilter() sẽ thao tác nhầm lên node đã "chết", không còn hiển thị trên trang.
let grid = document.getElementById('product-grid');
const pillButtons = document.querySelectorAll('#product-pill-filters .home-popular__filter-btn');
let currentPillFilter = 'all'; // Bộ lọc tag mặc định (Tất cả)

/**
 * Thực thi sắp xếp (Sort) và lọc (Filter) sản phẩm ngay tại client
 */
function applySortAndFilter() {
    if (!sortSelect || !grid) return;
    const sortBy = sortSelect.value;
    // Chuyển danh sách NodeList các thẻ sản phẩm thành Mảng (Array) để sắp xếp
    const cards = Array.from(grid.querySelectorAll('.p-product-card'));

    // Sắp xếp thứ tự các thẻ sản phẩm dựa vào tiêu chí được chọn
    cards.sort((a, b) => {
        if (sortBy === 'popular') return parseInt(b.dataset.sold || 0) - parseInt(a.dataset.sold || 0); // Bán chạy nhất
        if (sortBy === 'price-asc') return parseFloat(a.dataset.priceVal || 0) - parseFloat(b.dataset.priceVal || 0); // Giá thấp đến cao
        if (sortBy === 'price-desc') return parseFloat(b.dataset.priceVal || 0) - parseFloat(a.dataset.priceVal || 0); // Giá cao đến thấp
        if (sortBy === 'newest') return parseInt(b.dataset.date || 0) - parseInt(a.dataset.date || 0); // Mới nhất
        if (sortBy === 'rating') return parseFloat(b.dataset.ratingVal || 0) - parseFloat(a.dataset.ratingVal || 0); // Điểm sao đánh giá cao nhất
        return 0;
    });

    let visibleCount = 0; // Đếm số sản phẩm đang hiển thị
    
    // Duyệt qua từng thẻ sản phẩm để sắp xếp lại vị trí DOM và ẩn/hiện theo bộ lọc tag nhanh
    cards.forEach(card => {
        grid.appendChild(card); // Đắp lại phần tử vào lưới theo thứ tự mới đã sort
        
        let isMatch = true;
        // Kiểm tra điều kiện lọc tag nhanh
        if (currentPillFilter === 'hot') isMatch = card.dataset.isHot === '1';
        else if (currentPillFilter === 'new') isMatch = card.dataset.isNew === '1';
        
        // Ẩn hoặc hiển thị sản phẩm
        card.style.display = isMatch ? '' : 'none';
        if (isMatch) visibleCount++;

        // Điều chỉnh nhãn (badge) hiển thị tối ưu theo kiểu sắp xếp
        const hotBadge = card.querySelector('.home-prod-card__badge--hot');
        const newBadge = card.querySelector('.home-prod-card__badge--new');
        if (sortBy === 'newest') {
            if (newBadge) { newBadge.style.display = ''; if (hotBadge) hotBadge.style.display = 'none'; }
            else if (hotBadge) hotBadge.style.display = '';
        } else {
            if (hotBadge) { hotBadge.style.display = ''; if (newBadge) newBadge.style.display = 'none'; }
            else if (newBadge) newBadge.style.display = '';
        }
    });

    // Quản lý hiển thị thông báo rỗng khi không có sản phẩm nào khớp bộ lọc
    let emptyMsg = document.getElementById('empty-product-msg');
    if (visibleCount === 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.id = 'empty-product-msg';
            // Sử dụng class CSS chuẩn đã định nghĩa trong users.css thay vì viết code style inline
            emptyMsg.className = 'p-product-grid-empty';
            emptyMsg.textContent = 'Không tìm thấy sản phẩm nào phù hợp với bộ lọc.';
            grid.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = '';
            grid.appendChild(emptyMsg);
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
}

// Đăng ký sự kiện lắng nghe tương tác nếu tồn tại các phần tử điều khiển trên trang
if (sortSelect && grid) {
    // Lắng nghe sự thay đổi của hộp chọn Sắp xếp
    sortSelect.addEventListener('change', applySortAndFilter);
    
    // Gắn sự kiện click cho các nút lọc tag nhanh (Tất cả, Bán chạy, Mới nhất)
    pillButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Gỡ bỏ class active cũ và kích hoạt màu nổi bật cho nút vừa nhấp
            pillButtons.forEach(b => b.classList.remove('home-popular__filter-btn--active'));
            this.classList.add('home-popular__filter-btn--active');
            currentPillFilter = this.dataset.filter;
            // Thực thi lọc lại danh sách sản phẩm
            applySortAndFilter();
        });
    });
    
    // Tự động chạy sắp xếp mặc định lần đầu khi tải trang
    applySortAndFilter();
}

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
    loadProductsPage(url);
});

function loadProductsPage(url, updateHistory) {
    if (updateHistory === undefined) updateHistory = true;

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

            outerParent.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        loadProductsPage(window.location.href, false);
    }
});
