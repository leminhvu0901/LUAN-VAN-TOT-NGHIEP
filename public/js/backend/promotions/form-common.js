/**
 * form.js - Xử lý form Thêm/Sửa Khuyến mãi
 * - Chọn loại (percent/fixed): cập nhật UI + đổi định dạng
 * - Format VND cho các trường tiền
 * - Nút tự sinh mã, đếm ký tự mô tả
 * - Validation phía client
 */
document.addEventListener('DOMContentLoaded', function () {

    // === PHẦN TỬ DOM ===
    // Lấy các node cần dùng trong form thêm/sửa khuyến mãi
    const typePercentLabel  = document.getElementById('type-percent-label');
    const typeFixedLabel    = document.getElementById('type-fixed-label');
    const typeInputs        = document.querySelectorAll('input[name="type"]');
    const maxDiscountWrap   = document.getElementById('max-discount-wrap');
    const valueUnitLabel    = document.getElementById('value-unit');

    // Display inputs (hiển thị cho user)
    const displayValue      = document.getElementById('display-value');
    const displayMaxDiscount= document.getElementById('display-max-discount');
    const displayMinOrder   = document.getElementById('display-min-order');

    // Hidden inputs (gửi lên server - giá trị thuần số)
    const hiddenValue       = document.getElementById('promo-value');
    const hiddenMaxDiscount = document.getElementById('promo-max-discount');
    const hiddenMinOrder    = document.getElementById('promo-min-order');

    const btnGenCode        = document.getElementById('btn-gen-code');
    const promoCodeInput    = document.getElementById('promo-code');
    const descriptionInput  = document.getElementById('promo-description');
    const descCount         = document.getElementById('desc-count');

    // === TIỆN ÍCH ===

    /** Chuyển số sang định dạng VND hiển thị: 50000 → "50.000" */
    // Chuẩn hoá số để hiển thị dễ đọc cho người dùng
    function formatVND(raw) {
        if (!raw && raw !== 0) return '';
        const num = parseInt(String(raw).replace(/\D/g, ''), 10);
        if (isNaN(num)) return '';
        return num.toLocaleString('vi-VN');
    }

    /** Lấy số thuần từ chuỗi VND: "50.000" → 50000 */
    // Bóc ký tự định dạng, chỉ giữ phần số để gửi lên server
    function parseVND(str) {
        const cleaned = String(str).replace(/\./g, '').replace(/,/g, '').trim();
        const num = parseInt(cleaned, 10);
        return isNaN(num) ? '' : num;
    }

    /**
     * Gắn logic VND 2 chiều cho cặp (displayEl, hiddenEl)
     * - Khi gõ: format ngay, cập nhật hidden
     * - Khi focus: bỏ format để dễ gõ
     * - Khi blur: format lại
     */
    // Dùng chung cho các ô tiền để đồng bộ hiển thị và giá trị gửi đi
    function bindVNDInput(displayEl, hiddenEl) {
        if (!displayEl || !hiddenEl) return;

        displayEl.addEventListener('input', function () {
            // Cho phép chỉ nhập số và dấu chấm
            let raw = this.value.replace(/[^\d]/g, '');
            const num = parseInt(raw, 10);
            hiddenEl.value = isNaN(num) ? '' : num;

            // Format ngay khi gõ
            const pos = this.selectionStart;
            const oldLen = this.value.length;
            this.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
            // Giữ cursor
            const newLen = this.value.length;
            this.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));
        });

        displayEl.addEventListener('focus', function () {
            // Khi focus: hiển thị số thuần không có dấu chấm để dễ gõ
            if (hiddenEl.value) {
                this.value = hiddenEl.value;
            }
            this.select();
        });

        displayEl.addEventListener('blur', function () {
            // Khi blur: format lại
            if (hiddenEl.value) {
                this.value = parseInt(hiddenEl.value).toLocaleString('vi-VN');
            } else {
                this.value = '';
            }
        });
    }

    // Gắn VND cho các trường tiền
    bindVNDInput(displayMaxDiscount, hiddenMaxDiscount);
    bindVNDInput(displayMinOrder, hiddenMinOrder);

    // === LOẠI KHUYẾN MÃI ===

    // Đọc loại khuyến mãi đang được chọn trên form
    function getCurrentType() {
        const checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'percent';
    }

    // Cập nhật giao diện theo loại khuyến mãi percent/fixed
    function updateTypeUI(selectedType) {
        const isPercent = (selectedType === 'percent');

        // Cập nhật card style
        if (typePercentLabel) {
            typePercentLabel.classList.toggle('border-emerald-500', isPercent);
            typePercentLabel.classList.toggle('bg-emerald-50', isPercent);
            typePercentLabel.classList.toggle('border-gray-200', !isPercent);
            typePercentLabel.classList.toggle('bg-white', !isPercent);
        }
        if (typeFixedLabel) {
            typeFixedLabel.classList.toggle('border-emerald-500', !isPercent);
            typeFixedLabel.classList.toggle('bg-emerald-50', !isPercent);
            typeFixedLabel.classList.toggle('border-gray-200', isPercent);
            typeFixedLabel.classList.toggle('bg-white', isPercent);
        }

        // Ẩn/hiện max_discount_amount (chỉ cần khi giảm %)
        if (maxDiscountWrap) {
            maxDiscountWrap.style.display = isPercent ? '' : 'none';
        }

        // Cập nhật nhãn đơn vị + định dạng ô giá trị
        if (valueUnitLabel) {
            valueUnitLabel.textContent = isPercent ? '(% tỷ lệ)' : '(VNĐ cố định)';
        }

        // Khi đổi loại: reset display value rồi format lại
        if (displayValue && hiddenValue) {
            const raw = hiddenValue.value;
            if (isPercent) {
                // Giảm %: chỉ hiển thị số, không format dấu chấm
                displayValue.value = raw || '';
                displayValue.placeholder = 'VD: 20';
                // Tháo binding VND nếu có
                displayValue.onblur = null;
                displayValue.onfocus = null;
                displayValue.oninput = function () {
                    let v = this.value.replace(/[^\d.]/g, '');
                    hiddenValue.value = v;
                    this.value = v;
                };
            } else {
                // Giảm cố định: format VND
                displayValue.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
                displayValue.placeholder = 'VD: 50.000';
                bindVNDInput(displayValue, hiddenValue);
            }
        }
    }

    // Khởi tạo: đọc loại hiện tại
    const initialType = getCurrentType();
    updateTypeUI(initialType);

    // Nếu type là percent ban đầu: gắn input handler cho display-value (số thô)
    if (initialType === 'percent' && displayValue && hiddenValue) {
        // Với % thì không cần định dạng VND, chỉ cho nhập số
        displayValue.oninput = function () {
            let v = this.value.replace(/[^\d.]/g, '');
            hiddenValue.value = v;
            this.value = v;
        };
    }

    // Xử lý click chọn loại
    if (typePercentLabel) {
        // Click vào card % sẽ chọn radio tương ứng và cập nhật UI
        typePercentLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('percent');
        });
    }
    if (typeFixedLabel) {
        // Click vào card tiền cố định sẽ chuyển sang kiểu fixed
        typeFixedLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('fixed');
        });
    }

    // === NÚT TỰ SINH MÃ ===
    if (btnGenCode && promoCodeInput) {
        // Tạo code ngẫu nhiên khi bấm nút "Tự sinh"
        btnGenCode.addEventListener('click', function () {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            // Sinh mã 6 ký tự (KM- là prefix, tổng 9 ký tự ≤ 10)
            let suffix = '';
            for (let i = 0; i < 6; i++) {
                suffix += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            const code = 'KM' + suffix; // 8 ký tự ≤ 10
            promoCodeInput.value = code;

            // Hiệu ứng highlight
            promoCodeInput.style.borderColor = '#10b981';
            promoCodeInput.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.2)';
            setTimeout(() => {
                promoCodeInput.style.borderColor = '';
                promoCodeInput.style.boxShadow = '';
            }, 1500);
        });
    }

    // === ĐẾM KÝ TỰ MÔ TẢ ===
    if (descriptionInput && descCount) {
        // Hiển thị số ký tự đã nhập để tránh vượt quá giới hạn 100 ký tự
        const updateCount = () => {
            const len = descriptionInput.value.length;
            descCount.textContent = len + '/100';
            descCount.style.color = len >= 90 ? '#ef4444' : (len >= 75 ? '#f59e0b' : '');
        };
        descriptionInput.addEventListener('input', updateCount);
        updateCount(); // Khởi tạo ban đầu
    }

    // === VALIDATION CLIENT ===
    const form = document.getElementById('promotion-form');
    if (form) {
        // Chặn submit nếu giá trị giảm không hợp lệ
        form.addEventListener('submit', function (e) {
            const selectedType = getCurrentType();
            const rawValue = hiddenValue ? parseFloat(hiddenValue.value) : NaN;

            if (isNaN(rawValue) || rawValue <= 0) {
                e.preventDefault();
                if (displayValue) {
                    displayValue.focus();
                    displayValue.style.borderColor = '#ef4444';
                    setTimeout(() => displayValue.style.borderColor = '', 2000);
                }
                alert('Giá trị giảm phải lớn hơn 0.');
                return;
            }

            if (selectedType === 'percent' && rawValue > 100) {
                e.preventDefault();
                if (displayValue) {
                    displayValue.focus();
                    displayValue.style.borderColor = '#ef4444';
                    setTimeout(() => displayValue.style.borderColor = '', 2000);
                }
                alert('Tỷ lệ giảm giá không thể vượt quá 100%.');
                return;
            }
        });
    }

    // === RECURRING TOGGLE ===
    // Hiện/ẩn phần lịch cố định hoặc lặp lại theo checkbox recurring
    const isRecurringCb = document.getElementById('is_recurring_cb');
    const fixedTimeWrapper = document.getElementById('fixed_time_wrapper');
    const recurringTimeWrapper = document.getElementById('recurring_time_wrapper');

    if (isRecurringCb && fixedTimeWrapper && recurringTimeWrapper) {
        isRecurringCb.addEventListener('change', function () {
            if (this.checked) {
                fixedTimeWrapper.classList.add('hidden');
                recurringTimeWrapper.classList.remove('hidden');
            } else {
                fixedTimeWrapper.classList.remove('hidden');
                recurringTimeWrapper.classList.add('hidden');
            }
        });
    }
});
