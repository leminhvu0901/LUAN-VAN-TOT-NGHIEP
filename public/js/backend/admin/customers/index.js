/**
 * index.js - Quản lý danh sách khách hàng khu vực Admin
 * Lọc/tìm kiếm/phân trang nay là form GET/link thường (tải lại trang), không còn AJAX.
 * Các tính năng còn lại (sẽ chuyển tiếp ở giai đoạn sau):
 * - Chọn nhiều (Checkbox) và Xóa hàng loạt khách hàng bất đồng bộ.
 * - Bật/Tắt trạng thái hoạt động (Khóa/Mở khóa) có prompt ghi nhận lý do khóa tài khoản.
 * - Xóa đơn lẻ tài khoản khách hàng bằng AJAX.
 */
document.addEventListener('DOMContentLoaded', function () {
    // === PHẦN TỬ DOM ===
    const filterForm = document.getElementById('filter-form'); // Form bộ lọc
    const tableContainer = document.getElementById('table-container'); // Khung chứa bảng danh sách
    const btnClearFilter = document.getElementById('btn-clear-filter'); // Nút xóa lọc
    
    /**
     * Tải dữ liệu danh sách khách hàng bằng AJAX
     * @param {string|null} urlStr - URL phân trang cần tải tiếp theo, null để lọc từ đầu
     */
    function fetchCustomers(urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            url.search = new URLSearchParams(formData).toString();
        }

        // Cập nhật URL trên thanh địa chỉ của trình duyệt mà không reload
        window.history.pushState({}, '', url);
        tableContainer.style.opacity = '0.5';
        tableContainer.style.pointerEvents = 'none';

        // Gửi truy vấn nhận phản hồi JSON chứa mã html render sẵn của bảng
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
            // Ẩn/Hiện nút xóa bộ lọc
            if (btnClearFilter) {
                const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                    (key === 'search' && val !== '') ||
                    (key !== 'search' && key !== 'page' && val !== 'all' && val !== 'newest')
                );
                btnClearFilter.style.display = hasFilters ? 'inline-block' : 'none';
            }
            syncCheckboxes(); // Đồng bộ lại checkbox theo dữ liệu đã chọn trước đó
            updateBulkDeleteUI(); // Cập nhật lại trạng thái nút Xóa nhiều
        })
        .catch(error => console.error('Lỗi khi tải dữ liệu khách hàng:', error))
        .finally(() => {
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';
        });
    }

    // Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
    // (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

    // =========================================================
    // XỬ LÝ CHỌN CHECKBOX HÀNG LOẠT (BULK ACTIONS SELECTION)
    // =========================================================
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');
    const deselectBtn = document.getElementById('bulk-deselect-btn');

    window.selectedCustomerIds = new Set(); // Set lưu trữ ID các khách hàng được tích chọn

    /**
     * Cập nhật số đếm và ẩn hiện nút "Xóa nhiều"
     */
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

    /**
     * Đồng bộ hóa lại hiển thị checkbox của các hàng dựa theo danh sách trong Set
     */
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

    // Đăng ký sự kiện thay đổi của các Checkbox
    document.addEventListener('change', function (e) {
        // 1. Tích chọn nút Check tất cả ở tiêu đề bảng
        if (e.target && e.target.classList.contains('js-select-all')) {
            const checked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = checked);
            if (checked) {
                // Đọc toàn bộ danh sách ID khách hàng thỏa mãn bộ lọc hiện tại trên server
                const url = new URL(filterForm.action);
                const formData = new FormData(filterForm);
                url.search = new URLSearchParams(formData).toString();
                url.searchParams.set('fetch_all_ids', '1'); // Tham số đặc biệt chỉ lấy mảng ID

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
                // Bỏ chọn tất cả
                window.selectedCustomerIds.clear();
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                updateBulkDeleteUI();
            }
        }
        // 2. Tích chọn checkbox của một hàng đơn lẻ
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

    // =========================================================
    // XÓA HÀNG LOẠT KHÁCH HÀNG (BULK DELETE AJAX WITH ADMINALERT)
    // =========================================================
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
                        setTimeout(() => window.location.reload(), 1500); // Reload trang để làm mới dữ liệu
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

    // =========================================================
    // BẬT/TẮT TRẠNG THÁI KHÁCH HÀNG (MỞ/KHÓA TÀI KHOẢN QUA AJAX)
    // =========================================================
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status')) {
            const checkbox = e.target;
            const customerId = checkbox.getAttribute('data-id');
            const isActive = checkbox.checked ? 1 : 0;
            const statusTextId = `status-text-${customerId}`;
            const statusTextEl = document.getElementById(statusTextId);

            // Hàm thực thi cập nhật trạng thái hoạt động lên máy chủ
            const performToggle = (lockReason = null) => {
                checkbox.disabled = true; // Disable checkbox tạm thời chống click dồn dập

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

                        // Cập nhật văn bản nhãn màu trạng thái ngoài view
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
                        checkbox.checked = !isActive; // Hoàn tác lựa chọn checkbox nếu lỗi
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
                // Hiển thị Prompt yêu cầu nhập lý do khóa tài khoản khách
                if (window.AdminAlert && window.AdminAlert.prompt) {
                    window.AdminAlert.prompt(
                        'Khóa tài khoản?',
                        'Vui lòng nhập lý do khóa tài khoản này:',
                        'Nhập lý do (ví dụ: Vi phạm chính sách)...',
                        function(reason, isConfirmed) {
                            if (isConfirmed && reason) {
                                performToggle(reason);
                            } else {
                                checkbox.checked = true; // Hoàn tác bật lại checkbox
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
                        checkbox.checked = true;
                    }
                }
            } else {
                // Nếu là kích hoạt tài khoản
                performToggle();
            }
        }
    });

    // Sự kiện hủy bỏ toàn bộ tích chọn checkbox
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            window.selectedCustomerIds.clear();
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteUI();
        });
    }
});

/**
 * Xóa một tài khoản khách hàng đơn lẻ bất đồng bộ (AJAX)
 */
window.deleteCustomer = function(id) {
    window.AdminAlert.confirm('Bạn có chắc chắn muốn xóa tài khoản này không? Hành động này không thể hoàn tác.', function() {
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
                    
                    // Gọi bộ lọc kích hoạt AJAX tải lại bảng mà không cần tải lại toàn trang
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
