/**
 * File Javascript điều khiển tương tác trên trang quản lý đơn hàng của tôi
 */

document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        const toggle = event.target.closest('[data-toggle-order]');
        if (toggle) window.toggleOrderDetails(toggle.dataset.toggleOrder);
    });
    // 1. Kiểm tra xem Backend có chỉ định tự động mở chi tiết một đơn hàng cụ thể nào không (qua thuộc tính data-open-order-id)
    const mainContainer = document.querySelector('[data-open-order-id]');
    if (mainContainer) {
        const orderId = mainContainer.getAttribute('data-open-order-id');
        if (orderId) {
            // Thực hiện mở chi tiết và cuộn mượt đến đơn hàng đó sau một khoảng trễ ngắn (300ms)
            setTimeout(() => {
                // Gọi hàm hiển thị chi tiết đơn hàng
                if (typeof toggleOrderDetails === 'function') {
                    toggleOrderDetails(orderId);
                }
                // Tìm vùng chứa chi tiết đơn hàng và cuộn đến giữa màn hình
                const el = document.getElementById('order-details-' + orderId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        }
    }
});

/**
 * Hàm ẩn/hiển thị thông tin chi tiết của một đơn hàng (Collapsible Details)
 * @param {number|string} orderId - ID của đơn hàng cần bật/tắt
 */
window.toggleOrderDetails = function(orderId) {
    const detailsEl = document.getElementById('order-details-' + orderId);
    if (detailsEl) {
        // Toggle class 'hidden' của Tailwind/CSS để ẩn hoặc hiện thông tin chi tiết
        detailsEl.classList.toggle('hidden');
    }
};

/**
 * Hiển thị hộp thoại xác nhận và nhập lý do hủy đơn hàng
 * @param {number|string} orderId - ID của đơn hàng
 * @param {string} orderCode - Mã hiển thị của đơn hàng
 */
window.confirmCancelOrder = function(orderId, orderCode) {
    const reason = prompt(
        'Bạn có chắc chắn muốn hủy đơn hàng ' + orderCode + '?\n\n' +
        'Vui lòng nhập lý do hủy đơn hàng (tối thiểu 5 ký tự):',
        'Khách hàng tự hủy đơn hàng'
    );

    // Người dùng bấm Hủy (Cancel) trên prompt
    if (reason === null) {
        return;
    }

    const cleanReason = reason.trim();
    if (cleanReason.length < 5) {
        alert('Lý do hủy đơn hàng phải có ít nhất 5 ký tự!');
        return;
    }

    const form = document.getElementById('cancel-order-form');
    const input = document.getElementById('cancel-reason-input');

    if (form && input) {
        form.action = '/orders/' + orderId + '/cancel';
        input.value = cleanReason;
        form.submit();
    } else {
        alert('Không tìm thấy form hủy đơn hàng. Vui lòng tải lại trang.');
    }
};
