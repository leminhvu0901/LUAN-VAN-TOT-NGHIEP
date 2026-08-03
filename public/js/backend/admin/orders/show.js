/**
 * show.js - Xử lý các thao tác tương tác trong trang chi tiết đơn hàng (Order Details Page)
 * Các tính năng bao gồm:
 * - Thay thế ảnh phụ lỗi bằng ảnh mặc định khi tải ảnh thất bại (`applyFallbackImage`).
 * - Nhấn nút "In hóa đơn" kích hoạt hộp thoại in mặc định của trình duyệt (`window.print()`).
 * - Thao tác Hủy đơn thường: Đưa ra hộp thoại nhắc nhở lý do hủy tối thiểu 5 ký tự.
 * - Thao tác Hoàn tiền & Hủy đơn (dành cho thanh toán online ví dụ MoMo): Gọi API tích hợp ví MoMo hoàn tiền tự động trực tiếp cho khách hàng trước khi hủy hóa đơn.
 * - Submit các form chuyển trạng thái đơn hàng thông qua AJAX fetch mà không gây reload tải lại trang liên tục.
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
            const form = document.getElementById('cancel-order-form');
            const reasonInput = document.getElementById('cancel_reason_input');

            if (window.AdminAlert && window.AdminAlert.prompt) {
                window.AdminAlert.prompt(
                    'Hủy đơn hàng?',
                    'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):',
                    'Nhập lý do hủy đơn...',
                    function (reason, isConfirmed) {
                        if (isConfirmed && reason && reason.trim().length >= 5) {
                            reasonInput.value = reason.trim();
                            submitOrderActionForm(form);
                        }
                    },
                    'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự)!',
                    'Xác nhận',
                    5
                );
            } else {
                const reason = prompt('Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):');
                if (reason && reason.trim().length >= 5) {
                    reasonInput.value = reason.trim();
                    submitOrderActionForm(form);
                } else if (reason !== null) {
                    showOrderActionMessage('Lý do hủy đơn phải có ít nhất 5 ký tự.', 'error');
                }
            }
        });
    }

    // 4. Xử lý nút "Hoàn tiền & Hủy đơn" (Dành cho đơn thanh toán Online qua MoMo/VNPay đã thanh toán)
    const refundCancelBtn = document.getElementById('refund-cancel-order-btn');
    if (refundCancelBtn) {
        refundCancelBtn.addEventListener('click', function () {
            const form = document.getElementById('refund-cancel-order-form');
            const reasonInput = document.getElementById('refund_cancel_reason_input');

            if (window.AdminAlert && window.AdminAlert.prompt) {
                window.AdminAlert.prompt(
                    'Hoàn tiền & hủy đơn hàng?',
                    'Hệ thống sẽ gọi hoàn tiền ví MoMo cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):',
                    'Nhập lý do hủy đơn...',
                    function (reason, isConfirmed) {
                        if (isConfirmed && reason && reason.trim().length >= 5) {
                            reasonInput.value = reason.trim();
                            submitOrderActionForm(form);
                        }
                    },
                    'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự)!',
                    'Xác nhận',
                    5
                );
            } else {
                const reason = prompt('Hệ thống sẽ gọi hoàn tiền ví MoMo cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):');
                if (reason && reason.trim().length >= 5) {
                    reasonInput.value = reason.trim();
                    submitOrderActionForm(form);
                } else if (reason !== null) {
                    showOrderActionMessage('Lý do hủy đơn phải có ít nhất 5 ký tự.', 'error');
                }
            }
        });
    }

    // 5. Ngăn chặn submit tải lại trang cho các nút cập nhật trạng thái đơn (Xác nhận đơn, Giao hàng...)
    document.querySelectorAll('form[action*="/status"]').forEach(function (form) {
        if (form.id === 'cancel-order-form' || form.id === 'refund-cancel-order-form') return;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrderActionForm(form);
        });
    });
}

/**
 * Đọc CSRF Token lưu trữ trong thẻ meta đầu trang
 */
function getShowPageCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Hiển thị thông báo Toast thành công hoặc thất bại cho trang chi tiết đơn
 */
function showOrderActionMessage(message, type) {
    if (window.AdminAlert && type === 'error' && window.AdminAlert.error) {
        window.AdminAlert.error(message);
    } else if (window.AdminAlert && type !== 'error' && window.AdminAlert.success) {
        window.AdminAlert.success(message);
    } else {
        alert(message);
    }
}

/**
 * Thực thi gửi submit form thay đổi trạng thái đơn hàng bất đồng bộ (fetch)
 */
function submitOrderActionForm(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true; // Disable nút bấm chống click đúp gửi trùng request

    fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getShowPageCsrfToken() },
        body: new FormData(form),
    })
        .then(function (response) {
            return response.json().then(function (data) { return { status: response.status, data: data }; });
        })
        .then(function (result) {
            if (result.status >= 400) {
                const errors = (result.data && result.data.errors) || {};
                const firstError = Object.values(errors)[0];
                showOrderActionMessage((firstError && firstError[0]) || (result.data && result.data.message) || 'Không thể xử lý, vui lòng thử lại.', 'error');
                if (btn) btn.disabled = false;
                return;
            }
            // Thành công: Reload tải lại trang để hiển thị chính xác trạng thái mới và các nút điều hướng tiếp theo
            window.location.reload();
        })
        .catch(function () {
            showOrderActionMessage('Không thể kết nối máy chủ, vui lòng thử lại.', 'error');
            if (btn) btn.disabled = false;
        });
}

// Khởi tạo kích hoạt trang chi tiết đơn
initOrderShowPage();
