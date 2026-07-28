function applyFallbackImage(image) {
    if (image.dataset.fallbackApplied === "true") return;

    image.dataset.fallbackApplied = "true";
    image.src = image.dataset.fallbackSrc;
}

function printSection(bodyClass) {
    document.body.classList.add("pos-printing-ticket", bodyClass);

    function cleanup() {
        document.body.classList.remove("pos-printing-ticket", bodyClass);
        window.removeEventListener("afterprint", cleanup);
    }
    window.addEventListener("afterprint", cleanup);
    // Dự phòng cho trình duyệt không bắn sự kiện afterprint đáng tin cậy.
    setTimeout(cleanup, 3000);

    window.print();
}

function initOrderShowPage() {
    const prepTicketBtn = document.getElementById("print-prep-ticket-btn");
    if (prepTicketBtn) {
        prepTicketBtn.addEventListener("click", function () {
            printSection("pos-printing-prep");
        });
    }

    const invoiceBtn = document.getElementById("print-invoice-btn");
    if (invoiceBtn) {
        invoiceBtn.addEventListener("click", function () {
            printSection("pos-printing-invoice");
        });
    }

    // Tính trước tiền thừa khi lễ tân nhập số tiền khách đưa (đơn tiền mặt chờ xác nhận).
    const tenderedDisplay = document.getElementById("cash-amount-tendered-display");
    const tenderedInput = document.getElementById("cash-amount-tendered");
    const changePreview = document.getElementById("cash-change-preview");
    const finalAmountInput = document.getElementById("cash-final-amount");
    if (tenderedDisplay && tenderedInput && changePreview && finalAmountInput) {
        const finalAmount = Number(finalAmountInput.value);
        
        const formatValue = function (val) {
            let raw = String(val).replace(/[^0-9]/g, '');
            if (raw.length > 10) raw = raw.slice(0, 10);
            tenderedInput.value = raw;
            tenderedDisplay.value = raw === '' ? '' : new Intl.NumberFormat('vi-VN').format(parseInt(raw));
            
            const tendered = Number(raw) || 0;
            const change = Math.max(0, tendered - finalAmount);
            changePreview.textContent = change.toLocaleString("vi-VN") + "đ";
        };

        // Format giá trị ban đầu
        if (tenderedDisplay.value) {
            formatValue(tenderedDisplay.value);
        }

        tenderedDisplay.addEventListener("input", function () {
            const selectionStart = this.selectionStart;
            const prevLen = this.value.length;

            formatValue(this.value);

            const newLen = this.value.length;
            const diff = newLen - prevLen;
            const newPos = Math.max(0, selectionStart + diff);
            this.setSelectionRange(newPos, newPos);
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

    // "Xác nhận đơn"/"Hoàn thành" (form đơn giản, không cần xử lý gì thêm trước khi submit) và form
    // "Phân công giao hàng" — tất cả submit qua fetch thay vì tải lại cả trang, trang này trước đây
    // hoàn toàn KHÔNG có chỗ hiển thị lỗi (không @error, không $errors->any()) nên mỗi lần thao tác
    // lỗi lễ tân không hề biết vì sao, chỉ thấy trang tải lại mà "không có gì xảy ra".
    document.querySelectorAll('form[action*="/status"], form[action*="/assign-delivery"]').forEach(function (form) {
        if (form.id === 'cancel-order-form') return; // form hủy có luồng riêng qua prompt ở trên
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrderActionForm(form);
        });
    });

    // Xác nhận thu tiền mặt
    const cashForm = document.querySelector('form[action*="/confirm-cash"]');
    if (cashForm) {
        cashForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrderActionForm(cashForm);
        });
    }

    // Thanh toán MoMo — điều hướng thật sang cổng MoMo (redirect_url server trả về) khi thành công,
    // không dùng submitOrderActionForm() chung (form khác vì đích đến là điều hướng, không phải reload).
    const momoForm = document.querySelector('form[action*="/pay-momo"]');
    if (momoForm) {
        momoForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const btn = momoForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(momoForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getShowPageCsrfToken() },
                body: new FormData(momoForm),
            })
                .then(function (response) {
                    return response.json().then(function (data) { return { status: response.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status >= 400) {
                        showOrderActionMessage((result.data && result.data.message) || 'Không thể kết nối cổng thanh toán MoMo.', 'error');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (result.data && result.data.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }
                    if (btn) btn.disabled = false;
                })
                .catch(function () {
                    showOrderActionMessage('Không thể kết nối máy chủ, vui lòng thử lại.', 'error');
                    if (btn) btn.disabled = false;
                });
        });
    }
}

function getShowPageCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Hiện thông báo lỗi/thành công dùng chung cho toàn trang chi tiết đơn — ưu tiên toast AdminAlert
 * (đã nạp sẵn ở layout dùng chung) để nhất quán với phần còn lại của khu vực quản trị/nhân viên,
 * alert() thô chỉ dùng khi AdminAlert vì lý do gì đó chưa nạp được.
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
 * Submit 1 form thao tác đơn hàng (xác nhận/hoàn thành/hủy/phân công/thu tiền mặt) qua fetch. Thành
 * công thì tải lại trang để hiển thị đúng trạng thái/nút bấm mới (trang có nhiều khối điều kiện theo
 * trạng thái đơn — tái tạo lại toàn bộ bằng JS sẽ phức tạp hơn lợi ích mang lại). Lỗi thì hiện thông
 * báo tại chỗ, KHÔNG tải lại trang — khác hẳn trước đây lỗi bị nuốt mất hoàn toàn không có gì hiển thị.
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
