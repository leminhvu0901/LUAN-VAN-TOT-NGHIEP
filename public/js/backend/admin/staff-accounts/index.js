document.addEventListener('DOMContentLoaded', function () {
    // 1. TỰ ĐỘNG LỌC DỮ LIỆU KHI THAY ĐỔI SELECT HOẶC GÕ TÌM KIẾM
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const btnClearFilter = document.getElementById('btn-clear-filter');
    
    function fetchStaff(urlStr = null) {
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
        })
        .catch(error => console.error('Lỗi khi tải dữ liệu nhân viên:', error))
        .finally(() => {
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';
        });
    }

    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => fetchStaff());
        });

        let searchTimeout;
        const searchInput = filterForm.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => fetchStaff(), 400);
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
                fetchStaff();
            });
        }
    }

    // 3. ĐỔI LOẠI NHÂN VIÊN (LỄ TÂN / VẬN CHUYỂN) — XÁC NHẬN TRƯỚC KHI LƯU
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('staff-type-select')) {
            const select = e.target;
            const staffId = select.getAttribute('data-id');
            const newType = select.value;
            const oldValue = select.dataset.current;
            const label = newType === 'receptionist' ? 'Nhân viên pha chế' : 'Nhân viên giao hàng';

            if (newType === oldValue) return;

            // Khôi phục lựa chọn cũ ngay — chỉ đổi thật sự nếu người dùng xác nhận trong modal.
            select.value = oldValue;

            const applyChange = () => {
                select.value = newType;
                select.disabled = true;
                fetch(`/admin/staff-accounts/${staffId}/staff-type`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ staff_type: newType })
                })
                .then(response => response.json())
                .then(data => {
                    select.disabled = false;
                    if (data.success) {
                        select.dataset.current = newType;
                        window.AdminAlert.success(data.message, 'Thành công!');
                    } else {
                        select.value = oldValue;
                        window.AdminAlert.error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'Lỗi!');
                    }
                })
                .catch(() => {
                    select.disabled = false;
                    select.value = oldValue;
                    window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
                });
            };

            if (window.AdminAlert && window.AdminAlert.confirm) {
                window.AdminAlert.confirm(
                    `Đổi loại nhân viên thành "${label}"? Quyền truy cập của tài khoản này sẽ thay đổi ngay lập tức.`,
                    applyChange,
                    'Xác nhận đổi loại nhân viên'
                );
            } else if (confirm(`Đổi loại nhân viên thành "${label}"?`)) {
                applyChange();
            }
        }
    });

    // 4. XÓA TÀI KHOẢN NHÂN VIÊN — chỉ xóa được khi chưa có lịch sử hoạt động (backend tự kiểm tra
    // và trả lỗi rõ ràng nếu không xóa được, hướng dẫn khóa tài khoản thay vì xóa).
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-staff-btn');
        if (!btn) return;

        const staffId = btn.getAttribute('data-id');
        const staffName = btn.getAttribute('data-name');

        const performDelete = () => {
            btn.disabled = true;

            fetch(`/admin/staff-accounts/${staffId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    window.AdminAlert.success(data.message, 'Đã xóa!');
                    fetchStaff();
                } else {
                    window.AdminAlert.error(data.message || 'Không thể xóa tài khoản này.', 'Lỗi!');
                }
            })
            .catch(() => {
                btn.disabled = false;
                window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
            });
        };

        if (window.AdminAlert && window.AdminAlert.confirm) {
            window.AdminAlert.confirm(
                `Xóa vĩnh viễn tài khoản "${staffName}"? Hành động này không thể hoàn tác.`,
                performDelete,
                'Xác nhận xóa nhân viên'
            );
        } else if (confirm(`Xóa vĩnh viễn tài khoản "${staffName}"? Không thể hoàn tác.`)) {
            performDelete();
        }
    });

    // Xử lý phân trang bằng AJAX
    document.addEventListener('click', function (e) {
        const paginationLink = e.target.closest('#table-container nav a');
        if (paginationLink) {
            e.preventDefault();
            fetchStaff(paginationLink.href);
        }
    });

    // 2. BẬT/TẮT TRẠNG THÁI NHÂN VIÊN BẰNG TOGGLE (AJAX)
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status')) {
            const checkbox = e.target;
            const staffId = checkbox.getAttribute('data-id');
            const isActive = checkbox.checked ? 1 : 0;
            const statusTextId = `status-text-${staffId}`;
            const statusTextMobileId = `status-text-mobile-${staffId}`;
            const statusTextEl = document.getElementById(statusTextId);
            const statusTextMobileEl = document.getElementById(statusTextMobileId);

            const performToggle = (lockReason = null) => {
                checkbox.disabled = true;

                fetch(`/admin/staff-accounts/${staffId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ is_active: isActive, lock_reason: lockReason })
                })
                .then(response => response.json())
                .then(data => {
                    checkbox.disabled = false;
                    
                    if (data.success) {
                        window.AdminAlert.success(data.message, 'Thành công!');

                        const updateEl = (el) => {
                            if (el) {
                                if (isActive) {
                                    el.textContent = 'Hoạt động';
                                    el.classList.remove('text-rose-500');
                                    el.classList.add('text-emerald-600');
                                    el.removeAttribute('title');
                                } else {
                                    el.textContent = 'Bị khóa';
                                    el.classList.remove('text-emerald-600');
                                    el.classList.add('text-rose-500');
                                    if (lockReason) {
                                        el.setAttribute('title', `Lý do: ${lockReason}`);
                                    }
                                }
                            }
                        };
                        updateEl(statusTextEl);
                        updateEl(statusTextMobileEl);
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
                if (window.AdminAlert && window.AdminAlert.prompt) {
                    window.AdminAlert.prompt(
                        'Khóa tài khoản?',
                        'Vui lòng nhập lý do khóa tài khoản nhân viên này:',
                        'Nhập lý do (ví dụ: Vi phạm quy định làm việc)...',
                        function(reason, isConfirmed) {
                            if (isConfirmed && reason) {
                                performToggle(reason);
                            } else {
                                checkbox.checked = true; // Hoàn tác
                            }
                        }
                    );
                } else {
                    const reason = prompt('Nhập lý do khóa tài khoản nhân viên này:');
                    if (reason !== null && reason.trim() !== '') {
                        performToggle(reason.trim());
                    } else {
                        checkbox.checked = true;
                    }
                }
            } else {
                performToggle();
            }
        }
    });
});
