(function () {
    "use strict";

    function openModal(id) {
        document.getElementById(id)?.classList.remove("hidden");
    }

    function closeModal(id) {
        document.getElementById(id)?.classList.add("hidden");
    }

    function getFieldErrorElement(input) {
        if (!input?.id) return null;

        return document.querySelector(`[data-error-for="${input.id}"]`);
    }

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

    function getProposedValue(input, insertedText) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;

        return `${input.value.slice(0, start)}${insertedText}${input.value.slice(end)}`;
    }

    function guardInsertedContent(input, getValidationMessage) {
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

        input.addEventListener("paste", function (event) {
            const pastedText = event.clipboardData?.getData("text");
            if (typeof pastedText !== "string") return;

            const message = getValidationMessage(getProposedValue(this, pastedText));
            if (!message) return;

            event.preventDefault();
            setFieldError(this, message, false);
        });
    }

    function getTextValidationMessage(input, value) {
        const valueLength = Array.from(value).length;
        const maxLength = Number(input.dataset.maxLength);
        const fieldLabel = input.dataset.fieldLabel || "Nội dung";

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

        if (Number.isFinite(maxLength) && valueLength > maxLength) {
            return `${fieldLabel} không được nhập quá ${maxLength} ký tự.`;
        }

        return "";
    }

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

    function validateTextInput(input) {
        if (!input) return true;

        const message = getTextValidationMessage(input, input.value);
        setFieldError(input, message);

        if (!message) input.dataset.lastValidValue = input.value;

        return message === "";
    }

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

    function syncCurrencyValue(formattedInput, rawInput, value) {
        const numericValue = Number(value) || 0;
        rawInput.value = numericValue;
        formattedInput.value = new Intl.NumberFormat("vi-VN").format(numericValue);
        formattedInput.dataset.lastValidDigits = String(numericValue);
        setFieldError(formattedInput);
    }

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

    function confirmAction(title, text) {
        return new Promise(function (resolve) {
            window.AdminAlert.confirm(text, function () { resolve(true); }, title);
        });
    }

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
