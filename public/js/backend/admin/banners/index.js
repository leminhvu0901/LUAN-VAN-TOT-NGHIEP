
/**
 * index.js - Xử lý danh sách banner ở trang quản trị (Admin Banners Control)
 * Lọc/tìm kiếm/phân trang/bật-tắt/xóa/xóa nhiều đều là form thường (tải lại trang), không còn AJAX.
 * JS ở đây chỉ quản lý tích chọn nhiều dòng TRONG TRANG HIỆN TẠI và hỏi xác nhận trước khi xóa nhiều.
 */
document.addEventListener('DOMContentLoaded', function () {
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkDeselectBtn = document.getElementById('bulk-deselect-btn');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    }

    function updateBulkDeleteButton() {
        const count = getCheckedIds().length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (bulkDeleteBtn) {
            bulkDeleteBtn.classList.toggle('hidden', count === 0);
            bulkDeleteBtn.classList.toggle('flex', count > 0);
        }
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.toggle('hidden', count === 0);
            bulkDeselectBtn.classList.toggle('flex', count > 0);
        }
    }

    document.addEventListener('change', function (e) {
        // 1. Tích chọn nút Check tất cả — chỉ áp dụng cho các dòng đang hiển thị ở trang này
        if (e.target.classList.contains('js-select-all')) {
            const isChecked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = isChecked);
            updateBulkDeleteButton();
        }
        // 2. Tích chọn checkbox của một banner đơn lẻ
        else if (e.target.classList.contains('row-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const allChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
            updateBulkDeleteButton();
        }
    });

    if (bulkDeselectBtn) {
        bulkDeselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteButton();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Bạn chuẩn bị xóa ${ids.length} banner đã chọn. Tiếp tục?`)) return;

            bulkDeleteForm.querySelectorAll('input[name="banner_ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'banner_ids[]';
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }
});
