/**
 * index.js - Quản lý trang danh sách đơn hàng khu vực Admin
 * Lọc/tìm kiếm/phân trang là form GET/link thường (tải lại trang), xóa/xóa nhiều/đổi trạng thái
 * đều là form POST thường, không còn AJAX.
 * JS ở đây chỉ còn quản lý: tích chọn nhiều dòng TRONG TRANG HIỆN TẠI để xóa hàng loạt, và hộp thoại
 * prompt() xin lý do khi đổi trạng thái sang "Đã hủy". Ô chọn trạng thái dùng thẳng <select> gốc của
 * trình duyệt (không còn Custom Dropdown/portal menu tự vẽ).
 */

let tableContainer;

/**
 * Reset lại toàn bộ bộ nhớ chọn nhiều đơn hàng về trạng thái ban đầu
 */
function resetOrderSelection() {
    if (!window.selectedOrderIds) return;
    window.selectedOrderIds.clear();
    updateBulkDeleteButton();
}

/**
 * Cập nhật số lượng hiển thị và trạng thái ẩn hiện của nút "Xóa nhiều"
 */
function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");

    if (!bulkDeleteBtn || !selectedCountSpan) return;

    const count = window.selectedOrderIds.size;
    selectedCountSpan.textContent = count;

    if (count > 0) {
        bulkDeleteBtn.classList.remove("hidden");
        bulkDeleteBtn.classList.add("flex");
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.remove("hidden");
            bulkDeselectBtn.classList.add("flex");
        }
    } else {
        bulkDeleteBtn.classList.add("hidden");
        bulkDeleteBtn.classList.remove("flex");
        if (bulkDeselectBtn) {
            bulkDeselectBtn.classList.add("hidden");
            bulkDeselectBtn.classList.remove("flex");
        }
    }
}

/**
 * Đóng gói và submit form thực thi xóa hàng loạt các đơn hàng đang tích chọn trong trang hiện tại
 */
function submitBulkDelete() {
    if (window.selectedOrderIds.size === 0) return;
    if (!confirm(`Bạn chuẩn bị xóa ${window.selectedOrderIds.size} đơn hàng đã chọn. Tiếp tục?`)) return;

    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    bulkDeleteForm.querySelectorAll('input[name="order_ids[]"]').forEach((el) => el.remove());

    window.selectedOrderIds.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "order_ids[]";
        input.value = id;
        bulkDeleteForm.appendChild(input);
    });

    bulkDeleteForm.submit();
}

/**
 * Khởi tạo ô tìm kiếm và Flatpickr (lọc/tìm kiếm/phân trang submit form GET thường, bấm nút "Lọc")
 */
function initSearchAndFilters() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".orders-date-picker", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            allowInput: true,
            disableMobile: true,
            locale: "vn",
            monthSelectorType: "static",
            appendTo: document.querySelector('.orders-page') || document.body,
        });
    }
}

/**
 * Khởi tạo bắt các sự kiện thay đổi dữ liệu bảng (Check All, Chọn dòng, Đổi trạng thái)
 */
function initTableEvents() {
    tableContainer = document.getElementById("table-container");
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    window.selectedOrderIds = new Set();
    window.submitBulkDelete = submitBulkDelete;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }

    tableContainer.addEventListener("change", function (e) {
        // 1. Checkbox chọn tất cả (chỉ các dòng trong trang hiện tại)
        if (e.target.classList.contains("js-select-all")) {
            const isChecked = e.target.checked;

            document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = isChecked);

            window.selectedOrderIds.clear();
            document.querySelectorAll(".order-checkbox").forEach((cb) => {
                cb.checked = isChecked;
                if (isChecked) window.selectedOrderIds.add(cb.value);
            });

            updateBulkDeleteButton();
            return;
        }

        // 2. Checkbox chọn từng dòng đơn hàng
        if (e.target.classList.contains("order-checkbox")) {
            if (e.target.checked) {
                window.selectedOrderIds.add(e.target.value);
            } else {
                window.selectedOrderIds.delete(e.target.value);
            }

            const allCheckboxes = document.querySelectorAll(".order-checkbox");
            const allChecked =
                document.querySelectorAll(".order-checkbox:checked").length === allCheckboxes.length;
            document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = allChecked);

            updateBulkDeleteButton();
            return;
        }

        // 3. Xử lý đổi trạng thái trực tiếp trong bảng danh sách
        if (e.target.classList.contains("js-order-status-select")) {
            const select = e.target;

            if (select.value !== "cancelled") {
                select.form.submit();
                return;
            }

            // Nếu đổi trạng thái sang HỦY ĐƠN: xin lý do hủy qua prompt() gốc của trình duyệt
            const reason = prompt("Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):");
            if (reason === null) {
                select.value = select.dataset.currentStatus; // Rollback trạng thái cũ nếu hủy bỏ hộp thoại
                return;
            }
            if (reason.trim().length < 5) {
                alert("Lý do hủy đơn phải có ít nhất 5 ký tự.");
                select.value = select.dataset.currentStatus;
                return;
            }

            const reasonInput = document.createElement("input");
            reasonInput.type = "hidden";
            reasonInput.name = "cancel_reason";
            reasonInput.value = reason.trim();
            select.form.appendChild(reasonInput);
            select.form.submit();
        }
    });

    // Theo dõi thay đổi cấu trúc bảng để tự động cập nhật số lượng nút chọn hàng loạt
    const observer = new MutationObserver(function () {
        updateBulkDeleteButton();
    });
    observer.observe(tableContainer, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", function () {
    initSearchAndFilters();
    initTableEvents();
});
