/**
 * index.js - Xử lý danh sách danh mục sản phẩm ở khu vực Admin
 * Lọc/tìm kiếm/phân trang nay là form GET/link thường (tải lại trang), không còn AJAX.
 * Các tính năng còn lại (sẽ chuyển tiếp ở giai đoạn sau):
 * - Xóa đơn lẻ danh mục và tạo hiệu ứng CSS trượt biến mất.
 * - Xóa hàng loạt danh mục đã chọn (Bulk Delete) có hộp thoại xác nhận.
 * - Tự động đồng bộ và giữ trạng thái tích chọn checkbox sau khi tải AJAX.
 */
document.addEventListener('DOMContentLoaded', function () {
    // === PHẦN TỬ DOM ===
    const filterForm = document.getElementById('filter-form'); // Form bộ lọc đầu trang
    const tableContainer = document.getElementById('table-container'); // Khung chứa bảng danh mục
    const btnClearFilter = document.getElementById('btn-clear-filter'); // Nút xóa lọc

    /**
     * Gọi AJAX lấy dữ liệu danh sách danh mục từ server
     * @param {string|null} urlStr - URL phân trang cần chuyển tiếp, null để lấy theo bộ lọc form hiện tại
     */
    function fetchCategories(urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            url.search = searchParams.toString();
        }

        // Cập nhật thanh địa chỉ URL của trình duyệt
        window.history.pushState({}, '', url);

        // Hiển thị hiệu ứng mờ loading
        const loader = document.getElementById('table-loader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.classList.add('flex');
        } else {
            tableContainer.style.opacity = '0.5';
            tableContainer.style.pointerEvents = 'none';
        }

        // Gọi request lấy dữ liệu dạng JSON
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Cập nhật thống kê số lượng danh mục
                if (data.total !== undefined) {
                    const totalStat = document.getElementById('total-categories-stat');
                    if (totalStat) totalStat.innerText = data.total;
                }
                if (data.active !== undefined) {
                    const activeStat = document.getElementById('active-categories-stat');
                    if (activeStat) activeStat.innerText = data.active;
                }
                if (data.inactive !== undefined) {
                    const inactiveStat = document.getElementById('inactive-categories-stat');
                    if (inactiveStat) inactiveStat.innerText = data.inactive;
                }
                
                // Ghi đè mã HTML bảng mới vào view
                if (data.html) {
                    const tableWrapper = document.getElementById("categories-table-wrapper");
                    if (tableWrapper) {
                        tableWrapper.innerHTML = data.html;
                    } else {
                        tableContainer.innerHTML = data.html;
                    }
                    document.dispatchEvent(new Event("tableDataLoaded"));
                }

                // Hiện/Ẩn nút xóa bộ lọc dựa trên tham số đang chạy
                if (btnClearFilter) {
                    const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                        (key === 'search' && val !== '') ||
                        (key === 'status' && val !== 'all') ||
                        (key === 'sort' && val !== 'order_asc')
                    );
                    btnClearFilter.style.display = hasFilters ? 'inline-block' : 'none';
                }
            })
            .catch(error => console.error('Lỗi khi tải dữ liệu danh mục:', error))
            .finally(() => {
                // Ẩn vòng xoay loading
                if (loader) {
                    loader.classList.add('hidden');
                    loader.classList.remove('flex');
                }
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            });
    }

    // Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
    // (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

    // Sử dụng cơ chế ủy quyền sự kiện (Event Delegation) trên bảng dữ liệu
    tableContainer.addEventListener('click', function (e) {
        // Click nút xóa một danh mục đơn lẻ
        const deleteBtn = e.target.closest('.delete-category-btn');
        if (deleteBtn) {
            e.preventDefault();
            const id = deleteBtn.dataset.id;
            const url = deleteBtn.dataset.url;
            
            // Lấy tên danh mục để đưa vào câu hỏi xác nhận
            let name = 'danh mục này';
            const row = deleteBtn.closest('tr') || deleteBtn.closest('.bg-white.p-4.rounded-2xl');
            if (row) {
                const nameEl = row.querySelector('.font-bold');
                if (nameEl) name = nameEl.innerText.trim();
            }

            deleteCategoryAjax(id, name, url);
        }
    });

    window.fetchCategories = fetchCategories;
});

/**
 * Hiển thị hộp thoại xác nhận trước khi xóa danh mục đơn lẻ
 */
function deleteCategoryAjax(id, name, url) {
    window.AdminAlert.confirm(
        `Bạn có chắc muốn xóa danh mục "${name}"? Hành động này không thể hoàn tác.`,
        function () { doDeleteAjax(id, url); },
        'Xóa danh mục?'
    );
}

/**
 * Đặt lại trạng thái chọn nhiều dòng của danh mục về ban đầu
 */
window.resetCategorySelection = function() {
    window.isGlobalSelectAll = false;
    if (window.selectedCategoryIds) window.selectedCategoryIds.clear();
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeselectBtn = document.getElementById('bulk-deselect-btn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.classList.add('hidden');
        bulkDeleteBtn.classList.remove('flex');
    }
    if (bulkDeselectBtn) {
        bulkDeselectBtn.classList.add('hidden');
        bulkDeselectBtn.classList.remove('flex');
    }
};

/**
 * Thực thi gửi request DELETE AJAX xóa danh mục
 */
function doDeleteAjax(id, url) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    fetch(url, {
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
                // Xóa dòng dữ liệu khỏi bảng với hiệu ứng trượt biến mất mượt mà
                const row = document.getElementById('category-row-' + id);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => row.remove(), 300);
                }

                if (window.resetCategorySelection) window.resetCategorySelection();
                window.AdminAlert.success(data.message, 'Đã xóa!');
            } else {
                window.AdminAlert.error(data.message, 'Không thể xóa');
            }
        })
        .catch(() => {
            window.AdminAlert.error('Có lỗi xảy ra khi xóa. Vui lòng thử lại.');
        });
}

// ============================================================
// XỬ LÝ CHỌN NHIỀU VÀ XÓA HÀNG LOẠT (BULK DELETE CATEGORIES)
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('table-container');
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');

    window.isGlobalSelectAll = false;
    window.selectedCategoryIds = new Set(); // Set lưu trữ danh sách ID được chọn

    /**
     * Cập nhật số hiển thị và ẩn/hiện nút "Xóa nhiều" dựa trên số lượng tích chọn
     */
    function updateBulkDeleteButton() {
        const selectAllEls = document.querySelectorAll('.js-select-all');
        const isSelectAll = selectAllEls.length > 0 ? selectAllEls[0].checked : false;
        let countText = window.selectedCategoryIds.size;
        
        if (window.isGlobalSelectAll) {
            const totalInput = document.getElementById('total-categories-count');
            if (totalInput) countText = totalInput.value;
        }

        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const bulkDeselectBtn = document.getElementById('bulk-deselect-btn');

        if (countText > 0) {
            if(selectedCountSpan) selectedCountSpan.textContent = countText;
            if (bulkDeleteBtn) {
                bulkDeleteBtn.classList.remove('hidden');
                bulkDeleteBtn.classList.add('flex');
            }
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.remove('hidden');
                bulkDeselectBtn.classList.add('flex');
            }
        } else {
            if (bulkDeleteBtn) {
                bulkDeleteBtn.classList.add('hidden');
                bulkDeleteBtn.classList.remove('flex');
            }
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.add('hidden');
                bulkDeselectBtn.classList.remove('flex');
            }
        }
    }

    if(tableContainer) {
        tableContainer.addEventListener('change', function(e) {
            // 1. Tích chọn / bỏ chọn hộp "Chọn tất cả" ở đầu bảng
            if (e.target.classList.contains('js-select-all')) {
                const isChecked = e.target.checked;
                window.isGlobalSelectAll = isChecked;
                document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);
                if (!isChecked) {
                    window.selectedCategoryIds.clear();
                }
                document.querySelectorAll('.row-checkbox:not(:disabled)').forEach(cb => {
                    cb.checked = isChecked;
                    if (isChecked) window.selectedCategoryIds.add(cb.value);
                });
                updateBulkDeleteButton();
            } 
            // 2. Tích chọn từng dòng danh mục đơn lẻ
            else if (e.target.classList.contains('row-checkbox')) {
                if (e.target.checked) {
                    window.selectedCategoryIds.add(e.target.value);
                } else {
                    window.selectedCategoryIds.delete(e.target.value);
                    window.isGlobalSelectAll = false;
                }
                const allCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                const allChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:not(:disabled):checked').length === allCheckboxes.length;
                document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
                updateBulkDeleteButton();
            }
        });
    }

    // Khôi phục trạng thái Checkbox của người dùng sau khi fetch AJAX bằng MutationObserver
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                if (window.isGlobalSelectAll) {
                    document.querySelectorAll('.js-select-all').forEach(el => el.checked = true);
                    
                    document.querySelectorAll('.row-checkbox').forEach(cb => {
                        cb.checked = true;
                        window.selectedCategoryIds.add(cb.value);
                    });
                } else {
                    document.querySelectorAll('.row-checkbox').forEach(cb => {
                        if (window.selectedCategoryIds.has(cb.value)) {
                            cb.checked = true;
                        }
                    });
                    const allCheckboxes = document.querySelectorAll('.row-checkbox');
                    if (allCheckboxes.length > 0) {
                        const allChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
                        document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
                    }
                }
                updateBulkDeleteButton();
            }
        });
    });

    if (tableContainer) {
        observer.observe(tableContainer, { childList: true, subtree: true });
    }

    // Hộp thoại xác nhận trước khi thực hiện xóa nhiều danh mục
    window.submitBulkDelete = function() {
        const selectAllEls = document.querySelectorAll('.js-select-all');
        const isSelectAll = selectAllEls.length > 0 ? selectAllEls[0].checked : false;

        let titleText = 'Xác nhận xóa nhiều?';
        let messageText = `Bạn chuẩn bị xóa ${window.selectedCategoryIds.size} danh mục đã chọn.`;

        if (isSelectAll) {
            titleText = 'Xác nhận xóa tất cả?';
            let countText = window.selectedCategoryIds.size;
            const totalInput = document.getElementById('total-categories-count');
            if (totalInput) countText = totalInput.value;
            messageText = `Bạn chuẩn bị xóa TẤT CẢ ${countText} danh mục trùng khớp với bộ lọc.`;
        }

        window.AdminAlert.confirm(messageText, function () {
            executeBulkDelete(isSelectAll);
        }, titleText);
    };

    const submitBtn = document.getElementById('bulk-delete-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', window.submitBulkDelete);
    }

    /**
     * Gửi request POST AJAX thực thi xóa hàng loạt danh mục
     */
    function executeBulkDelete(isSelectAll) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '';
        
        const form = document.getElementById('bulk-delete-form');
        const deleteUrl = form.action;

        let data = {};
        if (isSelectAll) {
            data.delete_all_pages = '1';
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of urlParams.entries()) {
                if (key !== 'page' && value !== '') {
                    data[key] = value;
                }
            }
        } else {
            data.category_ids = Array.from(window.selectedCategoryIds);
        }

        fetch(deleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.success) {
                window.AdminAlert.success(resData.message, 'Đã xóa!');
                resetCategorySelection();
                if (typeof window.fetchCategories === 'function') {
                    window.fetchCategories();
                } else {
                    window.location.reload();
                }
            } else {
                window.AdminAlert.error(resData.message || 'Có lỗi xảy ra.');
            }
        })
        .catch(err => {
            console.error(err);
            window.AdminAlert.error('Lỗi kết nối.');
        });
    }
});
