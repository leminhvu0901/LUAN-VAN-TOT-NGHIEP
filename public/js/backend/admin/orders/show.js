/**
 * show.js - Xử lý các thao tác tương tác trong trang chi tiết đơn hàng (Order Details Page)
 * Các form đổi trạng thái/hủy/hoàn tiền đều là form POST thường (tải lại trang sau khi submit), không còn AJAX.
 * JS ở đây chỉ còn: ảnh dự phòng khi lỗi tải ảnh, nút in hóa đơn, và hộp thoại prompt() xin lý do hủy đơn
 * trước khi submit form hủy/hoàn tiền.
 */

/**
 * Tự động gán ảnh dự phòng khi tải ảnh đại diện sản phẩm bị lỗi
 * @param {HTMLImageElement} image - Thẻ img bị tải lỗi
 */
function applyFallbackImage(image) {
    if (image.dataset.fallbackApplied === "true") return;

    image.dataset.fallbackApplied = "true";
    image.src = image.dataset.fallbackSrc;
}

/**
 * Xin lý do hủy đơn qua hộp thoại prompt() của trình duyệt, điền vào ô ẩn rồi submit form thật
 * @param {HTMLButtonElement} btn - Nút bấm kích hoạt (cancel-order-btn hoặc refund-cancel-order-btn)
 * @param {HTMLFormElement} form - Form cần submit sau khi có lý do hợp lệ
 * @param {HTMLInputElement} reasonInput - Ô input ẩn chứa lý do hủy
 * @param {string} message - Nội dung câu hỏi hiển thị trong hộp thoại prompt()
 */
function askCancelReasonAndSubmit(btn, form, reasonInput, message) {
    const reason = prompt(message);
    if (reason === null) return; // Người dùng bấm Hủy hộp thoại

    if (reason.trim().length < 5) {
        alert('Lý do hủy đơn phải có ít nhất 5 ký tự.');
        return;
    }

    reasonInput.value = reason.trim();
    form.submit();
}

/**
 * Khởi tạo toàn bộ sự kiện khi vào trang chi tiết đơn hàng
 */
function initOrderShowPage() {
    const printButton = document.getElementById("order-print-btn");

    // 1. Gắn sự kiện nút In hóa đơn đơn hàng
    if (printButton) {
        printButton.addEventListener("click", function () {
            window.print();
        });
    }

    // 2. Tải ảnh dự phòng nếu lỗi tải ảnh
    document.querySelectorAll("img[data-fallback-src]").forEach((image) => {
        image.addEventListener("error", function () {
            applyFallbackImage(this);
        });

        if (image.complete && image.naturalWidth === 0) {
            applyFallbackImage(image);
        }
    });

    // 3. Xử lý nút hủy đơn hàng thường (Thanh toán khi nhận hàng COD)
    const cancelBtn = document.getElementById('cancel-order-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            askCancelReasonAndSubmit(
                cancelBtn,
                document.getElementById('cancel-order-form'),
                document.getElementById('cancel_reason_input'),
                'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):'
            );
        });
    }

    // 4. Xử lý nút "Hoàn tiền & Hủy đơn" (Dành cho đơn thanh toán Online qua MoMo/VNPay đã thanh toán)
    const refundCancelBtn = document.getElementById('refund-cancel-order-btn');
    if (refundCancelBtn) {
        refundCancelBtn.addEventListener('click', function () {
            askCancelReasonAndSubmit(
                refundCancelBtn,
                document.getElementById('refund-cancel-order-form'),
                document.getElementById('refund_cancel_reason_input'),
                'Hệ thống sẽ gọi hoàn tiền cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):'
            );
        });
    }
}

// Khởi tạo kích hoạt trang chi tiết đơn
initOrderShowPage();
