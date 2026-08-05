// =========================================================================
// QUẢN LÝ SẢN PHẨM TRANG QUẢN TRỊ (ADMIN AREA PRODUCTS CONTROLLER)
// Lọc/tìm kiếm/phân trang/xóa/xóa nhiều đều là form thường (tải lại trang), không còn AJAX.
// JS ở đây chỉ quản lý tích chọn nhiều dòng TRONG TRANG HIỆN TẠI và hỏi xác nhận trước khi xóa.
// =========================================================================

// Layout admin KHÔNG cuộn ở <body>/window (body có class 'overflow-hidden' khóa cứng) — phần tử
// thực sự cuộn là #main-content-area. Mọi chỗ cần đọc/ghi vị trí cuộn trong file này phải dùng
// phần tử này, không phải window.scrollY/scrollTo.
function getScrollContainer() {
    return document.getElementById("main-content-area");
}

/**
 * Cập nhật giao diện nút Xóa nhiều dựa trên số lượng checkbox được chọn
 */
function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");
    const count = document.querySelectorAll(".product-checkbox:checked").length;

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count > 0 ? `(${count})` : "";
    }

    if (!bulkDeleteBtn) return;

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
 * Xử lý khi nhấn nút "Chọn tất cả" ở đầu bảng — chỉ áp dụng cho các dòng đang hiển thị
 */
function handleSelectAll(checked) {
    document.querySelectorAll(".product-checkbox").forEach((checkbox) => {
        checkbox.checked = checked;
    });
    updateBulkDeleteButton();
}

function syncSelectAllCheckboxes() {
    const checkboxes = document.querySelectorAll(".product-checkbox");
    const allChecked = checkboxes.length > 0 && document.querySelectorAll(".product-checkbox:checked").length === checkboxes.length;
    document.querySelectorAll(".js-select-all").forEach((el) => (el.checked = allChecked));
}

/**
 * Xác nhận rồi submit form xóa hàng loạt (đưa các ID đang chọn vào form ẩn #bulk-delete-form)
 */
function submitBulkDelete() {
    const ids = Array.from(document.querySelectorAll(".product-checkbox:checked")).map((cb) => cb.value);
    if (ids.length === 0) return;

    if (!confirm(`Bạn chuẩn bị xóa ${ids.length} sản phẩm đã chọn. Tiếp tục?`)) return;

    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    if (!bulkDeleteForm) return;

    bulkDeleteForm.querySelectorAll('input[name="product_ids[]"]').forEach((el) => el.remove());
    ids.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "product_ids[]";
        input.value = id;
        bulkDeleteForm.appendChild(input);
    });

    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;
    bulkDeleteForm.submit();
}

/**
 * Hỏi xác nhận trước khi submit form xóa một sản phẩm đơn lẻ
 */
function confirmDeleteProduct(event, formElement) {
    if (!confirm("Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống. Tiếp tục?")) {
        event.preventDefault();
        return false;
    }
    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;
    return true;
}

/**
 * Đăng ký tất cả các sự kiện tương tác trên bảng sản phẩm
 */
function initProductTableEvents(tableContainer) {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");

    window.submitBulkDelete = submitBulkDelete;
    window.confirmDeleteProduct = confirmDeleteProduct;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }
    if (bulkDeselectBtn) {
        bulkDeselectBtn.addEventListener("click", function () {
            document.querySelectorAll(".product-checkbox, .js-select-all").forEach((el) => (el.checked = false));
            updateBulkDeleteButton();
        });
    }

    tableContainer.addEventListener("change", function (event) {
        if (event.target.classList.contains("js-select-all")) {
            handleSelectAll(event.target.checked);
            return;
        }
        if (event.target.classList.contains("product-checkbox")) {
            syncSelectAllCheckboxes();
            updateBulkDeleteButton();
        }
    });

    tableContainer.addEventListener("submit", function (event) {
        const deleteForm = event.target.closest(".js-product-delete-form");
        if (deleteForm) {
            confirmDeleteProduct(event, deleteForm);
        }
    });
}

// Khởi chạy khi tài liệu HTML sẵn sàng
document.addEventListener("DOMContentLoaded", function () {
    const tableContainer = document.getElementById("table-container");
    if (!tableContainer) return;

    initProductTableEvents(tableContainer);
    updateBulkDeleteButton();
});

// Giữ lại vị trí cuộn khi rời trang (vd sau khi xóa/lọc) và khôi phục lại khi quay về
const PRODUCTS_SCROLL_STORAGE_KEY = "admin-products-scroll-y";

window.addEventListener("beforeunload", function () {
    const container = getScrollContainer();
    const scrollY = window.pendingScrollY !== undefined ? window.pendingScrollY : container?.scrollTop;
    if (scrollY !== undefined) sessionStorage.setItem(PRODUCTS_SCROLL_STORAGE_KEY, String(scrollY));
});

window.addEventListener("load", function () {
    const savedScrollY = sessionStorage.getItem(PRODUCTS_SCROLL_STORAGE_KEY);
    const container = getScrollContainer();
    if (savedScrollY !== null && container) {
        container.scrollTop = parseInt(savedScrollY, 10);
        sessionStorage.removeItem(PRODUCTS_SCROLL_STORAGE_KEY);
    }
});
