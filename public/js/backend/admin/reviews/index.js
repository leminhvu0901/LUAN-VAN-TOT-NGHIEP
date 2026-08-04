/**
 * Lọc/tìm kiếm/phân trang nay là form GET/link thường (tải lại trang), không còn AJAX.
 * Phần còn lại (Checkbox + Bulk Delete, sẽ chuyển tiếp ở giai đoạn sau) vẫn dùng AJAX.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Khi DOM sẵn sàng: chuẩn bị các biến tham chiếu DOM và trạng thái
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const btnClearFilter = document.getElementById('btn-clear-filter');
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');

    // =====================
    // Checkbox logic
    // =====================
    window.selectedReviewIds = new Set();

    // Cập nhật trạng thái nút xóa hàng loạt và hiển thị số lượng đã chọn
    function updateBulkDeleteButton() {
        const count = window.selectedReviewIds.size;
        if (bulkDeleteContainer) {
            if (count > 0) {
                bulkDeleteContainer.classList.remove('hidden');
                bulkDeleteContainer.classList.add('flex');
            } else {
                bulkDeleteContainer.classList.remove('flex');
                bulkDeleteContainer.classList.add('hidden');
            }
        }
        const deselectBtn = document.getElementById('bulk-deselect-btn');
        if (deselectBtn) {
            if (count > 0) {
                deselectBtn.classList.remove('hidden');
                deselectBtn.classList.add('flex');
            } else {
                deselectBtn.classList.remove('flex');
                deselectBtn.classList.add('hidden');
            }
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
    }

    // Đồng bộ trạng thái các checkbox hàng theo Set `selectedReviewIds`
    function syncCheckboxes() {
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        allCheckboxes.forEach(cb => {
            cb.checked = window.selectedReviewIds.has(cb.value);
        });
        const selectAllEls = document.querySelectorAll('.js-select-all');
        if (selectAllEls.length > 0 && allCheckboxes.length > 0) {
            const isAllChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
            selectAllEls.forEach(el => el.checked = isAllChecked);
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('js-select-all')) {
            const checked = e.target.checked;
            // Đồng bộ trạng thái checkbox selectAll khác (nếu có)
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = checked);

            if (checked) {
                // Lấy tất cả ID của tất cả các trang dựa trên bộ lọc hiện tại
                const url = new URL(filterForm.action);
                const formData = new FormData(filterForm);
                formData.set('fetch_all_ids', '1');
                url.search = new URLSearchParams(formData).toString();

                document.querySelectorAll('.js-select-all').forEach(el => el.disabled = true);

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.ids) {
                            window.selectedReviewIds = new Set(data.ids.map(id => id.toString()));
                            syncCheckboxes();
                            updateBulkDeleteButton();
                        }
                    })
                    .catch(err => console.error('Lỗi khi lấy danh sách ID:', err))
                    .finally(() => {
                        document.querySelectorAll('.js-select-all').forEach(el => el.disabled = false);
                    });
            } else {
                window.selectedReviewIds.clear();
                syncCheckboxes();
                updateBulkDeleteButton();
            }
        }
        if (e.target && e.target.classList.contains('row-checkbox')) {
            if (e.target.checked) window.selectedReviewIds.add(e.target.value);
            else window.selectedReviewIds.delete(e.target.value);
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllEls = document.querySelectorAll('.js-select-all');
            if (selectAllEls.length > 0) {
                const isAllChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
                selectAllEls.forEach(el => el.checked = isAllChecked);
            }
            updateBulkDeleteButton();
        }
    });

    // =====================
    // AJAX filter + pagination
    // - Tải lại bảng review theo form lọc hoặc link phân trang (AJAX)
    // - Cập nhật URL history, hiển thị partial HTML khi cần
    // =====================
    // Tải dữ liệu bảng review (urlStr nếu truyền lên là link phân trang)
    window.fetchReviews = function (urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            url.search = new URLSearchParams(formData).toString();
        }

        window.history.pushState({}, '', url);
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
                if (data.html) {
                    tableContainer.innerHTML = data.html;
                }
                if (data.stats) {
                    window.updateStats(data.stats);
                }
                if (btnClearFilter) {
                    const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                        (key === 'search' && val !== '') ||
                        (key !== 'search' && key !== 'page' && val !== 'all' && val !== 'newest')
                    );
                    btnClearFilter.style.display = hasFilters ? 'inline-flex' : 'none';
                }
                syncCheckboxes();
                updateBulkDeleteButton();
            })
            .catch(error => console.error('Lỗi khi tải dữ liệu đánh giá:', error))
            .finally(() => {
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            });
    }

    // Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
    // (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

    tableContainer.addEventListener('click', function (e) {
        // Xóa đánh giá
        const deleteBtn = e.target.closest('.js-delete-review');
        if (deleteBtn) {
            e.preventDefault();
            const id = deleteBtn.getAttribute('data-id');
            if (window.AdminAlert) {
                window.AdminAlert.confirm('Bạn có chắc chắn muốn xóa đánh giá này không? Hành động này không thể hoàn tác.', function () {
                    doDelete(id);
                }, 'Xác nhận xóa?');
            } else {
                if (confirm('Bạn có chắc chắn muốn xóa đánh giá này không?')) doDelete(id);
            }
            return;
        }

        // Ẩn/Hiện đánh giá
        const toggleBtn = e.target.closest('.js-toggle-visibility');
        if (toggleBtn) {
            e.preventDefault();
            const id = toggleBtn.getAttribute('data-id');
            const url = toggleBtn.getAttribute('data-url');
            doToggleVisibility(id, url, toggleBtn);
            return;
        }
    });

    // =====================
    // Bulk Delete
    // =====================
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = Array.from(window.selectedReviewIds);
            if (ids.length === 0) return;

            if (window.AdminAlert) {
                window.AdminAlert.confirm(`Bạn có chắc chắn muốn xóa ${ids.length} đánh giá đã chọn?`, function () {
                    doBulkDelete(ids);
                }, 'Xác nhận xóa hàng loạt?');
            } else {
                if (confirm(`Bạn có chắc chắn muốn xóa ${ids.length} đánh giá đã chọn?`)) doBulkDelete(ids);
            }
        });
    }

    const deselectBtn = document.getElementById('bulk-deselect-btn');
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            window.selectedReviewIds.clear();
            updateBulkDeleteButton();
        });
    }
});

/**
 * Hàm gọi API chuyển trạng thái Ẩn/Hiện đánh giá
 */
function doToggleVisibility(id, url, btn) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (btn) {
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (btn) {
                    if (data.new_status) {
                        btn.outerHTML = `
                        <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-bold text-[11px] lg:text-xs border border-emerald-100 hover:bg-emerald-100 transition-colors" data-id="${id}" data-url="${url}">
                            <span class="material-symbols-outlined text-[14px] lg:text-[16px]" style="font-variation-settings: 'FILL' 1;">visibility</span>
                            Đang hiển thị
                        </button>`;
                    } else {
                        btn.outerHTML = `
                        <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-lg font-bold text-[11px] lg:text-xs border border-rose-100 hover:bg-rose-100 transition-colors" data-id="${id}" data-url="${url}">
                            <span class="material-symbols-outlined text-[14px] lg:text-[16px]" style="font-variation-settings: 'FILL' 1;">visibility_off</span>
                            Đang ẩn
                        </button>`;
                    }
                }
                if (data.stats) {
                    window.updateStats(data.stats);
                }
            } else {
                if (window.AdminAlert) window.AdminAlert.error('Cập nhật trạng thái thất bại!', 'Lỗi');
            }
        })
        .catch(() => {
            if (window.AdminAlert) window.AdminAlert.error('Có lỗi xảy ra khi cập nhật trạng thái.', 'Lỗi');
        })
        .finally(() => {
            if (btn && btn.parentNode) { // Check parentNode just in case outerHTML wasn't successful
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
}

/**
 * Xóa đánh giá bằng AdminAlert
 */
function doDelete(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const deleteUrl = window.location.origin + '/admin/reviews/' + id;

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.AdminAlert) window.AdminAlert.success(data.message || 'Xóa thành công!', 'Đã xóa!');
                window.selectedReviewIds.delete(id.toString());

                if (typeof window.fetchReviews === 'function') {
                    window.fetchReviews();
                } else {
                    window.location.reload();
                }
            } else {
                if (window.AdminAlert) window.AdminAlert.error(data.message || 'Xóa thất bại. Vui lòng thử lại.', 'Thất bại');
            }
        })
        .catch(() => {
            if (window.AdminAlert) window.AdminAlert.error('Có lỗi xảy ra khi xóa. Vui lòng thử lại.', 'Lỗi');
        });
}

function doBulkDelete(ids) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const bulkDeleteUrl = document.getElementById('bulk-delete-form')?.action || (window.location.origin + '/admin/reviews/bulk-delete');

    fetch(bulkDeleteUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ review_ids: ids })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.AdminAlert) window.AdminAlert.success(data.message || 'Xóa hàng loạt thành công!', 'Đã xóa!');
                window.selectedReviewIds.clear();

                if (typeof window.fetchReviews === 'function') {
                    window.fetchReviews();
                } else {
                    window.location.reload();
                }
            } else {
                if (window.AdminAlert) window.AdminAlert.error(data.message || 'Xóa thất bại. Vui lòng thử lại.', 'Thất bại');
            }
        })
        .catch(() => {
            if (window.AdminAlert) window.AdminAlert.error('Có lỗi xảy ra khi xóa hàng loạt. Vui lòng thử lại.', 'Lỗi');
        });
}

/**
 * Cập nhật số liệu thống kê ở đầu trang
 */
window.updateStats = function (stats) {
    if (!stats) return;
    const totalEl = document.getElementById('stat-total-reviews');
    const activeEl = document.getElementById('stat-active-reviews');
    const hiddenEl = document.getElementById('stat-hidden-reviews');
    if (totalEl && stats.total !== undefined) totalEl.textContent = stats.total;
    if (activeEl && stats.active !== undefined) activeEl.textContent = stats.active;
    if (hiddenEl && stats.hidden !== undefined) hiddenEl.textContent = stats.hidden;
};
