/**
 * Xử lý lọc danh sách danh mục bằng AJAX
 */
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const btnClearFilter = document.getElementById('btn-clear-filter');

    // Hàm gọi AJAX lấy dữ liệu
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

        // Cập nhật thanh địa chỉ trình duyệt
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
                if (data.html) {
                    tableContainer.innerHTML = data.html;
                }

                // Hiện / Ẩn nút Xóa lọc
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
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            });
    }

    // Lắng nghe thay đổi dropdown
    filterForm.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => fetchCategories());
    });

    // Tìm kiếm với debounce
    let timeout = null;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchCategories(), 400);
        });
    }

    // Nút Xóa lọc
    if (btnClearFilter) {
        btnClearFilter.addEventListener('click', function (e) {
            e.preventDefault();
            filterForm.reset();
            filterForm.querySelectorAll('select').forEach(s => {
                if (s.name === 'sort') s.value = 'order_asc';
                else s.value = 'all';
            });
            if (searchInput) searchInput.value = '';
            fetchCategories();
        });
    }

    // Phân trang bằng AJAX
    tableContainer.addEventListener('click', function (e) {
        const pageLink = e.target.closest('.pagination-container a');
        if (pageLink) {
            e.preventDefault();
            fetchCategories(pageLink.href);
        }
    });
});

/**
 * Xóa danh mục bằng SweetAlert2
 */
function deleteCategory(id, name) {
    const confirmMsg = `Bạn có chắc chắn muốn xóa danh mục "${name}"?\nHành động này không thể hoàn tác.`;

    if (typeof Swal !== 'undefined') {
        let config = window.swalConfig || {
            icon: 'warning',
            width: '320px',
            padding: '1rem',
            showCancelButton: true,
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-xl shadow-xl border border-gray-100',
                title: 'text-base font-bold text-gray-800',
                htmlContainer: 'text-sm text-gray-500 mt-1',
                confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm border-none outline-none ml-2',
                cancelButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all border-none outline-none mr-2',
                icon: 'transform scale-[0.6] -mt-3 -mb-2',
                actions: 'mt-3 w-full flex justify-center'
            },
            buttonsStyling: false
        };

        Swal.fire({
            ...config,
            title: 'Xóa danh mục?',
            html: `Bạn có chắc muốn xóa danh mục <strong class="font-bold">${name}</strong>?<br>Hành động này không thể hoàn tác.`
        }).then((result) => {
            if (result.isConfirmed) {
                doDelete(id);
            }
        });
    } else {
        if (confirm(confirmMsg)) {
            doDelete(id);
        }
    }
}

function doDelete(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const deleteUrl = window.location.origin + '/admin/categories/' + id;

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Xóa dòng khỏi bảng với hiệu ứng
                const row = document.getElementById('category-row-' + id);
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
                            htmlContainer: 'text-sm text-gray-500 mt-1',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                        }
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Không thể xóa',
                        text: data.message,
                        width: '320px',
                        padding: '1rem',
                        confirmButtonText: 'Đóng',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl shadow-xl border border-gray-100',
                            title: 'text-base font-bold text-gray-800',
                            htmlContainer: 'text-sm text-gray-500 mt-1',
                            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                            icon: 'transform scale-[0.6] -mt-3 -mb-2',
                            actions: 'mt-3 w-full flex justify-center'
                        }
                    });
                } else {
                    alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
                }
            }
        })
        .catch(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Có lỗi xảy ra khi xóa. Vui lòng thử lại.',
                    width: '320px',
                    padding: '1rem',
                    confirmButtonText: 'Đóng',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-gray-100',
                        title: 'text-base font-bold text-gray-800',
                        htmlContainer: 'text-sm text-gray-500 mt-1',
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

// ============================================================
// Xử lý Xóa nhiều cho Danh mục
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('table-container');
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');

    window.isGlobalSelectAll = false;
    window.selectedCategoryIds = new Set();

    function updateBulkDeleteButton() {
        const isSelectAll = document.getElementById('selectAll') ? document.getElementById('selectAll').checked : false;
        let countText = window.selectedCategoryIds.size;
        
        if (window.isGlobalSelectAll) {
            const totalInput = document.getElementById('total-categories-count');
            if (totalInput) countText = totalInput.value;
        }

        if (countText > 0) {
            if(selectedCountSpan) selectedCountSpan.textContent = countText;
            if(bulkDeleteContainer) {
                bulkDeleteContainer.style.display = 'block';
            }
        } else {
            if(bulkDeleteContainer) {
                bulkDeleteContainer.style.display = 'none';
            }
        }
    }

    if(tableContainer) {
        tableContainer.addEventListener('change', function(e) {
            if (e.target.id === 'selectAll') {
                const isChecked = e.target.checked;
                window.isGlobalSelectAll = isChecked;
                if (!isChecked) {
                    window.selectedCategoryIds.clear();
                }
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                    if (isChecked) window.selectedCategoryIds.add(cb.value);
                });
                updateBulkDeleteButton();
            } else if (e.target.classList.contains('row-checkbox')) {
                if (e.target.checked) {
                    window.selectedCategoryIds.add(e.target.value);
                } else {
                    window.selectedCategoryIds.delete(e.target.value);
                    window.isGlobalSelectAll = false;
                }
                const allCheckboxes = document.querySelectorAll('.row-checkbox');
                const allChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
                const selectAllEl = document.getElementById('selectAll');
                if(selectAllEl) selectAllEl.checked = allChecked;
                updateBulkDeleteButton();
            }
        });
    }

    // Khôi phục trạng thái checkbox sau khi fetch AJAX
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                if (window.isGlobalSelectAll) {
                    const selectAll = document.getElementById('selectAll');
                    if (selectAll) selectAll.checked = true;
                    
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
                        const selectAll = document.getElementById('selectAll');
                        if (selectAll) selectAll.checked = allChecked;
                    }
                }
                updateBulkDeleteButton();
            }
        });
    });

    if (tableContainer) {
        observer.observe(tableContainer, { childList: true, subtree: true });
    }

    window.swalConfig = {
        icon: 'warning',
        width: '320px',
        padding: '1rem',
        showCancelButton: true,
        confirmButtonText: 'Xóa ngay',
        cancelButtonText: 'Hủy',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-xl shadow-xl border border-gray-100',
            title: 'text-base font-bold text-gray-800',
            htmlContainer: 'text-sm text-gray-500 mt-1',
            confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm border-none outline-none ml-2',
            cancelButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all border-none outline-none mr-2',
            icon: 'transform scale-[0.6] -mt-3 -mb-2',
            actions: 'mt-3 w-full flex justify-center'
        },
        buttonsStyling: false
    };

    window.submitBulkDelete = function() {
        const isSelectAll = document.getElementById('selectAll') ? document.getElementById('selectAll').checked : false;
        
        let titleText = 'Xác nhận xóa nhiều?';
        let messageText = `Bạn chuẩn bị xóa ${window.selectedCategoryIds.size} danh mục đã chọn.`;
        
        if (isSelectAll) {
            titleText = 'Xác nhận xóa tất cả?';
            let countText = window.selectedCategoryIds.size;
            const totalInput = document.getElementById('total-categories-count');
            if (totalInput) {
                countText = totalInput.value;
            }
            messageText = `Bạn chuẩn bị xóa TẤT CẢ ${countText} danh mục trùng khớp với bộ lọc.`;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                ...window.swalConfig,
                title: titleText,
                text: messageText
            }).then((result) => {
                if (result.isConfirmed) {
                    executeBulkDelete(isSelectAll);
                }
            });
        } else {
            if (confirm(messageText)) {
                executeBulkDelete(isSelectAll);
            }
        }
    };

    function executeBulkDelete(isSelectAll) {
        const form = document.getElementById('bulk-delete-form');
        
        form.querySelectorAll('input:not([name="_token"])').forEach(el => el.remove());

        if (isSelectAll) {
            const inputAll = document.createElement('input');
            inputAll.type = 'hidden';
            inputAll.name = 'delete_all_pages';
            inputAll.value = '1';
            form.appendChild(inputAll);
            
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of urlParams.entries()) {
                if (key !== 'page' && value !== '') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }
            }
        } else {
            window.selectedCategoryIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_ids[]';
                input.value = id;
                form.appendChild(input);
            });
        }

        form.submit();
    }
});
