/**
 * Xử lý lọc danh sách sản phẩm bằng AJAX 
 */
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const displayCount = document.getElementById('display-count');
    const btnClearFilter = document.getElementById('btn-clear-filter');

    // Hàm gọi AJAX lấy dữ liệu
    function fetchProducts(urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            url.search = searchParams.toString();
        }

        // Cập nhật thanh địa chỉ trình duyệt để user có thể copy link hoặc F5
        window.history.pushState({}, '', url);

        // Hiển thị trạng thái mờ (loading)
        tableContainer.style.opacity = '0.5';
        tableContainer.style.pointerEvents = 'none';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Cập nhật giao diện bảng
                if (data.html) {
                    tableContainer.innerHTML = data.html;
                }

                // Cập nhật text hiển thị số lượng
                if (displayCount && data.count_text) {
                    displayCount.innerHTML = data.count_text;
                }

                // Hiện / Ẩn nút Xóa lọc dựa trên các tham số
                if (btnClearFilter) {
                    const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                        (key === 'search' && val !== '') ||
                        (key !== 'search' && key !== 'page' && val !== 'all' && val !== 'newest')
                    );
                    btnClearFilter.style.display = hasFilters ? 'inline-block' : 'none';
                }
            })
            .catch(error => console.error('Lỗi khi tải dữ liệu sản phẩm:', error))
            .finally(() => {
                // Bỏ trạng thái mờ
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            });
    }

    // 1. Lắng nghe sự thay đổi của Dropdown (Select)
    filterForm.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => fetchProducts());
    });

    // 2. Lắng nghe gõ phím ô Tìm kiếm 
    let timeout = null;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchProducts(), 400); // Đợi 0.4s sau khi ngừng gõ mới gọi
        });
    }

    // 3. Xử lý nút Xóa lọc
    if (btnClearFilter) {
        btnClearFilter.addEventListener('click', function (e) {
            e.preventDefault();
            filterForm.reset();
            // Reset thủ công các select về giá trị mặc định
            filterForm.querySelectorAll('select').forEach(s => {
                if (s.name === 'sort') s.value = 'newest';
                else s.value = 'all';
            });
            if (searchInput) searchInput.value = '';

            fetchProducts();
        });
    }

    // 4. Xử lý Click Phân trang (Pagination) bằng Event Delegation
    tableContainer.addEventListener('click', function (e) {
        // Tìm thẻ <a> cha gần nhất nếu click vào icon/text bên trong
        const pageLink = e.target.closest('.pagination-container a');
        if (pageLink) {
            e.preventDefault(); // Ngăn trình duyệt chuyển trang
            fetchProducts(pageLink.href); // Gọi AJAX với đường dẫn của trang mới
        }
    });
});
