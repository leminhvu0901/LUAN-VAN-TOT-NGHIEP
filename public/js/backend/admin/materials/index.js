/**
 * index.js - Quản lý trang danh sách vật tư khu vực Admin
 * Lọc/tìm kiếm/phân trang/xóa/xóa nhiều đều là form thường (tải lại trang), không còn AJAX.
 * JS ở đây chỉ quản lý tích chọn nhiều dòng TRONG TRANG HIỆN TẠI, hỏi xác nhận trước khi xóa,
 * và định dạng tiền tệ cho ô nhập Giá vốn ở form Thêm mới vật tư.
 */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        const page = document.getElementById("materials-index-page");
        const tableContainer = document.getElementById("table-container");
        const bulkDeleteButton = document.getElementById("bulk-delete-btn");
        const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
        const selectedCount = document.getElementById("selected-count");
        const bulkDeleteForm = document.getElementById("bulk-delete-form");

        if (!page || !tableContainer) return;

        // Liên kết định dạng tiền tệ cho trường Giá vốn ở form Thêm mới
        MaterialsCommon.bindCurrencyInput(
            document.getElementById("add-formatted-price"),
            document.getElementById("add-raw-price"),
        );

        function getCheckedIds() {
            return Array.from(tableContainer.querySelectorAll(".material-checkbox:checked")).map((cb) => cb.value);
        }

        function updateBulkDeleteButton() {
            const count = getCheckedIds().length;
            if (selectedCount) selectedCount.textContent = count > 0 ? `(${count})` : "";
            if (!bulkDeleteButton) return;
            bulkDeleteButton.classList.toggle("hidden", count === 0);
            bulkDeleteButton.classList.toggle("flex", count > 0);
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.toggle("hidden", count === 0);
                bulkDeselectBtn.classList.toggle("flex", count > 0);
            }
        }

        function syncSelectAllCheckboxes() {
            const rowCheckboxes = [...tableContainer.querySelectorAll(".material-checkbox:not(:disabled)")];
            const allChecked = rowCheckboxes.length > 0 && rowCheckboxes.every((cb) => cb.checked);
            tableContainer.querySelectorAll(".js-select-all").forEach((el) => (el.checked = allChecked));
        }

        if (bulkDeselectBtn) {
            bulkDeselectBtn.addEventListener("click", function () {
                tableContainer.querySelectorAll(".material-checkbox, .js-select-all").forEach((el) => (el.checked = false));
                updateBulkDeleteButton();
            });
        }

        if (bulkDeleteButton) {
            bulkDeleteButton.addEventListener("click", function () {
                const ids = getCheckedIds();
                if (ids.length === 0) return;
                if (!confirm(`Bạn chuẩn bị xóa ${ids.length} vật tư đã chọn. Các vật tư đang bị ràng buộc sẽ được giữ lại. Tiếp tục?`)) return;

                bulkDeleteForm.querySelectorAll('input[name="material_ids[]"]').forEach((el) => el.remove());
                ids.forEach((id) => {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "material_ids[]";
                    input.value = id;
                    bulkDeleteForm.appendChild(input);
                });
                bulkDeleteForm.submit();
            });
        }

        // Lắng nghe sự kiện check/uncheck các dòng dữ liệu — chỉ áp dụng cho trang hiện tại
        tableContainer.addEventListener("change", function (event) {
            if (event.target.classList.contains("js-select-all")) {
                tableContainer.querySelectorAll(".material-checkbox:not(:disabled)").forEach((cb) => {
                    cb.checked = event.target.checked;
                });
                updateBulkDeleteButton();
                return;
            }
            if (event.target.classList.contains("material-checkbox")) {
                syncSelectAllCheckboxes();
                updateBulkDeleteButton();
            }
        });

        // Xác nhận xóa vật tư đơn lẻ: đã xử lý trực tiếp bằng onsubmit="return confirm(...)" trên
        // mỗi form ở Blade (partials/table.blade.php), không cần thêm listener JS ở đây nữa.
    });
})();
