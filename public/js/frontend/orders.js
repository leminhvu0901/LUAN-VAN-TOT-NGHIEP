/**
 * File Javascript điều khiển tương tác trên trang quản lý đơn hàng của tôi
 */

document.addEventListener('DOMContentLoaded', function() {
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
