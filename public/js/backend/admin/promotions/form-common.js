/**
 * form-common.js - Xử lý logic dùng chung cho Form Thêm mới / Chỉnh sửa Chương trình khuyến mãi
 * Các tính năng bao gồm:
 * - Định dạng tiền tệ VND 2 chiều thời gian thực khi gõ (cho display input và hidden input).
 * - Bật/Tắt lựa chọn loại khuyến mãi: Theo tỷ lệ % hoặc Số tiền cố định.
 * - Ẩn/Hiện linh hoạt các trường cấu hình theo phạm vi áp dụng (Toàn đơn hàng, theo Sản phẩm, Danh mục, hoặc Combo mua sắm).
 * - Nút "Tự sinh mã" tự động tạo chuỗi mã code ngẫu nhiên gồm chữ cái và chữ số có hiệu ứng highlight nhấp nháy.
 * - Đếm ngược số lượng ký tự nhập ở ô Mô tả (giới hạn 100 ký tự) hiển thị cảnh báo đỏ khi gần hết.
 * - Cấu hình thêm/xóa động các sản phẩm thành phần trong gói Combo (Combo Items Repeater).
 * - Validate chặt chẽ dữ liệu combo và quà tặng kèm trước khi gửi lên server.
 * - Bật/Tắt chế độ lịch lặp lại (Recurring) hoặc lịch cố định và khởi tạo bộ chọn Flatpickr.
 */
document.addEventListener('DOMContentLoaded', function () {

    // === PHẦN TỬ DOM ===
    const typePercentLabel = document.getElementById('type-percent-label');
    const typeFixedLabel = document.getElementById('type-fixed-label');
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const maxDiscountWrap = document.getElementById('max-discount-wrap');
    const valueUnitLabel = document.getElementById('value-unit');

    // Các trường hiển thị định dạng cho người dùng xem
    const displayValue = document.getElementById('display-value');
    const displayMaxDiscount = document.getElementById('display-max-discount');
    const displayMinOrder = document.getElementById('display-min-order');

    // Các trường input ẩn lưu trữ số nguyên thuần gửi lên máy chủ
    const hiddenValue = document.getElementById('promo-value');
    const hiddenMaxDiscount = document.getElementById('promo-max-discount');
    const hiddenMinOrder = document.getElementById('promo-min-order');

    const btnGenCode = document.getElementById('btn-gen-code');
    const promoCodeInput = document.getElementById('promo-code');
    const descriptionInput = document.getElementById('promo-description');
    const descCount = document.getElementById('desc-count');

    // === TIỆN ÍCH ĐỊNH DẠNG TIỀN TỆ ===

    /**
     * Chuyển đổi số nguyên thuần túy sang định dạng hiển thị VNĐ có dấu chấm
     * Ví dụ: 50000 -> "50.000"
     */
    function formatVND(raw) {
        if (!raw && raw !== 0) return '';
        const num = parseInt(String(raw).replace(/\D/g, ''), 10);
        if (isNaN(num)) return '';
        return num.toLocaleString('vi-VN');
    }

    /**
     * Loại bỏ các ký tự định dạng, bóc lấy số nguyên thuần
     * Ví dụ: "50.000" -> 50000
     */
    function parseVND(str) {
        const cleaned = String(str).replace(/\./g, '').replace(/,/g, '').trim();
        const num = parseInt(cleaned, 10);
        return isNaN(num) ? '' : num;
    }

    /**
     * Đăng ký liên kết định dạng VNĐ 2 chiều cho cặp ô nhập (Hiển thị & Ẩn)
     * - Khi nhập: Tự động định dạng dấu chấm phân cách hàng nghìn.
     * - Khi focus: Tạm thời đưa về số thuần túy để người dùng dễ gõ/sửa.
     * - Khi rời đi (blur): Định dạng lại hoàn chỉnh.
     */
    function bindVNDInput(displayEl, hiddenEl) {
        if (!displayEl || !hiddenEl) return;

        displayEl.addEventListener('input', function () {
            let raw = this.value.replace(/[^\d]/g, '');
            const num = parseInt(raw, 10);
            hiddenEl.value = isNaN(num) ? '' : num;

            // Format trực tiếp khi đang gõ phím và bảo lưu vị trí con trỏ chuột
            const pos = this.selectionStart;
            const oldLen = this.value.length;
            this.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
            const newLen = this.value.length;
            this.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));
        });

        displayEl.addEventListener('focus', function () {
            if (hiddenEl.value) {
                this.value = hiddenEl.value;
            }
            this.select();
        });

        displayEl.addEventListener('blur', function () {
            if (hiddenEl.value) {
                this.value = parseInt(hiddenEl.value).toLocaleString('vi-VN');
            } else {
                this.value = '';
            }
        });
    }

    // Gán định dạng tiền tệ VNĐ cho các trường Giảm giá tối đa và Đơn hàng tối thiểu
    bindVNDInput(displayMaxDiscount, hiddenMaxDiscount);
    bindVNDInput(displayMinOrder, hiddenMinOrder);

    // === PHÂN LOẠI HÌNH THỨC GIẢM GIÁ ===

    /**
     * Đọc kiểu giảm giá hiện tại (Phần trăm hoặc Tiền cố định)
     */
    function getCurrentType() {
        const checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'percent';
    }

    /**
     * Cập nhật giao diện tương ứng khi người dùng đổi loại giảm giá
     */
    function updateTypeUI(selectedType) {
        const isPercent = (selectedType === 'percent');

        // Đổi màu viền xanh lá chủ đạo Material Design khi được chọn
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

        // Chỉ hiển thị trường "Số tiền giảm tối đa" khi chọn hình thức giảm giá theo phần trăm
        if (maxDiscountWrap) {
            maxDiscountWrap.style.display = isPercent ? '' : 'none';
        }

        // Thay đổi nhãn đơn vị đo lường tương ứng
        if (valueUnitLabel) {
            valueUnitLabel.textContent = isPercent ? '(% tỷ lệ)' : '(VNĐ cố định)';
        }

        // Chuyển đổi cơ chế gõ và định dạng số cho ô nhập Giá trị giảm
        if (displayValue && hiddenValue) {
            const raw = hiddenValue.value;
            if (isPercent) {
                // Nhập phần trăm: Chỉ cho nhập số thô, tối đa 100
                displayValue.value = raw || '';
                displayValue.placeholder = 'VD: 20';
                displayValue.onblur = null;
                displayValue.onfocus = null;
                displayValue.oninput = function () {
                    let v = this.value.replace(/[^\d.]/g, '');
                    hiddenValue.value = v;
                    this.value = v;
                };
            } else {
                // Nhập số tiền cố định: Áp dụng định dạng VNĐ có dấu chấm hàng nghìn
                displayValue.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
                displayValue.placeholder = 'VD: 50.000';
                bindVNDInput(displayValue, hiddenValue);
            }
        }
    }

    // Khởi tạo trạng thái giao diện loại giảm giá ban đầu
    const initialType = getCurrentType();
    updateTypeUI(initialType);

    if (initialType === 'percent' && displayValue && hiddenValue) {
        displayValue.oninput = function () {
            let v = this.value.replace(/[^\d.]/g, '');
            hiddenValue.value = v;
            this.value = v;
        };
    }

    // Gắn sự kiện click đổi loại hình thức giảm giá khi nhấp vào thẻ bọc
    if (typePercentLabel) {
        typePercentLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('percent');
        });
    }
    if (typeFixedLabel) {
        typeFixedLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('fixed');
        });
    }

    // === CẤU HÌNH PHẠM VI ÁP DỤNG KHUYẾN MÃI ===
    const scopeOptions = document.querySelectorAll('.scope-option');
    const scopeProductFields = document.getElementById('scope-product-fields');
    const scopeCategoryFields = document.getElementById('scope-category-fields');
    const scopeComboFields = document.getElementById('scope-combo-fields');
    const moneyDiscountFields = document.getElementById('money-discount-fields');

    /**
     * Ẩn hiện các phân vùng nhập liệu cấu hình riêng cho từng loại phạm vi
     */
    function updateScopeUI(selectedScope) {
        scopeOptions.forEach(function (option) {
            const isActive = option.dataset.scope === selectedScope;
            option.classList.toggle('border-emerald-500', isActive);
            option.classList.toggle('bg-emerald-50', isActive);
            option.classList.toggle('border-gray-200', !isActive);
            option.classList.toggle('bg-white', !isActive);
        });

        // Ẩn hiện vùng cấu hình chọn sản phẩm, danh mục, hoặc combo
        if (scopeProductFields) scopeProductFields.classList.toggle('hidden', selectedScope !== 'product');
        if (scopeCategoryFields) scopeCategoryFields.classList.toggle('hidden', selectedScope !== 'category');
        if (scopeComboFields) scopeComboFields.classList.toggle('hidden', selectedScope !== 'combo');
        
        // Với Combo: Giảm giá và quà tặng được quy định riêng nên ẩn cấu hình giảm giá chung của toàn hóa đơn
        if (moneyDiscountFields) moneyDiscountFields.classList.toggle('hidden', selectedScope === 'combo');
    }

    function getCurrentScope() {
        const checked = document.querySelector('input[name="scope"]:checked');
        return checked ? checked.value : 'order';
    }

    if (scopeOptions.length > 0) {
        scopeOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                updateScopeUI(this.dataset.scope);
            });
        });
        updateScopeUI(getCurrentScope());
    }

    // Lọc nhanh danh sách sản phẩm bằng cách gõ từ khóa (phục vụ khi phạm vi là theo Sản phẩm)
    const productSearch = document.getElementById('product-search');
    if (productSearch) {
        productSearch.addEventListener('input', function () {
            const keyword = this.value.trim().toLowerCase();
            document.querySelectorAll('.product-option').forEach(function (row) {
                const matched = !keyword || (row.dataset.name || '').includes(keyword);
                row.classList.toggle('hidden', !matched);
            });
        });
    }

    // === NÚT TỰ SINH MÃ CODE KHUYẾN MÃI ===
    if (btnGenCode && promoCodeInput) {
        btnGenCode.addEventListener('click', function () {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let suffix = '';
            for (let i = 0; i < 6; i++) {
                suffix += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            const code = 'KM' + suffix; // Sinh mã ngẫu nhiên gồm tiền tố KM + 6 ký tự
            promoCodeInput.value = code;

            // Hiệu ứng nhấp nháy viền xanh lá highlight báo hiệu mã mới được sinh ra
            promoCodeInput.style.borderColor = '#10b981';
            promoCodeInput.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.2)';
            setTimeout(() => {
                promoCodeInput.style.borderColor = '';
                promoCodeInput.style.boxShadow = '';
            }, 1500);
        });
    }

    // === ĐẾM NGƯỢC KÝ TỰ MÔ TẢ ===
    if (descriptionInput && descCount) {
        const updateCount = () => {
            const len = descriptionInput.value.length;
            descCount.textContent = len + '/100';
            // Cảnh báo màu cam khi đạt 75 ký tự, màu đỏ khi đạt từ 90 ký tự trở lên
            descCount.style.color = len >= 90 ? '#ef4444' : (len >= 75 ? '#f59e0b' : '');
        };
        descriptionInput.addEventListener('input', updateCount);
        updateCount();
    }

    // === KIỂM TRA TÍNH HỢP LỆ TRƯỚC KHI GỬI (CLIENT-SIDE VALIDATION) ===
    const form = document.getElementById('promotion-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            // Nếu là phạm vi Combo, chuyển sang hàm validate combo riêng
            if (getCurrentScope() === 'combo') {
                if (!validateComboBeforeSubmit()) {
                    e.preventDefault();
                }
                return;
            }

            const selectedType = getCurrentType();
            const rawValue = hiddenValue ? parseFloat(hiddenValue.value) : NaN;

            // Kiểm tra bắt buộc giá trị giảm phải lớn hơn 0
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

            // Nếu giảm theo tỷ lệ %, khống chế không được vượt quá 100%
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

    // =========================================================
    // XỬ LÝ KHỐI COMBO MUA SẮM (COMBO ITEMS REPEATER & REWARDS)
    // =========================================================
    
    /**
     * Khởi tạo tính năng thêm/xóa nhanh các sản phẩm bắt buộc trong gói Combo
     */
    function initComboItems() {
        const container = document.getElementById('combo-items');
        const addButton = document.getElementById('add-combo-item');
        if (!container || !addButton) return;

        // Tạo cấu trúc hàng sản phẩm mới gồm dropdown sản phẩm, ô số lượng và nút xóa
        function createComboItemRow() {
            const row = document.createElement('div');
            row.className = 'combo-item-row grid grid-cols-1 sm:grid-cols-[1fr_120px_40px] gap-2';
            const options = Array.from(document.querySelectorAll('#combo-items select')[0]?.options || [])
                .map((opt) => '<option value="' + opt.value + '">' + opt.textContent + '</option>').join('');
            row.innerHTML =
                '<select name="combo_product_ids[]" class="custom-select-init w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" data-width-class="w-full">' + options + '</select>' +
                '<input name="combo_quantities[]" type="number" min="1" value="1" placeholder="SL" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
                '<button type="button" class="js-remove-combo-item w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa sản phẩm"><span class="material-symbols-outlined">delete</span></button>';
            return row;
        }

        addButton.addEventListener('click', function () {
            // Cho phép thêm tối đa 20 sản phẩm thành phần trong một combo
            if (container.querySelectorAll('.combo-item-row').length < 20) {
                container.appendChild(createComboItemRow());
                if (typeof window.initCustomSelects === 'function') window.initCustomSelects();
            }
        });

        // Xóa dòng sản phẩm (sử dụng Event Delegation để nhận diện nút xóa mới thêm)
        container.addEventListener('click', function (event) {
            const button = event.target.closest('.js-remove-combo-item');
            if (!button) return;
            const rows = container.querySelectorAll('.combo-item-row');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input[type="number"]').forEach((input) => input.value = '1');
            } else {
                button.closest('.combo-item-row').remove();
            }
        });
    }
    initComboItems();

    // === Cấu hình phần thưởng cho gói Combo (Giảm giá hoặc Tặng quà) ===
    const comboHasDiscount = document.getElementById('combo_has_discount');
    const comboHasGift = document.getElementById('combo_has_gift');
    const comboDiscountFields = document.getElementById('combo-discount-fields');
    const comboGiftFields = document.getElementById('combo-gift-fields');
    const comboDiscountTypePercentLabel = document.getElementById('combo-discount-type-percent-label');
    const comboDiscountTypeFixedLabel = document.getElementById('combo-discount-type-fixed-label');
    const comboDiscountValueInput = document.getElementById('combo-discount-value');
    const comboDiscountValueUnit = document.getElementById('combo-discount-value-unit');
    const comboMaxDiscountWrap = document.getElementById('combo-max-discount-wrap');

    /**
     * Ẩn hiện các trường cấu hình thưởng giảm tiền/tặng quà tương ứng với checkbox được chọn
     */
    function updateComboRewardUI() {
        if (comboDiscountFields && comboHasDiscount) comboDiscountFields.classList.toggle('hidden', !comboHasDiscount.checked);
        if (comboGiftFields && comboHasGift) comboGiftFields.classList.toggle('hidden', !comboHasGift.checked);
    }

    function getComboDiscountType() {
        const checked = document.querySelector('input[name="discount_type"]:checked');
        return checked ? checked.value : 'percent';
    }

    /**
     * Đổi giao diện phần hình thức giảm giá của riêng gói Combo (Phần trăm hoặc Tiền mặt)
     */
    function updateComboDiscountTypeUI(selectedType) {
        const isPercent = selectedType === 'percent';
        if (comboDiscountTypePercentLabel) {
            comboDiscountTypePercentLabel.classList.toggle('border-emerald-500', isPercent);
            comboDiscountTypePercentLabel.classList.toggle('bg-emerald-50', isPercent);
            comboDiscountTypePercentLabel.classList.toggle('border-gray-200', !isPercent);
            comboDiscountTypePercentLabel.classList.toggle('bg-white', !isPercent);
        }
        if (comboDiscountTypeFixedLabel) {
            comboDiscountTypeFixedLabel.classList.toggle('border-emerald-500', !isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('bg-emerald-50', !isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('border-gray-200', isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('bg-white', isPercent);
        }
        if (comboMaxDiscountWrap) comboMaxDiscountWrap.classList.toggle('hidden', !isPercent);
        if (comboDiscountValueUnit) comboDiscountValueUnit.textContent = isPercent ? '(% tỷ lệ)' : '(VNĐ cố định)';
        if (comboDiscountValueInput) {
            comboDiscountValueInput.max = isPercent ? '100' : '';
            comboDiscountValueInput.step = isPercent ? '1' : '1000';
            comboDiscountValueInput.placeholder = isPercent ? 'VD: 15' : 'VD: 10000';
        }
    }

    if (comboHasDiscount) comboHasDiscount.addEventListener('change', updateComboRewardUI);
    if (comboHasGift) comboHasGift.addEventListener('change', updateComboRewardUI);
    updateComboRewardUI();

    if (comboDiscountTypePercentLabel) {
        comboDiscountTypePercentLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateComboDiscountTypeUI('percent');
        });
    }
    if (comboDiscountTypeFixedLabel) {
        comboDiscountTypeFixedLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateComboDiscountTypeUI('fixed');
        });
    }
    updateComboDiscountTypeUI(getComboDiscountType());

    /**
     * Ràng buộc dữ liệu chi tiết của Combo trước khi submit form
     */
    function validateComboBeforeSubmit() {
        const productSelects = Array.from(document.querySelectorAll('#combo-items select[name="combo_product_ids[]"]'));
        const hasProduct = productSelects.some((sel) => sel.value);
        
        // 1. Phải có chọn ít nhất một sản phẩm bắt buộc trong combo
        if (!hasProduct) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm cho combo.');
            return false;
        }

        // 2. Phải tích chọn tối thiểu 1 hình thức thưởng: hoặc Giảm giá hoặc Tặng quà kèm theo
        if (!(comboHasDiscount && comboHasDiscount.checked) && !(comboHasGift && comboHasGift.checked)) {
            alert('Combo phải có ít nhất giảm giá hoặc tặng quà.');
            return false;
        }

        // 3. Nếu chọn giảm giá combo, khống chế số tiền giảm phải hợp lệ lớn hơn 0
        if (comboHasDiscount && comboHasDiscount.checked) {
            const rawValue = comboDiscountValueInput ? parseFloat(comboDiscountValueInput.value) : NaN;
            if (isNaN(rawValue) || rawValue <= 0) {
                alert('Giá trị giảm giá combo phải lớn hơn 0.');
                return false;
            }
        }

        // 4. Nếu chọn tặng kèm sản phẩm quà tặng, kiểm tra bắt buộc điền sản phẩm tặng và số lượng tặng hợp lệ
        if (comboHasGift && comboHasGift.checked) {
            const giftProduct = document.querySelector('#combo-gift-fields select[name="gift_product_id"]');
            const giftQty = document.querySelector('#combo-gift-fields input[name="gift_quantity"]');
            if (!giftProduct || !giftProduct.value) {
                alert('Vui lòng chọn sản phẩm tặng cho combo.');
                return false;
            }
            if (!giftQty || !giftQty.value || parseInt(giftQty.value, 10) <= 0) {
                alert('Vui lòng nhập số lượng tặng hợp lệ cho combo.');
                return false;
            }
        }

        return true;
    }

    // === TÍNH NĂNG CHU KỲ LẶP LẠI (RECURRING SCHEDULE) ===
    const isRecurringCb = document.getElementById('is_recurring_cb');
    const fixedTimeWrapper = document.getElementById('fixed_time_wrapper');
    const recurringTimeWrapper = document.getElementById('recurring_time_wrapper');

    if (isRecurringCb && fixedTimeWrapper && recurringTimeWrapper) {
        isRecurringCb.addEventListener('change', function () {
            // Bật/tắt ẩn hiện phân khúc cài đặt lịch cụ thể hoặc lịch lặp lại hàng ngày/tuần
            if (this.checked) {
                fixedTimeWrapper.classList.add('hidden');
                recurringTimeWrapper.classList.remove('hidden');
            } else {
                fixedTimeWrapper.classList.remove('hidden');
                recurringTimeWrapper.classList.add('hidden');
            }
        });
    }

    // === KHỞI TẠO BỘ CHỌN FLAT PICKER CHO THỜI HẠN CHƯƠNG TRÌNH ===
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".promotion-date-picker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S", // Định dạng lưu trữ chuẩn MySQL
            altInput: true,
            altFormat: "d/m/Y H:i", // Hiển thị thân thiện
            locale: "vn",
            disableMobile: true,
            time_24hr: true
        });
    }
});
