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
                            form.submit();
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
                    form.submit();
                } else if (reason !== null) {
                    alert('Lý do hủy đơn phải có ít nhất 5 ký tự.');
                }
            }
        });
    }
}

initOrderShowPage();
