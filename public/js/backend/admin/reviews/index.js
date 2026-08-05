/**
 * index.js - Quản lý danh sách đánh giá khu vực Admin
 * Lọc/tìm kiếm/phân trang/ẩn-hiện/xóa/xóa nhiều đều là form thường (tải lại trang), không còn AJAX.
 * Xóa từng dòng dùng onsubmit="return confirm(...)" ngay trong Blade (partials/table.blade.php).
 * JS ở đây chỉ quản lý tích chọn nhiều dòng TRONG TRANG HIỆN TẠI để xóa hàng loạt.
 */
document.addEventListener('DOMContentLoaded', function () {
    const bulkDeleteContainer = document.getElementById('bulk-delete-container');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    }

    function updateBulkDeleteButton() {
        const count = getCheckedIds().length;
        if (bulkDeleteContainer) bulkDeleteContainer.classList.toggle('hidden', count === 0);
        const deselectBtn = document.getElementById('bulk-deselect-btn');
        if (deselectBtn) deselectBtn.classList.toggle('hidden', count === 0);
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('js-select-all')) {
            const checked = e.target.checked;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = checked);
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checked);
            updateBulkDeleteButton();
        }
        if (e.target && e.target.classList.contains('row-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const isAllChecked = allCheckboxes.length > 0 && document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
            document.querySelectorAll('.js-select-all').forEach(el => el.checked = isAllChecked);
            updateBulkDeleteButton();
        }
    });

    const deselectBtn = document.getElementById('bulk-deselect-btn');
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
            updateBulkDeleteButton();
        });
    }

    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Bạn chuẩn bị xóa ${ids.length} đánh giá đã chọn. Tiếp tục?`)) return;

            bulkDeleteForm.querySelectorAll('input[name="review_ids[]"]').forEach(el => el.remove());
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'review_ids[]';
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }
});
