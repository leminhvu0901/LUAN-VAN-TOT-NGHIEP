/**
 * Xử lý lọc danh sách đánh giá bằng AJAX + Checkbox + Bulk Delete
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
            bulkDeleteContainer.style.display = count > 0 ? 'block' : 'none';
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
        const selectAllEl = document.getElementById('selectAll');
        if (selectAllEl && allCheckboxes.length > 0) {
            selectAllEl.checked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'selectAll') {
            const checked = e.target.checked;
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = checked;
                if (checked) window.selectedReviewIds.add(cb.value);
                else window.selectedReviewIds.delete(cb.value);
            });
            updateBulkDeleteButton();
        }
        if (e.target && e.target.classList.contains('row-checkbox')) {
            if (e.target.checked) window.selectedReviewIds.add(e.target.value);
            else window.selectedReviewIds.delete(e.target.value);
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllEl = document.getElementById('selectAll');
            if (selectAllEl) {
                selectAllEl.checked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
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
    function fetchReviews(urlStr = null) {
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

    filterForm.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => fetchReviews());
    });

    let timeout = null;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchReviews(), 400);
        });
    }

    if (btnClearFilter) {
        btnClearFilter.addEventListener('click', function (e) {
            e.preventDefault();
            filterForm.reset();
            filterForm.querySelectorAll('select').forEach(s => {
                if (s.name === 'sort') s.value = 'newest';
                else s.value = 'all';
            });
            if (searchInput) searchInput.value = '';
            fetchReviews();
        });
    }

    tableContainer.addEventListener('click', function (e) {
        const pageLink = e.target.closest('.pagination-container a');
        if (pageLink) {
            e.preventDefault();
            fetchReviews(pageLink.href);
        }
    });

    // =====================
    // Bulk Delete
    // =====================
    window.submitBulkDelete = function () {
        const count = window.selectedReviewIds.size;
        if (count === 0) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Xóa nhiều đánh giá?',
                text: `Bạn chuẩn bị xóa ${count} đánh giá đã chọn. Hành động này không thể hoàn tác.`,
                icon: 'warning',
                width: '320px',
                padding: '1rem',
                showCancelButton: true,
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-base font-bold text-gray-800',
                    htmlContainer: 'text-sm text-gray-500 mt-1',
                    confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm border-none outline-none ml-2',
                    cancelButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all border-none outline-none mr-2',
                    icon: 'transform scale-[0.6] -mt-3 -mb-2',
                    actions: 'mt-3 w-full flex justify-center'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeBulkDelete();
                }
            });
        } else {
            if (confirm(`Xóa ${count} đánh giá đã chọn?`)) {
                executeBulkDelete();
            }
        }
    };

    // Tạo form dữ liệu và submit để xóa hàng loạt theo các id đã chọn
    function executeBulkDelete() {
        const form = document.getElementById('bulk-delete-form');
        form.querySelectorAll('input[name="review_ids[]"]').forEach(el => el.remove());

        window.selectedReviewIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'review_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        form.submit();
    }
});

/**
 * Hàm gọi API chuyển trạng thái Ẩn/Hiện đánh giá
 */
// Gọi API đổi trạng thái hiển thị của một đánh giá và cập nhật nút
window.toggleVisibility = function (id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const toggleUrl = window.location.origin + '/admin/reviews/' + id + '/toggle-visibility';
    const btn = document.getElementById('btn-toggle-' + id);

    if (btn) {
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
    }

    fetch(toggleUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (btn) {
                    if (data.new_status) {
                        btn.innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-semibold text-xs border border-emerald-100 hover:bg-emerald-100 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            Hiển thị
                        </span>`;
                    } else {
                        btn.innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-lg font-semibold text-xs border border-rose-100 hover:bg-rose-100 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                            Bị ẩn
                        </span>`;
                    }
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error', title: 'Lỗi',
                        text: 'Cập nhật trạng thái thất bại!',
                        width: '320px', padding: '1rem',
                        confirmButtonText: 'Đóng', buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                } else {
                    alert('Cập nhật trạng thái thất bại!');
                }
            }
        })
        .catch(() => alert('Có lỗi xảy ra khi cập nhật trạng thái.'))
        .finally(() => {
            if (btn) {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
}

/**
 * Xóa đánh giá bằng SweetAlert2
 */
// Hiển thị hộp thoại xác nhận (SweetAlert2) trước khi gọi xóa
window.deleteReview = function (id) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Xóa đánh giá?',
            html: `Bạn có chắc muốn xóa đánh giá này?<br><span class="text-xs text-gray-400">Hành động này không thể hoàn tác.</span>`,
            icon: 'warning',
            width: '320px',
            padding: '1rem',
            showCancelButton: true,
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy',
            reverseButtons: true,
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-xl shadow-xl border border-gray-100',
                title: 'text-base font-bold text-gray-800',
                htmlContainer: 'text-sm text-gray-500 mt-1',
                confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm border-none outline-none ml-2',
                cancelButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all border-none outline-none mr-2',
                icon: 'transform scale-[0.6] -mt-3 -mb-2',
                actions: 'mt-3 w-full flex justify-center'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doDelete(id);
            }
        });
    } else {
        if (confirm('Xóa đánh giá này?')) {
            doDelete(id);
        }
    }
}

// Thực hiện gọi API xóa một đánh giá, cập nhật UI (ẩn/loại bỏ row) và thông báo
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
                const row = document.getElementById('review-row-' + id);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 300);
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false,
                        width: '320px',
                        padding: '1rem',
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                        }
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error', title: 'Thất bại',
                        text: 'Xóa thất bại. Vui lòng thử lại.',
                        width: '320px', padding: '1rem',
                        confirmButtonText: 'Đóng', buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                } else {
                    alert('Xóa thất bại. Vui lòng thử lại.');
                }
            }
        })
        .catch(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error', title: 'Lỗi',
                    text: 'Có lỗi xảy ra khi xóa. Vui lòng thử lại.',
                    width: '320px', padding: '1rem',
                    confirmButtonText: 'Đóng', buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-gray-100',
                        title: 'text-base font-bold text-gray-800',
                        confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                        icon: 'transform scale-[0.6] -mt-3 -mb-2',
                        actions: 'mt-3 w-full flex justify-center'
                    }
                });
            } else {
                alert('Có lỗi xảy ra khi xóa. Vui lòng thử lại.');
            }
        });
}
