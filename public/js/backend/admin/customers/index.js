document.addEventListener('DOMContentLoaded', function () {
    // ---------------------------------------------------------
    // 1. TỰ ĐỘNG LỌC DỮ LIỆU KHI THAY ĐỔI SELECT HOẶC GÕ TÌM KIẾM
    // ---------------------------------------------------------
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const btnClearFilter = document.getElementById('btn-clear-filter');
    
    function fetchCustomers(urlStr = null) {
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
                btnClearFilter.style.display = hasFilters ? 'inline-block' : 'none';
            }
            syncCheckboxes();
            updateBulkDeleteUI();
        })
        .catch(error => console.error('Lỗi khi tải dữ liệu khách hàng:', error))
        .finally(() => {
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';
        });
    }

    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => fetchCustomers());
        });

        let searchTimeout;
        const searchInput = filterForm.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => fetchCustomers(), 400);
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
                fetchCustomers();
            });
        }
    }

    // Xử lý phân trang bằng AJAX
    document.addEventListener('click', function (e) {
        const paginationLink = e.target.closest('#table-container nav a');
        if (paginationLink) {
            e.preventDefault();
            fetchCustomers(paginationLink.href);
        }
    });

    // =========================================================
    // 2. XỬ LÝ CHECK ALL VÀ CẬP NHẬT GIAO DIỆN NÚT XÓA NHIỀU
    // =========================================================
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');
    const deselectBtn = document.getElementById('bulk-deselect-btn');

    window.selectedCustomerIds = new Set();

    function updateBulkDeleteUI() {
        const count = window.selectedCustomerIds.size;
        
        if (bulkDeleteContainer) {
            if (count > 0) {
                bulkDeleteContainer.classList.remove('hidden');
                bulkDeleteContainer.style.display = '';
            } else {
                bulkDeleteContainer.classList.add('hidden');
                bulkDeleteContainer.style.display = 'none';
            }
        }
        if (deselectBtn) {
            if (count > 0) {
                deselectBtn.classList.remove('hidden');
            } else {
                deselectBtn.classList.add('hidden');
            }
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
    }

    // Đồng bộ lại UI checkbox sau khi fetch AJAX
    function syncCheckboxes() {
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        allCheckboxes.forEach(cb => {
            cb.checked = window.selectedCustomerIds.has(cb.value);
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
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = checked);
            if (checked) {
                // Fetch tất cả ID
                const url = new URL(filterForm.action);
                const formData = new FormData(filterForm);
                url.search = new URLSearchParams(formData).toString();
                url.searchParams.set('fetch_all_ids', '1');

                const selectAllEl = e.target;
                selectAllEl.disabled = true;
                document.querySelectorAll('.row-checkbox').forEach(cb => cb.disabled = true);

                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.ids) {
                        data.ids.forEach(id => window.selectedCustomerIds.add(id.toString()));
                        document.querySelectorAll('.row-checkbox').forEach(cb => {
                            cb.checked = true;
                            cb.disabled = false;
                        });
                        updateBulkDeleteUI();
                    }
                })
                .finally(() => {
                    selectAllEl.disabled = false;
                });
            } else {
                window.selectedCustomerIds.clear();
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                updateBulkDeleteUI();
            }
        }
        if (e.target && e.target.classList.contains('row-checkbox')) {
            if (e.target.checked) window.selectedCustomerIds.add(e.target.value);
            else window.selectedCustomerIds.delete(e.target.value);
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllEls = document.querySelectorAll('.js-select-all');
            if (selectAllEls.length > 0) {
                const isAllChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
                selectAllEls.forEach(el => el.checked = isAllChecked);
            }
            updateBulkDeleteUI();
        }
    });

    // ---------------------------------------------------------
    // 3. XÓA NHIỀU KHÁCH HÀNG (AJAX VỚI SWEETALERT2)
    // 3. XÓA NHIỀU KHÁCH HÀNG (AJAX VỚI ADMINALERT)
    // ---------------------------------------------------------
    document.addEventListener('click', function (e) {
        const bulkDeleteBtn = e.target.closest('.js-bulk-delete');
        if (bulkDeleteBtn) {
            e.preventDefault();

            const count = window.selectedCustomerIds.size;
            if (count === 0) return;

            window.AdminAlert.confirm(`Bạn có chắc chắn muốn xóa ${count} khách hàng đã chọn?`, function() {
                const ids = Array.from(window.selectedCustomerIds);
                const form = document.getElementById('bulk-delete-form');
                
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: ids })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.AdminAlert.success(data.message, 'Thành công!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        window.AdminAlert.error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'Lỗi!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
                });
            }, 'Xác nhận xóa hàng loạt?');
        }
    });

    // ---------------------------------------------------------
    // 4. BẬT/TẮT TRẠNG THÁI KHÁCH HÀNG BẰNG TOGGLE (AJAX)
    // ---------------------------------------------------------
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status')) {
            const checkbox = e.target;
            const customerId = checkbox.getAttribute('data-id');
            const isActive = checkbox.checked ? 1 : 0;
            const statusTextId = `status-text-${customerId}`;
            const statusTextEl = document.getElementById(statusTextId);

            const performToggle = (lockReason = null) => {
                // Tạm thời disable checkbox trong lúc gọi API
                checkbox.disabled = true;

                fetch(`/admin/customers/${customerId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ is_active: isActive, lock_reason: lockReason })
                })
                .then(response => response.json())
                .then(data => {
                    checkbox.disabled = false;
                    
                    if (data.success) {
                        window.AdminAlert.success(data.message, 'Thành công!');

                        // Cập nhật lại chữ "Hoạt động" / "Bị khóa"
                        if (statusTextEl) {
                            if (isActive) {
                                statusTextEl.textContent = 'Hoạt động';
                                statusTextEl.classList.remove('text-rose-500');
                                statusTextEl.classList.add('text-emerald-600');
                                statusTextEl.removeAttribute('title');
                            } else {
                                statusTextEl.textContent = 'Bị khóa';
                                statusTextEl.classList.remove('text-emerald-600');
                                statusTextEl.classList.add('text-rose-500');
                                if (lockReason) {
                                    statusTextEl.setAttribute('title', `Lý do: ${lockReason}`);
                                }
                            }
                        }
                    } else {
                        checkbox.checked = !isActive;
                        window.AdminAlert.error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'Lỗi!');
                    }
                })
                .catch(error => {
                    checkbox.disabled = false;
                    checkbox.checked = !isActive;
                    console.error('Error:', error);
                    window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
                });
            };

            if (isActive === 0) {
                // Prompt cho lý do khóa tài khoản
                if (window.AdminAlert && window.AdminAlert.prompt) {
                    window.AdminAlert.prompt(
                        'Khóa tài khoản?',
                        'Vui lòng nhập lý do khóa tài khoản này:',
                        'Nhập lý do (ví dụ: Vi phạm chính sách)...',
                        function(reason, isConfirmed) {
                            if (isConfirmed && reason) {
                                performToggle(reason);
                            } else {
                                checkbox.checked = true; // Revert
                            }
                        },
                        'Vui lòng nhập lý do khóa tài khoản!',
                        'Khóa tài khoản'
                    );
                } else {
                    const reason = prompt("Vui lòng nhập lý do khóa tài khoản:");
                    if (reason !== null && reason.trim() !== "") {
                        performToggle(reason.trim());
                    } else {
                        checkbox.checked = true; // Revert
                    }
                }
            } else {
                performToggle();
            }
        }
    });

    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            window.selectedCustomerIds.clear();
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteUI();
        });
    }
});

window.deleteCustomer = function(id) {
    window.AdminAlert.confirm('Bạn có chắc chắn muốn xóa tài khoản này không? Hành động này không thể hoàn tác.', function() {
        // Tìm form tương ứng và submit bằng AJAX
        const form = document.getElementById('delete-form-' + id);
        if (form) {
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.AdminAlert.success(data.message, 'Thành công!');
                    if (window.selectedCustomerIds) window.selectedCustomerIds.clear();
                    // Kích hoạt load lại bảng mà không giật trang
                    const select = document.querySelector('#filter-form select');
                    if (select) select.dispatchEvent(new Event('change'));
                } else {
                    window.AdminAlert.error(data.message, 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
            });
        }
    }, 'Xác nhận xóa tài khoản?');
};
