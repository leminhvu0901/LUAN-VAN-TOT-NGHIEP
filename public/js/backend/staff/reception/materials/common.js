/**
 * Modun tiện ích dùng chung (Common Utilities) cho quản lý Nguyên liệu / Vật tư.
 * Cung cấp các hàm xử lý Modal, Validate dữ liệu nhập (văn bản, đơn vị tính, định dạng tiền tệ VNĐ),
 * và xác nhận hành động bằng hộp thoại SweetAlert / AdminAlert.
 */
(function () {
    "use strict";

    /**
     * Mở một modal giao diện dựa theo ID phần tử.
     * @param {string} id - ID của phần tử modal HTML.
     */
    function openModal(id) {
        document.getElementById(id)?.classList.remove("hidden");
    }

    /**
     * Đóng một modal giao diện dựa theo ID phần tử.
     * @param {string} id - ID của phần tử modal HTML.
     */
    function closeModal(id) {
        document.getElementById(id)?.classList.add("hidden");
    }

    /**
     * Tìm phần tử hiển thị văn bản lỗi tương ứng với một ô nhập liệu (input).
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @returns {HTMLElement|null}
     */
    function getFieldErrorElement(input) {
        if (!input?.id) return null;

        return document.querySelector(`[data-error-for="${input.id}"]`);
    }

    /**
     * Cập nhật trạng thái hiển thị lỗi trực quan (viền đỏ, thông báo lỗi ARIA) cho ô nhập liệu.
     * @param {HTMLInputElement|HTMLTextAreaElement} input - Ô nhập liệu cần đánh dấu lỗi.
     * @param {string} message - Nội dung thông báo lỗi (truyền chuỗi rỗng để xóa lỗi).
     * @param {boolean} blockSubmission - Có chặn submit form qua HTML5 custom validity hay không.
     */
    function setFieldError(input, message = "", blockSubmission = true) {
        if (!input) return;

        const hasError = message !== "";
        const errorElement = getFieldErrorElement(input);

        input.setCustomValidity(blockSubmission ? message : "");
        input.setAttribute("aria-invalid", hasError ? "true" : "false");
        input.classList.toggle("border-red-500", hasError);
        input.classList.toggle("focus:border-red-500", hasError);
        input.classList.toggle("focus:ring-red-500", hasError);
        input.style.borderColor = hasError ? "#ef4444" : "";

        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.toggle("hidden", !hasError);
        }
    }

    /**
     * Dự đoán giá trị chuỗi sau khi thực hiện thao tác nhập / dán nội dung.
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @param {string} insertedText 
     * @returns {string}
     */
    function getProposedValue(input, insertedText) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;

        return `${input.value.slice(0, start)}${insertedText}${input.value.slice(end)}`;
    }

    /**
     * Ngăn chặn người dùng nhập hoặc dán các ký tự không hợp lệ ngay từ thời điểm gõ (beforeinput / paste).
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @param {Function} getValidationMessage - Hàm trả về thông báo lỗi nếu chuỗi dự kiến không hợp lệ.
     */
    function guardInsertedContent(input, getValidationMessage) {
        // Kiểm tra trước khi ký tự được chèn vào ô nhập
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

            event.preventDefault();
            setFieldError(this, message, false);
        });

        // Kiểm tra khi người dùng dán văn bản từ clipboard
        input.addEventListener("paste", function (event) {
            const pastedText = event.clipboardData?.getData("text");
            if (typeof pastedText !== "string") return;

            const message = getValidationMessage(getProposedValue(this, pastedText));
            if (!message) return;

            event.preventDefault();
            setFieldError(this, message, false);
        });
    }

    /**
     * Kiểm tra tính hợp lệ của trường văn bản (giới hạn độ dài, ràng buộc trường Đơn vị tính nguyên liệu).
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @param {string} value 
     * @returns {string} Chuỗi thông báo lỗi (nếu có), hoặc chuỗi rỗng nếu hợp lệ.
     */
    function getTextValidationMessage(input, value) {
        const valueLength = Array.from(value).length;
        const maxLength = Number(input.dataset.maxLength);
        const fieldLabel = input.dataset.fieldLabel || "Nội dung";

        // Ràng buộc riêng cho trường "Đơn vị tính" (material unit): không được chứa chữ số hoặc ký tự đặc biệt
        if (input.matches("[data-material-unit]") && value !== "") {
            const allowedExistingValue = input.dataset.allowedExistingValue;
            const isUnchangedExistingValue =
                allowedExistingValue !== undefined && value === allowedExistingValue;

            if (!isUnchangedExistingValue && /\p{N}/u.test(value)) {
                return "Đơn vị không được nhập số.";
            }

            if (
                !isUnchangedExistingValue &&
                !/^[\p{L}\p{M}\s.\/-]+$/u.test(value)
            ) {
                return "Đơn vị không được nhập ký tự đặc biệt.";
            }
        }

        // Kiểm tra độ dài tối đa cho phép
        if (Number.isFinite(maxLength) && valueLength > maxLength) {
            return `${fieldLabel} không được nhập quá ${maxLength} ký tự.`;
        }

        return "";
    }

    /**
     * Làm sạch (sanitize) giá trị văn bản, tự động loại bỏ các ký tự vi phạm ràng buộc.
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @param {string} value 
     * @returns {string} Chuỗi sau khi đã làm sạch.
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
     * Xác thực tính hợp lệ của ô nhập văn bản và cập nhật giao diện lỗi.
     * @param {HTMLInputElement|HTMLTextAreaElement} input 
     * @returns {boolean} True nếu hợp lệ, False nếu có lỗi.
     */
    function validateTextInput(input) {
        if (!input) return true;

        const message = getTextValidationMessage(input, input.value);
        setFieldError(input, message);

        if (!message) input.dataset.lastValidValue = input.value;

        return message === "";
    }

    /**
     * Đăng ký tự động trình lắng nghe sự kiện validate văn bản cho tất cả các ô nhập có thuộc tính `data-max-length` hoặc `data-material-unit`.
     * @param {Document|HTMLElement} root 
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
                    input.value = input.dataset.lastValidValue ?? "";
                    setFieldError(input, message, false);
                    return;
                }

                input.dataset.lastValidValue = input.value;
                setFieldError(input);
            }

            input.addEventListener("input", handleTextInput);
            input.addEventListener("compositionend", handleTextInput);

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
     * Đồng bộ hóa số tiền dạng nguyên (rawInput) và số tiền được định dạng tiếng Việt (formattedInput).
     * @param {HTMLInputElement} formattedInput - Ô hiển thị số tiền có dấu phân cách hàng nghìn (vd: 100.000).
     * @param {HTMLInputElement} rawInput - Ô ẩn chứa giá trị số nguyên gửi về server (vd: 100000).
     * @param {number|string} value - Giá trị số tiền.
     */
    function syncCurrencyValue(formattedInput, rawInput, value) {
        const numericValue = Number(value) || 0;
        rawInput.value = numericValue;
        formattedInput.value = new Intl.NumberFormat("vi-VN").format(numericValue);
        formattedInput.dataset.lastValidDigits = String(numericValue);
        setFieldError(formattedInput);
    }

    /**
     * Kiểm tra tính hợp lệ của số tiền nhập vào (chỉ chấp nhận chữ số, không vượt quá giới hạn tối đa).
     * @param {HTMLInputElement} input 
     * @param {string} value 
     * @returns {{digits: string, message: string}}
     */
    function getCurrencyValidation(input, value) {
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
     * Gắn bộ lắng nghe sự kiện định dạng tiền tệ tự động (VNĐ) cho ô nhập giá.
     * Khi người dùng gõ số, tự động thêm dấu phân cách hàng nghìn và đồng bộ sang ô input ẩn (rawInput).
     * @param {HTMLInputElement} formattedInput - Ô nhập hiển thị (vd: 50.000).
     * @param {HTMLInputElement} rawInput - Ô nhập ẩn gửi form (vd: 50000).
     */
    function bindCurrencyInput(formattedInput, rawInput) {
        if (!formattedInput || !rawInput || formattedInput.dataset.currencyBound === "true") return;

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
     * Hiển thị hộp thoại xác nhận hành động (dùng AdminAlert / SweetAlert2).
     * @param {string} title - Tiêu đề hộp thoại.
     * @param {string} text - Nội dung chi tiết.
     * @returns {Promise<boolean>} Trả về Promise giải phóng thành True nếu người dùng bấm Xác nhận.
     */
    function confirmAction(title, text) {
        return new Promise(function (resolve) {
            window.AdminAlert.confirm(text, function () { resolve(true); }, title);
        });
    }

    // Tự động khởi chạy khi DOM sẵn sàng
    document.addEventListener("DOMContentLoaded", function () {
        bindTextValidation();

        const formattedPriceInput = document.getElementById('formatted_total_price');
        const priceHiddenInput = document.getElementById('total_price');
        if (formattedPriceInput && priceHiddenInput) {
            bindCurrencyInput(formattedPriceInput, priceHiddenInput);
        }

        // Ủy quyền sự kiện (Event delegation) để đóng/mở modal qua data attribute `data-open-modal` và `data-close-modal`
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

    // Xuất các hàm tiện ích ra không gian tên toàn cục `window.MaterialsCommon`
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
