/**
 * Xử lý Modal nhập Lý do Giao hàng Thất bại dành cho Nhân viên Vận chuyển (Delivery Staff).
 *
 * Tính năng chính:
 * - Hiển thị hộp thoại Modal yêu cầu Shipper nhập lý do chi tiết khi đánh dấu đơn hàng giao không thành công.
 * - Kiểm tra (validate) lý do tối thiểu 5 ký tự để tránh nhập sơ sài.
 * - Đồng bộ lý do vào form ẩn của đơn hàng tương ứng và thực thi Submit gửi về server.
 */
(function () {
    "use strict";

    // Form hiện tại đang được gọi modal xử lý
    let currentForm = null;

    // Lấy các phần tử DOM của modal nhập lý do
    const backdrop = document.getElementById('fail-reason-backdrop');
    const textarea = document.getElementById('fail-reason-textarea');
    const hint = document.getElementById('fail-reason-hint');

    /**
     * Mở modal nhập lý do giao thất bại cho một đơn hàng cụ thể.
     * @param {HTMLFormElement} form - Form HTML ẩn tương ứng với đơn hàng cần báo giao thất bại.
     */
    function openModal(form) {
        currentForm = form;
        textarea.value = '';
        hint.classList.add('hidden'); // Ẩn thông báo lỗi nếu có từ lần mở trước
        // Reset về lựa chọn mặc định "Khách không nhận hàng/không liên lạc được" mỗi lần mở modal.
        const defaultRadio = document.querySelector('input[name="fail-reason-type"][value="customer_unreachable"]');
        if (defaultRadio) defaultRadio.checked = true;
        backdrop.classList.remove('hidden'); // Hiển thị modal
        setTimeout(() => textarea.focus(), 50); // Tự động con trỏ vào ô nhập
    }

    /**
     * Đóng modal và đặt lại trạng thái form.
     */
    function closeModal() {
        backdrop.classList.add('hidden');
        currentForm = null;
    }

    // Đăng ký các bộ lắng nghe sự kiện khi DOM đã sẵn sàng
    document.addEventListener('DOMContentLoaded', function () {
        // Ủy quyền sự kiện click: Bắt nút có thuộc tính `data-open-fail-modal="ORDER_ID"`
        document.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-open-fail-modal]');
            if (!btn) return;

            const orderId = btn.getAttribute('data-open-fail-modal');
            const form = document.getElementById('fail-form-' + orderId);
            if (form) openModal(form);
        });

        // Nút Hủy bỏ & Nút X (đóng modal)
        const cancelBtn = document.getElementById('fail-reason-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        const closeBtn = document.getElementById('fail-reason-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        // Bấm ra ngoài vùng modal (khu vực lớp nền mờ backdrop) để đóng
        if (backdrop) {
            backdrop.addEventListener('click', function (event) {
                if (event.target === backdrop) closeModal();
            });
        }

        // Nút "Xác nhận báo giao thất bại"
        const confirmBtn = document.getElementById('fail-reason-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const reason = textarea.value.trim();

                // Kiểm tra lý do bắt buộc phải từ 5 ký tự trở lên
                if (reason.length < 5) {
                    hint.classList.remove('hidden'); // Hiển thị dòng nhắc nhở lỗi đỏ
                    textarea.focus();
                    return;
                }

                if (!currentForm) return;

                // Gán lý do nhập từ textarea + loại lý do đã chọn vào các ô input ẩn trong form gốc rồi
                // submit thật (tải lại trang, quay về đúng tab tương ứng do server redirect).
                const selectedType = document.querySelector('input[name="fail-reason-type"]:checked');
                currentForm.querySelector('input[name="reason"]').value = reason;
                currentForm.querySelector('input[name="failure_type"]').value = selectedType ? selectedType.value : 'other';
                closeModal();
                currentForm.submit();
            });
        }
    });
})();
