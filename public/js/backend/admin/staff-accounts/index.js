/**
 * index.js - Quản lý danh sách tài khoản nhân viên khu vực Admin
 * Lọc/tìm kiếm/phân trang và các thao tác dưới đây đều là form thường (tải lại trang), không AJAX.
 * JS ở đây chỉ hỏi xác nhận (confirm/prompt của trình duyệt) trước khi submit form thật.
 */
document.addEventListener('DOMContentLoaded', function () {
    // =========================================================
    // THAY ĐỔI CHỨC VỤ NHÂN VIÊN QUA DROPDOWN
    // =========================================================
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('staff-type-select')) {
            const select = e.target;
            const oldValue = select.dataset.current;
            const newValue = select.value;
            if (newValue === oldValue) return;

            const label = newValue === 'receptionist' ? 'Nhân viên pha chế' : 'Nhân viên giao hàng';
            if (confirm(`Đổi loại nhân viên thành "${label}"? Quyền truy cập của tài khoản này sẽ thay đổi ngay lập tức.`)) {
                select.closest('form').submit();
            } else {
                select.value = oldValue; // Hủy thao tác, giữ nguyên giá trị cũ
            }
        }
    });

    // =========================================================
    // BẬT/TẮT TRẠNG THÁI (KHÓA/MỞ TÀI KHOẢN)
    // =========================================================
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('toggle-status')) {
            const checkbox = e.target;
            const form = checkbox.closest('form');
            const willBeActive = checkbox.checked;

            if (!willBeActive) {
                // Khóa tài khoản: bắt buộc nhập lý do trước khi submit
                const reason = prompt('Vui lòng nhập lý do khóa tài khoản nhân viên này:');
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
