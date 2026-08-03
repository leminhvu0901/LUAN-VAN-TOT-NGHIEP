/**
 * imports.js - Quản lý nhập/xuất kho và thanh lý các lô vật tư (Materials & Lots Management)
 * Các tính năng bao gồm:
 * - Tự động tính toán tổng tiền dựa trên số lượng nhập và đơn giá vốn cố định.
 * - Khởi tạo chọn ngày hết hạn (Flatpickr) kèm khống chế minDate (ngày tối thiểu hợp lệ).
 * - Mở hộp thoại sửa vật tư: Giới hạn không cho sửa đơn vị & giá vốn nếu vật tư đó đã phát sinh lô nhập kho.
 * - Mở hộp thoại sửa lô nhập kho: Khống chế số lượng nhập mới không được bé hơn số lượng đã tiêu hao thực tế.
 * - Mở hộp thoại xuất kho (Consume) & thanh lý (Dispose) lô vật tư: Khống chế lượng xuất tối đa bằng tồn kho thực tế của lô đó.
 * - Hộp thoại xác nhận xóa vật tư vĩnh viễn khỏi hệ thống.
 */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        const page = document.getElementById("materials-imports-page");
        if (!page) return; // Chỉ kích hoạt logic nếu đang đứng tại đúng trang quản lý vật tư

        const createImportQuantity = document.getElementById("create-import-quantity"); // Input số lượng nhập
        const formattedTotalPrice = document.getElementById("formatted_total_price"); // Input tổng tiền (hiện định dạng)
        const rawTotalPrice = document.getElementById("total_price"); // Input tổng tiền ẩn (chứa số thô)
        const createImportForm = document.getElementById("form-create-import"); // Form tạo phiếu nhập
        const editFormattedPrice = document.getElementById("edit-formatted-price"); // Form sửa vật tư - input giá hiển thị
        const editRawPrice = document.getElementById("edit-price"); // Form sửa vật tư - input giá ẩn

        // Liên kết định dạng tiền tệ tự động cho form Thêm mới và Sửa vật tư
        MaterialsCommon.bindCurrencyInput(formattedTotalPrice, rawTotalPrice);
        MaterialsCommon.bindCurrencyInput(editFormattedPrice, editRawPrice);

        // Khởi tạo chọn ngày hết hạn bằng Flatpickr
        if (typeof flatpickr !== 'undefined') {
            const createExpirationInput = document.getElementById("create-expiration-date");
            if (createExpirationInput) {
                flatpickr(createExpirationInput, {
                    dateFormat: "Y-m-d", // Định dạng lưu trữ MySQL
                    altInput: true,
                    altFormat: "d/m/Y", // Định dạng hiển thị thân thiện người Việt
                    locale: "vn",
                    disableMobile: true,
                    monthSelectorType: "static",
                    minDate: createExpirationInput.dataset.minDate || "" // Hạn chế không cho chọn ngày đã qua
                });
            }

            const editExpirationInput = document.getElementById("edit-import-expiration-date");
            if (editExpirationInput) {
                flatpickr(editExpirationInput, {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    locale: "vn",
                    disableMobile: true,
                    monthSelectorType: "static"
                });
            }
        }

        // Tự động tính toán tổng tiền khi nhập số lượng
        if (createImportQuantity && formattedTotalPrice && rawTotalPrice) {
            const unitPrice = Number(createImportQuantity.dataset.unitPrice) || 0;

            createImportQuantity.addEventListener("input", function () {
                const quantity = Number(this.value) || 0;
                
                // Khống chế số lượng nhập dưới 1000 cho mỗi lần nhập tránh sai số lớn
                if (quantity >= 1000) {
                    MaterialsCommon.setFieldError(this, "Số lượng nhập phải bé hơn 1000.", false);
                } else {
                    MaterialsCommon.setFieldError(this, "", false);
                }

                // Tính tổng tiền = Số lượng * Giá vốn vật tư
                const total = Math.round(quantity * unitPrice);

                if (total > 0) {
                    MaterialsCommon.syncCurrencyValue(formattedTotalPrice, rawTotalPrice, total);
                } else {
                    rawTotalPrice.value = "";
                    formattedTotalPrice.value = "";
                    formattedTotalPrice.dataset.lastValidDigits = "";
                    MaterialsCommon.setFieldError(formattedTotalPrice);
                }
            });
        }

        // Ràng buộc số lượng nhập ở form chỉnh sửa phiếu nhập
        const editImportQuantity = document.getElementById("edit-import-quantity");
        if (editImportQuantity) {
            editImportQuantity.addEventListener("input", function () {
                const quantity = Number(this.value) || 0;
                if (quantity >= 1000) {
                    MaterialsCommon.setFieldError(this, "Số lượng nhập phải bé hơn 1000.", false);
                } else {
                    MaterialsCommon.setFieldError(this, "", false);
                }
            });
        }

        // Chặn submit form nhập kho nếu tổng tiền không hợp lệ
        createImportForm?.addEventListener("submit", function (event) {
            const totalPrice = Number(rawTotalPrice?.value);
            if (Number.isFinite(totalPrice) && totalPrice > 0 && totalPrice <= 9999999999.99) return;

            event.preventDefault();
            MaterialsCommon.setFieldError(
                formattedTotalPrice,
                totalPrice > 9999999999.99
                    ? "Tổng tiền thanh toán vượt quá giới hạn cho phép."
                    : "Tổng tiền thanh toán phải lớn hơn 0.",
            );
            formattedTotalPrice?.focus();
        });

        // Lắng nghe sự kiện click trong trang danh sách (sử dụng Event Delegation)
        page.addEventListener("click", function (event) {
            // 1. Nhấn nút mở Modal Chỉnh sửa thông tin vật tư chính
            const editMaterialButton = event.target.closest(".js-edit-material");
            if (editMaterialButton) {
                const hasImports = editMaterialButton.dataset.hasImports === "true"; // Kiểm tra vật tư đã từng nhập kho chưa
                const nameInput = document.getElementById("edit-name");
                const unitInput = document.getElementById("edit-unit");
                const form = document.getElementById("form-edit");
                const formAction = document.getElementById("material-edit-form-action");

                if (nameInput) {
                    nameInput.value = editMaterialButton.dataset.name || "";
                    MaterialsCommon.validateTextInput(nameInput);
                }
                
                if (unitInput) {
                    unitInput.value = editMaterialButton.dataset.unit || "";
                    unitInput.dataset.allowedExistingValue = editMaterialButton.dataset.unit || "";
                    
                    // NẾU VẬT TƯ ĐÃ CÓ PHIẾU NHẬP: Khóa trường đơn vị tính để tránh làm sai lệch báo cáo cũ
                    unitInput.readOnly = hasImports;
                    unitInput.classList.toggle("bg-gray-100", hasImports);
                    unitInput.title = hasImports
                        ? "Không thể đổi đơn vị khi vật tư đã có phiếu nhập."
                        : "";
                    MaterialsCommon.validateTextInput(unitInput);
                }
                
                if (editFormattedPrice && editRawPrice) {
                    MaterialsCommon.syncCurrencyValue(
                        editFormattedPrice,
                        editRawPrice,
                        editMaterialButton.dataset.price,
                    );
                    
                    // NẾU VẬT TƯ ĐÃ CÓ PHIẾU NHẬP: Khóa giá vốn vì hệ thống sẽ tính tự động dựa trên bình quân gia quyền của các lô nhập
                    editFormattedPrice.readOnly = hasImports;
                    editFormattedPrice.classList.toggle("bg-gray-100", hasImports);
                    editFormattedPrice.title = hasImports
                        ? "Giá vốn được tính tự động từ các phiếu nhập."
                        : "";
                }
                
                if (form) form.action = editMaterialButton.dataset.action;
                if (formAction) formAction.value = editMaterialButton.dataset.action;
                MaterialsCommon.openModal("modal-edit");
                return;
            }

            // 2. Nhấn nút mở Modal Chỉnh sửa thông tin lô nhập kho (Lot)
            const editImportButton = event.target.closest(".js-edit-import");
            if (editImportButton) {
                const quantityInput = document.getElementById("edit-import-quantity");
                const priceInput = document.getElementById("edit-import-total-price");
                const expirationInput = document.getElementById("edit-import-expiration-date");
                const noteInput = document.getElementById("edit-import-note");
                const idText = document.getElementById("edit-import-id-text");
                const form = document.getElementById("form-edit-import");
                const formAction = document.getElementById("import-edit-form-action");
                const minQuantity = document.getElementById("import-edit-min-quantity");
                const minExpiration = document.getElementById("import-edit-min-expiration");

                if (idText) idText.textContent = `LOT-${editImportButton.dataset.id}`;
                if (quantityInput) {
                    quantityInput.value = editImportButton.dataset.quantity;
                    // Số lượng nhập tối thiểu phải bằng lượng nguyên liệu đã sử dụng thực tế (consumed) để tránh âm kho
                    quantityInput.min = Math.max(
                        1,
                        Math.ceil(Number(editImportButton.dataset.consumed) || 0),
                    );
                }
                if (priceInput) priceInput.value = editImportButton.dataset.totalPrice;
                if (expirationInput) {
                    if (expirationInput._flatpickr) {
                        expirationInput._flatpickr.setDate(editImportButton.dataset.expirationDate || "");
                        expirationInput._flatpickr.set('minDate', editImportButton.dataset.minExpirationDate || "");
                    } else {
                        expirationInput.value = editImportButton.dataset.expirationDate || "";
                        expirationInput.min = editImportButton.dataset.minExpirationDate || "";
                    }
                }
                if (noteInput) {
                    noteInput.value = editImportButton.dataset.note || "";
                    MaterialsCommon.validateTextInput(noteInput);
                }
                if (form) form.action = editImportButton.dataset.action;
                if (formAction) formAction.value = editImportButton.dataset.action;
                if (minQuantity) minQuantity.value = editImportButton.dataset.consumed || "0";
                if (minExpiration) {
                    minExpiration.value = editImportButton.dataset.minExpirationDate || "";
                }
                MaterialsCommon.openModal("modal-edit-import");
                return;
            }

            // 3. Nhấn nút mở Modal thanh lý (hủy bỏ) nguyên liệu bị hỏng/hết hạn
            const disposeButton = event.target.closest(".js-dispose-batch");
            if (disposeButton) {
                const quantityInput = document.getElementById("dispose-batch-quantity");
                const unitText = document.getElementById("dispose-batch-unit");
                const maxText = document.getElementById("dispose-batch-max");
                const idText = document.getElementById("dispose-batch-id");
                const form = document.getElementById("form-dispose-batch");
                const formAction = document.getElementById("dispose-form-action");
                const maxQuantity = document.getElementById("dispose-max-quantity");

                if (quantityInput) {
                    quantityInput.value = "";
                    quantityInput.max = disposeButton.dataset.max; // Giới hạn lượng hủy tối đa bằng tồn kho thực tế của lô
                }
                if (unitText) unitText.textContent = disposeButton.dataset.unit;
                if (maxText) maxText.textContent = disposeButton.dataset.max;
                if (idText) idText.textContent = `LOT-${String(disposeButton.dataset.id).padStart(4, "0")}`;
                if (form) form.action = disposeButton.dataset.action;
                if (formAction) formAction.value = disposeButton.dataset.action;
                if (maxQuantity) maxQuantity.value = disposeButton.dataset.max;
                MaterialsCommon.openModal("modal-dispose-batch");
            }

            // 4. Nhấn nút mở Modal xuất kho nguyên liệu thủ công để chế biến
            const consumeButton = event.target.closest(".js-consume-batch");
            if (consumeButton) {
                const quantityInput = document.getElementById("consume-batch-quantity");
                const unitText = document.getElementById("consume-batch-unit");
                const maxText = document.getElementById("consume-batch-max");
                const idText = document.getElementById("consume-batch-id");
                const form = document.getElementById("form-consume-batch");
                const formAction = document.getElementById("consume-form-action");
                const maxQuantity = document.getElementById("consume-max-quantity");

                if (quantityInput) {
                    quantityInput.value = "";
                    quantityInput.max = consumeButton.dataset.max; // Giới hạn lượng xuất tối đa bằng tồn kho thực tế của lô
                }
                if (unitText) unitText.textContent = consumeButton.dataset.unit;
                if (maxText) maxText.textContent = consumeButton.dataset.max;
                if (idText) idText.textContent = `LOT-${String(consumeButton.dataset.id).padStart(4, "0")}`;
                if (form) form.action = consumeButton.dataset.action;
                if (formAction) formAction.value = consumeButton.dataset.action;
                if (maxQuantity) maxQuantity.value = consumeButton.dataset.max;
                MaterialsCommon.openModal("modal-consume-batch");
            }
        });

        // 5. Xử lý sự kiện nhấn submit xóa vĩnh viễn vật tư
        page.addEventListener("submit", async function (event) {
            const deleteForm = event.target.closest(".js-material-delete-form");
            if (!deleteForm) return;

            event.preventDefault();
            const confirmed = await MaterialsCommon.confirmAction(
                "Xác nhận xóa vật tư?",
                "Vật tư này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
            );
            if (confirmed) deleteForm.submit();
        });
    });
})();
