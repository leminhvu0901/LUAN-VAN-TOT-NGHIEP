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
    // Đơn đã thanh toán online: nói rõ tiền sẽ được hoàn tự động về phương thức đã thanh toán, để
    // khách không lo bấm hủy là mất tiền (xem CustomerOrderController::refundAndCancelForCustomer()).
    const btn = document.getElementById('cancel-btn-' + orderId);
    const paidOnline = btn && btn.dataset.paidOnline === '1';
    const gatewayLabel = (btn && btn.dataset.gatewayLabel) || 'MoMo';

    // window.FrontendAlert.prompt() (main.js) thay cho prompt() gốc của trình duyệt — hộp thoại xám
    // xịt "trang web cho biết" trước đây không đồng bộ với giao diện thương hiệu của trang.
    window.FrontendAlert.prompt({
        title: paidOnline
            ? 'Hủy đơn ' + orderCode + ' và hoàn tiền?'
            : 'Hủy đơn hàng ' + orderCode + '?',
        text: paidOnline
            ? 'Số tiền đã thanh toán sẽ được hoàn tự động qua ' + gatewayLabel + ' (có thể mất vài phút đến vài ngày làm việc tùy ngân hàng). Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):'
            : 'Vui lòng nhập lý do hủy đơn hàng (tối thiểu 5 ký tự):',
        placeholder: 'Lý do hủy đơn hàng',
        defaultValue: 'Khách hàng tự hủy đơn hàng',
        minLength: 5,
        confirmText: paidOnline ? 'Hủy & hoàn tiền' : 'Hủy đơn',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        submitCancelOrder(orderId, result.value.trim());
    });
};

function submitCancelOrder(orderId, cleanReason) {
    const form = document.getElementById('cancel-order-form');
    const input = document.getElementById('cancel-reason-input');

    if (!form || !input) {
        if (window.FrontendAlert) window.FrontendAlert.error('Không tìm thấy form hủy đơn hàng. Vui lòng tải lại trang.'); else alert('Không tìm thấy form hủy đơn hàng. Vui lòng tải lại trang.');
        return;
    }

    // Submit form thật (tải lại trang) — server xử lý xong sẽ redirect back kèm thông báo thành
    // công/lỗi hiển thị qua flash message ở đầu trang.
    form.action = '/orders/' + orderId + '/cancel';
    input.value = cleanReason;
    form.submit();
}
