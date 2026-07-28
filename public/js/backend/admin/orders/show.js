function applyFallbackImage(image) {
    if (image.dataset.fallbackApplied === "true") return;

    image.dataset.fallbackApplied = "true";
    image.src = image.dataset.fallbackSrc;
}

function initOrderShowPage() {
    const printButton = document.getElementById("order-print-btn");

    if (printButton) {
        printButton.addEventListener("click", function () {
            window.print();
        });
    }

    document.querySelectorAll("img[data-fallback-src]").forEach((image) => {
        image.addEventListener("error", function () {
            applyFallbackImage(this);
        });

        if (image.complete && image.naturalWidth === 0) {
            applyFallbackImage(image);
        }
    });

    // Xử lý nút hủy đơn hàng
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

    // Xử lý nút "Hoàn tiền & Hủy đơn" (đơn MoMo đã thanh toán)
    const refundCancelBtn = document.getElementById('refund-cancel-order-btn');
    if (refundCancelBtn) {
        refundCancelBtn.addEventListener('click', function () {
            const form = document.getElementById('refund-cancel-order-form');
            const reasonInput = document.getElementById('refund_cancel_reason_input');

            if (window.AdminAlert && window.AdminAlert.prompt) {
                window.AdminAlert.prompt(
                    'Hoàn tiền & hủy đơn hàng?',
                    'Hệ thống sẽ gọi hoàn tiền MoMo cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):',
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
                const reason = prompt('Hệ thống sẽ gọi hoàn tiền MoMo cho khách rồi hủy đơn — không thể hoàn tác. Vui lòng nhập lý do hủy (tối thiểu 5 ký tự):');
                if (reason && reason.trim().length >= 5) {
                    reasonInput.value = reason.trim();
                    submitOrderActionForm(form);
                } else if (reason !== null) {
                    showOrderActionMessage('Lý do hủy đơn phải có ít nhất 5 ký tự.', 'error');
                }
            }
        });
    }

    // Form đơn giản "Xác nhận đơn"/"Hoàn thành" — submit qua fetch thay vì tải lại cả trang.
    document.querySelectorAll('form[action*="/status"]').forEach(function (form) {
        if (form.id === 'cancel-order-form' || form.id === 'refund-cancel-order-form') return;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrderActionForm(form);
        });
    });
}

function getShowPageCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Hiện thông báo lỗi/thành công dùng chung cho toàn trang chi tiết đơn — ưu tiên toast AdminAlert
 * (đã nạp sẵn ở layout dùng chung), alert() thô chỉ dùng khi AdminAlert vì lý do gì đó chưa nạp được.
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
 * Submit 1 form thao tác đơn hàng (xác nhận/hủy/hoàn tiền) qua fetch. Thành công thì tải lại trang để
 * hiển thị đúng trạng thái/nút bấm mới. Lỗi thì hiện thông báo tại chỗ, KHÔNG tải lại trang.
 */
function submitOrderActionForm(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

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
            window.location.reload();
        })
        .catch(function () {
            showOrderActionMessage('Không thể kết nối máy chủ, vui lòng thử lại.', 'error');
            if (btn) btn.disabled = false;
        });
}

initOrderShowPage();
