/**
 * index.js - Quản lý danh sách tài khoản nhân viên khu vực Admin
 * Lọc/tìm kiếm/phân trang nay là form GET/link thường (tải lại trang), không còn AJAX.
 * Các tính năng còn lại (sẽ chuyển tiếp ở giai đoạn sau):
 * - Thay đổi trực tiếp Chức vụ nhân viên (Lễ tân/Pha chế hoặc Giao hàng) có hộp thoại xác nhận quyền hạn.
 * - Xóa tài khoản nhân viên bằng AJAX (chỉ cho phép xóa khi tài khoản chưa phát sinh dữ liệu lịch sử hoạt động hệ thống).
 * - Bật/Tắt trạng thái hoạt động (Khóa/Mở khóa tài khoản) kèm bộ nhập lý do khóa.
 */
document.addEventListener('DOMContentLoaded', function () {
    // === PHẦN TỬ DOM ===
    const filterForm = document.getElementById('filter-form'); // Form bộ lọc đầu trang
    const tableContainer = document.getElementById('table-container'); // Khung chứa bảng dữ liệu
    const btnClearFilter = document.getElementById('btn-clear-filter'); // Nút xóa lọc
    
    /**
     * Gửi request AJAX tải danh sách nhân viên
     * @param {string|null} urlStr - URL phân trang cần tải tiếp theo, null để lọc từ đầu
     */
    function fetchStaff(urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            url.search = new URLSearchParams(formData).toString();
        }

        // Cập nhật lại thanh địa chỉ trình duyệt
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
                tableContainer.innerHTML = data.html; // Ghi đè bảng mới vào giao diện
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

    // Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
    // (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

    // =========================================================
    // THAY ĐỔI CHỨC VỤ NHÂN VIÊN QUA DROPDOWN (STAFF TYPE PATCH)
    // =========================================================
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('staff-type-select')) {
            const select = e.target;
            const staffId = select.getAttribute('data-id');
            const newType = select.value;
            const oldValue = select.dataset.current;
            const label = newType === 'receptionist' ? 'Nhân viên pha chế' : 'Nhân viên giao hàng';

            if (newType === oldValue) return;

            // Khôi phục giá trị cũ ngay lập tức trước khi hỏi
            select.value = oldValue;

            const applyChange = () => {
                select.value = newType;
                select.disabled = true; // Disable chống bấm liên tục
                
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
                        select.value = oldValue; // Rollback
                        window.AdminAlert.error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'Lỗi!');
                    }
                })
                .catch(() => {
                    select.disabled = false;
                    select.value = oldValue;
                    window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
                });
            };

            // Hiển thị hộp thoại xác nhận phân quyền của tài khoản
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

    // =========================================================
    // XÓA TÀI KHOẢN NHÂN VIÊN BẤT ĐỒNG BỘ (AJAX DELETE)
    // =========================================================
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
                    fetchStaff(); // Tải lại bảng
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

    // =========================================================
    // BẬT/TẮT TRẠNG THÁI (KHÓA/MỞ TÀI KHOẢN QUA AJAX)
    // =========================================================
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

                        // Cập nhật lại văn bản nhãn trạng thái hiển thị ngoài bảng
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
                        checkbox.checked = !isActive; // Rollback
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

            // Nếu người dùng chọn khóa tài khoản (Active từ 1 -> 0)
            if (isActive === 0) {
                // Hiển thị Prompt hỏi lý do khóa tài khoản nhân sự
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
