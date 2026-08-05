/**
 * index.js - Quản lý trang danh sách đơn hàng khu vực Lễ tân
 * Lọc/tìm kiếm/phân trang là form GET/link thường (tải lại trang), không còn AJAX.
 * Bảng danh sách của Lễ tân chỉ để xem (đổi trạng thái/xóa đơn phải vào trang chi tiết hoặc do Admin xử lý)
 * nên JS ở đây chỉ còn khởi tạo Flatpickr cho 2 ô chọn ngày lọc.
 */
document.addEventListener("DOMContentLoaded", function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".orders-date-picker", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true,
            disableMobile: true,
            locale: "vn",
            monthSelectorType: "static",
            appendTo: document.querySelector('.orders-page') || document.body,
        });
    }
});
