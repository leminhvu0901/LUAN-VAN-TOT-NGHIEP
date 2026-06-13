// JS cho trang Orders (hiện tại trống, có thể thêm logic sau)
document.addEventListener('DOMContentLoaded', function() {
    // Logic for orders page
});

window.toggleOrderDetails = function(orderId) {
    const detailsEl = document.getElementById('order-details-' + orderId);
    if (detailsEl) {
        detailsEl.classList.toggle('hidden');
    }
};
