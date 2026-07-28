/**
 * Trang "Sản phẩm" của lễ tân — lọc theo từ khóa/trạng thái và chuyển trang qua fetch thay vì tải lại
 * cả trang, khớp cách trang /products phía khách hàng đã làm.
 */
(function () {
    'use strict';

    function loadGrid(url, updateHistory) {
        if (updateHistory === undefined) updateHistory = true;
        const area = document.getElementById('reception-products-grid-area');
        if (!area) {
            window.location.href = url;
            return;
        }
        const wrapper = area.parentElement;
        wrapper.style.opacity = '0.5';

        fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (response) {
                if (!response.ok) throw new Error('Request failed');
                return response.text();
            })
            .then(function (html) {
                wrapper.innerHTML = html;
                wrapper.style.opacity = '';
                if (updateHistory) history.pushState({}, '', url);
                // KHÔNG tự cuộn trang — tránh cảm giác "nảy" giao diện đột ngột khi đổi bộ lọc/trang.
            })
            .catch(function () {
                window.location.href = url;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('reception-products-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const params = new URLSearchParams(new FormData(filterForm));
                const url = filterForm.action + (params.toString() ? '?' + params.toString() : '');
                loadGrid(url);
            });
        }
    });

    // Ủy quyền sự kiện: các link phân trang nằm TRONG #reception-products-grid-area, vùng này bị
    // thay thế hoàn toàn mỗi lần tải — gắn listener trên document để không bị mất theo.
    document.addEventListener('click', function (event) {
        const area = document.getElementById('reception-products-grid-area');
        if (!area) return;
        const link = event.target.closest('#reception-products-grid-area .pagination a, #reception-products-grid-area nav a');
        if (!link) return;
        const url = link.getAttribute('href');
        if (!url) return;

        event.preventDefault();
        loadGrid(url);
    });

    window.addEventListener('popstate', function () {
        if (document.getElementById('reception-products-grid-area')) {
            loadGrid(window.location.href, false);
        }
    });
})();
