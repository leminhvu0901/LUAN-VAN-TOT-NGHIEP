/**
 * common.js - Thư viện xử lý chung cho phần quản lý Vật tư & Lô nhập kho
 * Các tính năng bao gồm:
 * - Ẩn/Hiện Modal giao diện.
 * - Hiển thị thông báo lỗi đầu vào và đánh dấu viền đỏ trường nhập lỗi.
 * - Ràng buộc ngăn chặn người dùng gõ/dán ký tự không hợp lệ thời gian thực (BeforeInput & Paste events).
 * - Validate tên đơn vị vật tư: Không cho phép nhập số hoặc ký tự đặc biệt.
 * - Validate tiền tệ: Tự động định dạng dấu chấm ngăn cách hàng nghìn kiểu vi-VN, kiểm tra giá trị tối đa cho phép.
 * - Hiển thị hộp thoại Swal xác nhận hành động bằng Promise.
 */
(function () {
    "use strict";

    /**
     * Mở một hộp thoại Modal bằng cách gỡ bỏ class ẩn
     * @param {string} id - ID của phần tử Modal cần hiển thị
     */
    function openModal(id) {
        document.getElementById(id)?.classList.remove("hidden");
    }

    /**
     * Đóng hộp thoại Modal bằng cách thêm class ẩn
     * @param {string} id - ID của phần tử Modal cần đóng
     */
    function closeModal(id) {
        document.getElementById(id)?.classList.add("hidden");
    }

    /**
     * Lấy thẻ span hiển thị thông tin lỗi tương ứng với ô input
     * @param {HTMLInputElement} input - Ô nhập cần tìm vùng báo lỗi
     * @returns {HTMLSpanElement|null} - Vùng chứa thông báo lỗi ngoài view
     */
    function getFieldErrorElement(input) {
        if (!input?.id) return null;
        return document.querySelector(`[data-error-for="${input.id}"]`);
    }

    /**
     * Thiết lập hiển thị thông báo lỗi cho ô nhập liệu cụ thể
     * @param {HTMLInputElement} input - Ô nhập liệu cần đánh dấu
     * @param {string} message - Nội dung thông báo lỗi, để trống "" nếu không có lỗi
     * @param {boolean} blockSubmission - true để chặn submit form (setCustomValidity)
     */
    function setFieldError(input, message = "", blockSubmission = true) {
        if (!input) return;

        const hasError = message !== "";
        const errorElement = getFieldErrorElement(input);

        // Thiết lập trạng thái báo lỗi của trình duyệt
        input.setCustomValidity(blockSubmission ? message : "");
        input.setAttribute("aria-invalid", hasError ? "true" : "false");
        
        // Thêm/Gỡ class báo lỗi viền đỏ
        input.classList.toggle("border-red-500", hasError);
        input.classList.toggle("focus:border-red-500", hasError);
        input.classList.toggle("focus:ring-red-500", hasError);
        input.style.borderColor = hasError ? "#ef4444" : "";

        // Hiển thị hoặc ẩn dòng mô tả lỗi đỏ bên dưới
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.toggle("hidden", !hasError);
        }
    }

    /**
     * Dự đoán chuỗi giá trị mới trước khi nó thực sự được điền vào ô nhập
     * @param {HTMLInputElement} input - Ô nhập dữ liệu
     * @param {string} insertedText - Ký tự chuẩn bị chèn vào (từ bàn phím hoặc paste)
     * @returns {string} - Chuỗi dự đoán mới
     */
    function getProposedValue(input, insertedText) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        return `${input.value.slice(0, start)}${insertedText}${input.value.slice(end)}`;
    }

    /**
     * Người gác cổng: Chặn và không cho hiển thị ký tự lạ ngay từ lúc gõ phím hoặc dán
     * @param {HTMLInputElement} input - Ô nhập liệu cần bảo vệ
     * @param {Function} getValidationMessage - Hàm callback kiểm tra tính hợp lệ của chuỗi dự đoán
     */
    function guardInsertedContent(input, getValidationMessage) {
        // Lắng nghe sự kiện trước khi ký tự được ghi vào ô input
        input.addEventListener("beforeinput", function (event) {
            if (
                event.isComposing ||
                typeof event.inputType !== "string" ||
                !event.inputType.startsWith("insert") ||
                typeof event.data !== "string"
            ) {
                return;
            }

            const message = getValidationMessage(getProposedValue(this, event.data));
            if (!message) return;

            event.preventDefault(); // Hủy sự kiện chèn ký tự lạ
            setFieldError(this, message, false); // Hiện cảnh báo lỗi
        });

        // Lắng nghe hành động dán (Paste) dữ liệu từ Clipboard
        input.addEventListener("paste", function (event) {
            const pastedText = event.clipboardData?.getData("text");
            if (typeof pastedText !== "string") return;

            const message = getValidationMessage(getProposedValue(this, pastedText));
            if (!message) return;

            event.preventDefault(); // Chặn dán nội dung chứa ký tự sai quy cách
            setFieldError(this, message, false);
        });
    }

    /**
     * Kiểm tra và trả về thông điệp lỗi cho các ô nhập văn bản (Họ tên, đơn vị...)
     */
    function getTextValidationMessage(input, value) {
        const valueLength = Array.from(value).length;
        const maxLength = Number(input.dataset.maxLength);
        const fieldLabel = input.dataset.fieldLabel || "Nội dung";

        // Kiểm tra riêng cho trường Đơn vị tính của vật tư
        if (input.matches("[data-material-unit]") && value !== "") {
            const allowedExistingValue = input.dataset.allowedExistingValue;
            const isUnchangedExistingValue =
                allowedExistingValue !== undefined && value === allowedExistingValue;

            // Đơn vị tính không được phép chứa số
            if (!isUnchangedExistingValue && /\p{N}/u.test(value)) {
                return "Đơn vị không được nhập số.";
            }

            // Đơn vị tính không được phép chứa ký tự đặc biệt (chỉ cho phép chữ, khoảng cách, gạch chéo, dấu gạch ngang)
            if (
                !isUnchangedExistingValue &&
                !/^[\p{L}\p{M}\s.\/-]+$/u.test(value)
            ) {
                return "Đơn vị không được nhập ký tự đặc biệt.";
            }
        }

        // Ràng buộc giới hạn độ dài ký tự
        if (Number.isFinite(maxLength) && valueLength > maxLength) {
            return `${fieldLabel} không được nhập quá ${maxLength} ký tự.`;
        }

        return "";
    }

    /**
     * Dọn sạch ký tự không hợp lệ khỏi chuỗi đầu vào (Sanitize)
     */
    function getSanitizedTextValue(input, value) {
        let sanitizedValue = value;
        const allowedExistingValue = input.dataset.allowedExistingValue;

        if (
            input.matches("[data-material-unit]") &&
            (allowedExistingValue === undefined || value !== allowedExistingValue)
        ) {
            sanitizedValue = sanitizedValue
                .replace(/\p{N}/gu, "")
                .replace(/[^\p{L}\p{M}\s.\/-]/gu, "");
        }

        const maxLength = Number(input.dataset.maxLength);
        if (Number.isFinite(maxLength)) {
            sanitizedValue = Array.from(sanitizedValue).slice(0, maxLength).join("");
        }

        return sanitizedValue;
    }

    /**
     * Hàm kiểm tra tính hợp lệ của ô nhập chữ chính thức
     */
    function validateTextInput(input) {
        if (!input) return true;

        const message = getTextValidationMessage(input, input.value);
        setFieldError(input, message);

        // Lưu giá trị gần nhất hợp lệ để rollback nếu gõ lỗi
        if (!message) input.dataset.lastValidValue = input.value;

        return message === "";
    }

    /**
     * Đăng ký kiểm tra dữ liệu đầu vào cho toàn bộ thẻ nhập có cấu hình validation
     */
    function bindTextValidation(root = document) {
        root.querySelectorAll("[data-max-length], [data-material-unit]").forEach((input) => {
            if (input.dataset.textValidationBound === "true") return;

            input.dataset.textValidationBound = "true";
            guardInsertedContent(input, (value) => getTextValidationMessage(input, value));

            function handleTextInput(event) {
                if (event?.isComposing) return;

                const message = getTextValidationMessage(input, input.value);
                if (message) {
                    // Nếu lỗi thì khôi phục lại giá trị hợp lệ gần nhất trong bộ nhớ tạm
                    input.value = input.dataset.lastValidValue ?? "";
                    setFieldError(input, message, false);
                    return;
                }

                input.dataset.lastValidValue = input.value;
                setFieldError(input);
            }

            input.addEventListener("input", handleTextInput);
            input.addEventListener("compositionend", handleTextInput);

            // Kiểm tra dọn dẹp giá trị mặc định ban đầu nếu có lỗi
            const initialMessage = getTextValidationMessage(input, input.value);
            if (initialMessage) {
                input.value = getSanitizedTextValue(input, input.value);
                input.dataset.lastValidValue = input.value;
                setFieldError(input, initialMessage, false);
            } else {
                input.dataset.lastValidValue = input.value;
            }
        });
    }

    /**
     * Đồng bộ số tiền giữa ô input hiển thị có dấu chấm và ô input ẩn lưu số thuần túy
     */
    function syncCurrencyValue(formattedInput, rawInput, value) {
        const numericValue = Number(value) || 0;
        rawInput.value = numericValue;
        formattedInput.value = new Intl.NumberFormat("vi-VN").format(numericValue);
        formattedInput.dataset.lastValidDigits = String(numericValue);
        setFieldError(formattedInput);
    }

    /**
     * Kiểm tra tính hợp lệ của số tiền
     */
    function getCurrencyValidation(input, value) {
        // Chỉ cho phép nhập số, không được có chữ cái hoặc ký tự lạ
        if (/[^\d.,\s]/u.test(value)) {
            return {
                digits: value.replace(/\D/g, ""),
                message: input.dataset.numberMessage || "Chỉ được nhập số.",
            };
        }

        const digits = value.replace(/\D/g, "");
        const maxValue = Number(input.dataset.maxValue);
        const exceedsMaximum =
            Number.isFinite(maxValue) && digits !== "" && Number(digits) > maxValue;

        return {
            digits,
            message: exceedsMaximum
                ? input.dataset.maxMessage || "Giá trị vượt quá giới hạn cho phép."
                : "",
        };
    }

    /**
     * Liên kết định dạng tiền tệ 2 chiều cho cặp ô nhập hiển thị và ô gửi dữ liệu ẩn
     */
    function bindCurrencyInput(formattedInput, rawInput) {
        if (!formattedInput || !rawInput || formattedInput.dataset.currencyBound === "true") return;

        // Lưu giá trị số vào input ẩn và định dạng dấu chấm kiểu vi-VN ngoài view
        function setCurrencyValue(digits) {
            rawInput.value = digits;
            formattedInput.value =
                digits === "" ? "" : new Intl.NumberFormat("vi-VN").format(digits);
            formattedInput.dataset.lastValidDigits = digits;
        }

        function handleCurrencyInput(event) {
            if (event?.isComposing) return;

            const validation = getCurrencyValidation(formattedInput, formattedInput.value);
            if (validation.message) {
                setCurrencyValue(formattedInput.dataset.lastValidDigits ?? "");
                setFieldError(formattedInput, validation.message, false);
                return;
            }

            setCurrencyValue(validation.digits);
            setFieldError(formattedInput);
        }

        formattedInput.dataset.currencyBound = "true";
        guardInsertedContent(
            formattedInput,
            (value) => getCurrencyValidation(formattedInput, value).message,
        );
        formattedInput.addEventListener("input", handleCurrencyInput);
        formattedInput.addEventListener("compositionend", handleCurrencyInput);

        const initialValidation = getCurrencyValidation(formattedInput, formattedInput.value);
        if (initialValidation.message) {
            setCurrencyValue("");
            setFieldError(formattedInput, initialValidation.message, false);
        } else {
            setCurrencyValue(initialValidation.digits);
        }
    }

    /**
     * Hiển thị modal xác nhận của AdminAlert bằng cách sử dụng Promise
     */
    function confirmAction(title, text) {
        return new Promise(function (resolve) {
            window.AdminAlert.confirm(text, function () { resolve(true); }, title);
        });
    }

    // Lắng nghe sự kiện click mở/đóng Modal giao diện
    document.addEventListener("DOMContentLoaded", function () {
        bindTextValidation();

        document.addEventListener("click", function (event) {
            const openButton = event.target.closest("[data-open-modal]");
            if (openButton) {
                openModal(openButton.dataset.openModal);
                return;
            }

            const closeButton = event.target.closest("[data-close-modal]");
            if (closeButton) {
                closeModal(closeButton.dataset.closeModal);
            }
        });
    });

    // Đăng xuất đối tượng toàn cục MaterialsCommon
    window.MaterialsCommon = {
        bindCurrencyInput,
        bindTextValidation,
        closeModal,
        confirmAction,
        openModal,
        setFieldError,
        syncCurrencyValue,
        validateTextInput,
    };
})();
