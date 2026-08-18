@extends('backend.layouts.app')

@section('title', 'Quản lý Kho Vật Tư - Nhân viên')

@section('content')

    <div id="materials-index-page" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        <!-- Tiêu đề trang -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Quản lý Kho Vật Tư</h2>
                <p class="text-gray-500 text-sm mt-1">Theo dõi, cập nhật và quản lý tồn kho nguyên liệu chi tiết.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <!-- Khung Thống kê 7 thẻ -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Card 1: Tổng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng mặt hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($totalItems) }}</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-shapes text-xs"></i> đang quản lý
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-box text-base"></i>
                </div>
            </div>

            <!-- Card 2: Sắp hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-orange-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($lowStockItems) }}</p>
                    <p class="text-orange-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-triangle-exclamation text-xs"></i> sắp hết
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                </div>
            </div>

            <!-- Card 2.5: Đã hết hàng -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Hết hàng</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($outOfStockItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i> cần nhập gấp
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-cart-arrow-down text-base"></i>
                </div>
            </div>

            <!-- Card 3: Sắp hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-amber-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Sắp hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiringItems ?? 0) }}</p>
                    <p class="text-amber-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Trong 30 ngày
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-clock-rotate-left text-base"></i>
                </div>
            </div>

            <!-- Card 4: Đã hết hạn -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã hết hạn</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($expiredItems ?? 0) }}</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Cần tiêu huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-calendar-xmark text-base"></i>
                </div>
            </div>

            <!-- Card 5: Đã thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-gray-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Đã thu hồi</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ number_format($disposedBatchesCount ?? 0) }}</p>
                    <p class="text-gray-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        Lô hàng xuất huỷ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-ban text-base"></i>
                </div>
            </div>

            <!-- Card 7: Tổng giá trị kho -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-emerald-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Tổng giá trị</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($totalValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-emerald-600 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-money-bill-wave text-base"></i>
                </div>
            </div>

            <!-- Card 8: Giá trị thu hồi -->
            <div class="bg-white p-3 sm:p-4 rounded-2xl organic-shadow flex items-center justify-between border border-gray-100 hover:border-red-300 transition-all group gap-2">
                <div class="space-y-1 min-w-0">
                    <p class="font-semibold text-xs text-gray-500 truncate">Giá trị đã hủy</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ number_format(($disposedValue ?? 0) / 1000000, 1) }}M</p>
                    <p class="text-red-500 font-medium text-[11px] flex items-center gap-1 truncate">
                        VNĐ
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="fa-solid fa-arrow-rotate-left text-base"></i>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="bg-white p-3 sm:p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-4 relative z-20">
            <div class="flex items-center justify-between lg:hidden">
                <h3 class="font-semibold text-gray-700">Bộ lọc & Tìm kiếm</h3>
                <button type="button"
                    onclick="toggleFilterPanel('filter-form')"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-filter text-xs"></i> <span class="hidden sm:inline">Bộ lọc</span>
                </button>
            </div>

            <form id="filter-form" action="{{ route('staff.reception.materials.index') }}" method="GET" class="hidden lg:flex flex-col w-full transition-all">
                <div class="flex flex-wrap items-center gap-3 w-full">
                    <div class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto lg:flex-1 flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 relative transition-colors hover:border-emerald-300 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm shrink-0"></i>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                            class="bg-transparent border-none focus:ring-0 text-sm font-medium pr-2 w-full outline-none"
                            placeholder="Tên vật tư, mã VT...">
                    </div>

                    <select name="status" id="status-select" class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Sắp hết hàng</option>
                        <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                        <option value="expiring" {{ request('status') === 'expiring' ? 'selected' : '' }}>Sắp hết hạn</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Đã hết hạn</option>
                        <option value="disposed" {{ request('status') === 'disposed' ? 'selected' : '' }}>Đã có xuất hủy</option>
                    </select>

                    <select name="sort" id="sort-select" class="w-full sm:w-[calc(50%-0.375rem)] lg:w-auto shrink-0 custom-select-init px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 text-sm font-medium text-gray-700 outline-none transition-colors hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Tồn kho tăng dần</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Tồn kho giảm dần</option>
                    </select>

                    <div class="w-full lg:w-auto shrink-0 lg:ml-auto flex items-center gap-2">
                        <button type="submit" class="flex-1 lg:flex-none px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl transition-all shadow-sm">
                            Lọc
                        </button>
                        <a href="{{ route('staff.reception.materials.index') }}" id="btn-clear-filter"
                            style="display: {{ (request('search') || (request('status') && request('status') != 'all') || (request('sort') && request('sort') != 'newest')) ? 'flex' : 'none' }};"
                            class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-5 py-2 bg-gray-100 text-gray-600 border border-gray-200 font-semibold text-sm rounded-xl hover:bg-gray-200 transition-all shadow-sm">
                            <i class="fa-solid fa-filter-circle-xmark text-sm shrink-0"></i>
                            <span class="whitespace-nowrap">Xóa lọc</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bảng dữ liệu -->
        <div class="bg-white rounded-2xl organic-shadow border border-gray-100 overflow-hidden flex flex-col h-[calc(100vh-230px)] min-h-[500px] w-full">
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative w-full">
                <div id="table-loader" class="absolute inset-0 bg-white/50 z-20 hidden items-center justify-center transition-all duration-300"></div>
                <div class="flex-1 flex flex-col min-h-0 relative w-full" id="materials-table-wrapper">
                    @include('backend.staff.reception.materials.partials.table')
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            "use strict";

            // Mở hộp thoại theo id truyền vào
            function openModal(id) {
                document.getElementById(id)?.classList.remove("hidden");
            }

            // Đóng hộp thoại và dọn trạng thái đang giữ
            function closeModal(id) {
                document.getElementById(id)?.classList.add("hidden");
            }

            // Tìm thẻ chứa dòng báo lỗi gắn với ô nhập đó
            function getFieldErrorElement(input) {
                if (!input?.id) return null;
                return document.querySelector(`[data-error-for="${input.id}"]`);
            }

            // Hiện/ẩn dòng lỗi đỏ ngay dưới ô nhập, không cần chờ submit
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

            // Dựng trước chuỗi SẼ thành sau khi chèn ký tự tại vị trí con trỏ, để biết có nên chặn hay không
            function getProposedValue(input, insertedText) {
                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? start;
                return `${input.value.slice(0, start)}${insertedText}${input.value.slice(end)}`;
            }

            // Bắt sự kiện beforeinput để chặn ký tự sai ngay TRƯỚC khi nó hiện ra, tránh hiện rồi mới xóa gây nhấp nháy
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

            // Sinh câu thông báo lỗi tiếng Việt tương ứng với loại vi phạm của ô chữ
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

            // Làm sạch chuỗi nhập vào; riêng ô đơn vị tính cho giữ nguyên giá trị cũ đã lưu
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

            // Kiểm tra giá trị hiện tại của ô chữ có hợp lệ không
            function validateTextInput(input) {
                if (!input) return true;

                const message = getTextValidationMessage(input, input.value);
                setFieldError(input, message);

                if (!message) input.dataset.lastValidValue = input.value;

                return message === "";
            }

            // Gắn toàn bộ xử lý kiểm tra ký tự vào một ô nhập chữ
            function bindTextValidation(root = document) {
                root.querySelectorAll("[data-max-length], [data-material-unit]").forEach((input) => {
                    if (input.dataset.textValidationBound === "true") return;

                    input.dataset.textValidationBound = "true";
                    guardInsertedContent(input, (value) => getTextValidationMessage(input, value));

                    // Chạy mỗi lần gõ vào ô chữ: làm sạch giá trị và cập nhật thông báo lỗi
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

            // Nạp giá trị có sẵn vào ô tiền khi mở form sửa
            function syncCurrencyValue(formattedInput, rawInput, value) {
                const numericValue = Number(value) || 0;
                rawInput.value = numericValue;
                formattedInput.value = new Intl.NumberFormat("vi-VN").format(numericValue);
                formattedInput.dataset.lastValidDigits = String(numericValue);
                setFieldError(formattedInput);
            }

            // Trả về thông báo lỗi cho ô tiền
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

            // Gắn xử lý tiền tệ vào ô nhập: dùng 2 input song song, ô hiện số đã format cho người xem và ô ẩn giữ số thô để gửi server
            function bindCurrencyInput(formattedInput, rawInput) {
                if (!formattedInput || !rawInput || formattedInput.dataset.currencyBound === "true") return;

                // Ghi giá trị vào cả 2 ô: ô thô nhận số nguyên, ô hiển thị nhận chuỗi đã chấm phân cách kiểu Việt Nam
                function setCurrencyValue(digits) {
                    rawInput.value = digits;
                    formattedInput.value =
                        digits === "" ? "" : new Intl.NumberFormat("vi-VN").format(digits);
                    formattedInput.dataset.lastValidDigits = digits;
                }

                // Chạy mỗi lần gõ vào ô tiền: lọc bỏ ký tự không phải số rồi format lại
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

            // Hỏi xác nhận trước khi thực hiện thao tác không hoàn tác được
            function confirmAction(title, text) {
                return new Promise(function (resolve) {
                    window.AdminAlert.confirm(text, function () { resolve(true); }, title);
                });
            }

            document.addEventListener("DOMContentLoaded", function () {
                bindTextValidation();

                const formattedPriceInput = document.getElementById('formatted_total_price');
                const priceHiddenInput = document.getElementById('total_price');
                if (formattedPriceInput && priceHiddenInput) {
                    bindCurrencyInput(formattedPriceInput, priceHiddenInput);
                }

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
        </script>
    @endpush
@endsection

