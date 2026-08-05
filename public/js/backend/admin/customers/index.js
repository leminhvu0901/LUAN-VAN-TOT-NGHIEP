/**
 * index.js - Quản lý danh sách khách hàng khu vực Admin
 * Lọc/tìm kiếm/phân trang và các thao tác dưới đây đều là form thường (tải lại trang), không AJAX.
 * JS ở đây chỉ hỏi xác nhận (confirm/prompt của trình duyệt) trước khi submit form thật, và quản lý
 * việc tích chọn nhiều dòng TRONG TRANG HIỆN TẠI (không còn hỗ trợ "chọn tất cả xuyên trang").
 */
document.addEventListener('DOMContentLoaded', function () {
    // =========================================================
    // CHỌN NHIỀU (chỉ trong trang hiện tại) & XÓA HÀNG LOẠT
    // =========================================================
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');
    const deselectBtn = document.getElementById('bulk-deselect-btn');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    }

    function updateBulkDeleteUI() {
        const count = getCheckedIds().length;
        if (bulkDeleteContainer) bulkDeleteContainer.classList.toggle('hidden', count === 0);
        if (deselectBtn) deselectBtn.classList.toggle('hidden', count === 0);
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    document.addEventListener('change', function (e) {
        // 1. Tích chọn nút Check tất cả — chỉ áp dụng cho các dòng đang hiển thị ở trang này
        if (e.target && e.target.classList.contains('js-select-all')) {
            const checked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = checked);
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checked);
            updateBulkDeleteUI();
        }
        // 2. Tích chọn checkbox của một hàng đơn lẻ
        if (e.target && e.target.classList.contains('row-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const isAllChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = isAllChecked);
            updateBulkDeleteUI();
        }
    });

    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteUI();
        });
    }

    const bulkDeleteBtn = document.querySelector('.js-bulk-delete');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Xóa ${ids.length} khách hàng đã chọn? Hành động này không thể hoàn tác.`)) return;

            // Xóa các input ids[] cũ (nếu có) rồi thêm lại đúng danh sách đang chọn, sau đó submit form thật
            bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }

    // =========================================================
    // BẬT/TẮT TRẠNG THÁI KHÁCH HÀNG (MỞ/KHÓA TÀI KHOẢN)
    // =========================================================
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status')) {
            const checkbox = e.target;
            const form = checkbox.closest('form');
            const willBeActive = checkbox.checked;

            if (!willBeActive) {
                const reason = prompt('Vui lòng nhập lý do khóa tài khoản này:');
                if (reason === null || reason.trim() === '') {
                    checkbox.checked = true; // Hủy thao tác, hoàn tác lại checkbox
                    return;
                }
                form.querySelector('input[name="lock_reason"]').value = reason.trim();
            }

            form.querySelector('input[name="is_active"]').value = willBeActive ? '1' : '0';
            form.submit();
        }
    });
});

/**
 * Xóa một tài khoản khách hàng đơn lẻ — xác nhận rồi submit form ẩn tương ứng (đã có sẵn trong bảng).
 */
window.deleteCustomer = function (id) {
    if (confirm('Bạn có chắc chắn muốn xóa tài khoản này không? Hành động này không thể hoàn tác.')) {
        const form = document.getElementById('delete-form-' + id);
        if (form) form.submit();
    }
};
