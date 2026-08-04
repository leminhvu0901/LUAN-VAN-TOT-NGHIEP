// =========================================================================
// QUẢN LÝ SẢN PHẨM TRANG QUẢN TRỊ (ADMIN AREA PRODUCTS CONTROLLER)
// =========================================================================

let filterTimeout = null; // Tránh gọi API liên tục khi gõ tìm kiếm (Debounce)
let filterForm; // Tham chiếu đến biểu mẫu chứa các bộ lọc
let tableContainer; // Container chứa toàn bộ bảng sản phẩm
let tableWrapper; // Vùng bọc riêng của bảng để cập nhật AJAX
let btnClearFilter; // Nút xóa bộ lọc
let loader; // Hiệu ứng tải trang

// Layout admin KHÔNG cuộn ở <body>/window (body có class 'overflow-hidden' khóa cứng) — phần tử
// thực sự cuộn là #main-content-area. Mọi chỗ cần đọc/ghi vị trí cuộn trong file này phải dùng 
// phần tử này, không phải window.scrollY/scrollTo.
function getScrollContainer() {
    return document.getElementById("main-content-area");
}

// Set lưu trữ danh sách ID các sản phẩm được tích chọn (để xóa nhiều)
window.selectedProductIds = new Set();
// Set lưu trữ danh sách ID các sản phẩm bị loại trừ (khi chọn Chọn tất cả các trang)
window.excludedProductIds = new Set();
// Trạng thái đã chọn toàn bộ bản ghi trên hệ thống hay chưa
window.isGlobalProductSelectAll = false;


/**
 * Ẩn hoặc hiện hiệu ứng mờ (overlay loading) trên bảng dữ liệu
 * @param {boolean} isVisible - true để làm mờ và chặn click, false để khôi phục bình thường
 */
function setLoaderVisible(isVisible) {
    if (!tableWrapper) return;

    if (isVisible) {
        tableWrapper.style.opacity = '0.5';
        tableWrapper.style.pointerEvents = 'none';
    } else {
        tableWrapper.style.opacity = '1';
        tableWrapper.style.pointerEvents = 'auto';
    }
}

/**
 * Gọi API lấy danh sách sản phẩm bằng AJAX (fetch) dựa trên bộ lọc
 * @param {string|null} urlStr - URL của phân trang cần chuyển tới, null để lấy theo bộ lọc hiện tại
 */
function fetchProducts(urlStr = null) {
    let url;

    if (urlStr) {
        url = new URL(urlStr);
    } else {
        resetProductSelection(); // Reset lại các checkbox đã chọn khi thay đổi bộ lọc
        url = new URL(filterForm.action);
        const formData = new FormData(filterForm);
        url.search = new URLSearchParams(formData).toString();
    }

    // Đẩy trạng thái URL mới lên thanh địa chỉ mà không tải lại trang
    window.history.pushState({}, "", url);
    setLoaderVisible(true);

    fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            // Cập nhật tổng số lượng bản ghi hiển thị
            if (data.total !== undefined) {
                const totalInput = document.getElementById("total-products-count");
                if (totalInput) totalInput.value = data.total;
            }

            // Ghi đè HTML bảng dữ liệu mới nhận được vào view
            if (data.html) {
                tableWrapper.innerHTML = data.html;
                document.dispatchEvent(new Event("tableDataLoaded"));
            }

            updateClearFilterButton(url);
        })
        .catch((error) => console.error("Lỗi khi tải dữ liệu sản phẩm:", error))
        .finally(() => setLoaderVisible(false));
}

/**
 * Hiển thị hoặc ẩn nút "Xóa bộ lọc" tùy thuộc có tham số lọc nào đang chạy
 */
function updateClearFilterButton(url) {
    if (!btnClearFilter) return;

    const hasFilters = [...new URLSearchParams(url.search)].some(
        ([key, value]) =>
            (key === "search" && value !== "") ||
            (key !== "search" && key !== "page" && value !== "all" && value !== "newest"),
    );

    btnClearFilter.style.display = hasFilters ? "flex" : "none";
}

/**
 * Cập nhật giao diện nút Xóa nhiều dựa trên số lượng checkbox được chọn
 */
function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");
    const count = getSelectedProductCount();

    // Hiển thị số lượng được chọn bên cạnh tiêu đề nút
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
 * Lấy tổng số lượng sản phẩm từ ô input ẩn
 */
function getTotalProductsCount() {
    const totalInput = document.getElementById("total-products-count");
    return totalInput ? Number.parseInt(totalInput.value, 10) || 0 : 0;
}

/**
 * Tính số lượng sản phẩm thực tế đang được chọn
 */
function getSelectedProductCount() {
    if (window.isGlobalProductSelectAll) {
        // Lấy tổng số trên tất cả các trang trừ đi danh sách bị bỏ chọn
        return Math.max(getTotalProductsCount() - window.excludedProductIds.size, 0);
    }
    return window.selectedProductIds.size;
}

/**
 * Đặt lại trạng thái lựa chọn hàng loạt về ban đầu
 */
function resetProductSelection() {
    window.isGlobalProductSelectAll = false;
    window.selectedProductIds.clear();
    window.excludedProductIds.clear();
    updateBulkDeleteButton();
}

/**
 * Đồng bộ hóa trạng thái hiển thị của các ô Checkbox trong bảng theo Set dữ liệu
 */
function syncProductCheckboxes() {
    const checkboxes = document.querySelectorAll(".product-checkbox");

    checkboxes.forEach((checkbox) => {
        checkbox.checked = window.isGlobalProductSelectAll
            ? !window.excludedProductIds.has(checkbox.value)
            : window.selectedProductIds.has(checkbox.value);
    });

    const selectAllCheckboxes = document.querySelectorAll(".js-select-all");
    selectAllCheckboxes.forEach(selectAll => {
        if (window.isGlobalProductSelectAll) {
            selectAll.checked = window.excludedProductIds.size === 0;
            selectAll.indeterminate = window.excludedProductIds.size > 0;
        } else {
            selectAll.checked =
                checkboxes.length > 0 &&
                document.querySelectorAll(".product-checkbox:checked").length === checkboxes.length;
            selectAll.indeterminate = false;
        }
    });

    updateBulkDeleteButton();
}

/**
 * Xử lý khi nhấn nút "Chọn tất cả" ở đầu bảng
 */
function handleSelectAll(checked) {
    window.isGlobalProductSelectAll = checked;
    window.selectedProductIds.clear();
    window.excludedProductIds.clear();

    document.querySelectorAll(".product-checkbox").forEach((checkbox) => {
        checkbox.checked = checked;
    });

    updateBulkDeleteButton();
}

/**
 * Xử lý khi nhấn chọn/bỏ chọn một checkbox sản phẩm đơn lẻ
 */
function handleProductCheckbox(checkbox) {
    if (window.isGlobalProductSelectAll) {
        if (checkbox.checked) {
            window.excludedProductIds.delete(checkbox.value);
        } else {
            window.excludedProductIds.add(checkbox.value);
        }
    } else {
        if (checkbox.checked) {
            window.selectedProductIds.add(checkbox.value);
        } else {
            window.selectedProductIds.delete(checkbox.value);
        }
    }

    syncProductCheckboxes();
}

/**
 * Mở hộp thoại xác nhận trước khi thực hiện xóa nhiều
 */
function submitBulkDelete() {
    const count = getSelectedProductCount();

    if (count === 0) {
        return;
    }

    const titleText = window.isGlobalProductSelectAll
        ? "Xác nhận xóa tất cả?"
        : "Xác nhận xóa nhiều?";
    const messageText = window.isGlobalProductSelectAll
        ? `Bạn chuẩn bị xóa TẤT CẢ ${count} sản phẩm trùng khớp với bộ lọc (bao gồm các trang khác).`
        : `Bạn chuẩn bị xóa ${count} sản phẩm đã chọn.`;

    window.AdminAlert.confirm(messageText, function () {
        executeBulkDelete();
    }, window.isGlobalProductSelectAll ? "Xác nhận xóa tất cả?" : "Xác nhận xóa nhiều?");
}

/**
 * Gửi dữ liệu xóa nhiều bằng AJAX về cho máy chủ (Server)
 */
function executeBulkDelete() {
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    if (!bulkDeleteForm) return;

    const formData = new FormData();
    formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);

    if (window.isGlobalProductSelectAll) {
        // Tùy chọn xóa mọi trang khớp với bộ lọc
        formData.append("delete_all_pages", "1");
        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams.entries()) {
            if (key !== "page" && value !== "") {
                formData.append(key, value);
            }
        }
        window.excludedProductIds.forEach((id) => {
            formData.append("excluded_product_ids[]", id);
        });
    } else {
        // Chỉ xóa các ID cụ thể được chọn
        window.selectedProductIds.forEach((id) => {
            formData.append("product_ids[]", id);
        });
    }

    setLoaderVisible(true);
    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;

    fetch(bulkDeleteForm.action, {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.AdminAlert.success(data.message);
                resetProductSelection();
                fetchProducts(window.location.href);
            } else {
                window.AdminAlert.error(data.message || "Có lỗi xảy ra khi xóa.");
                setLoaderVisible(false);
            }
        })
        .catch((error) => {
            console.error("Bulk delete error:", error);
            setLoaderVisible(false);
        });
}

/**
 * Helper thêm thẻ ẩn input vào form
 */
function appendHiddenInput(form, name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

/**
 * Hộp thoại xác nhận xóa một sản phẩm đơn lẻ
 */
function confirmDeleteProduct(event, formElement) {
    event.preventDefault();

    window.AdminAlert.confirm(
        "Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        function () { executeSingleDelete(formElement); },
        "Xác nhận xóa sản phẩm?"
    );

    return false;
}

/**
 * Gửi yêu cầu xóa một sản phẩm về server bằng AJAX
 */
function executeSingleDelete(formElement) {
    const formData = new FormData(formElement);
    setLoaderVisible(true);
    window.pendingScrollY = getScrollContainer()?.scrollTop ?? 0;

    fetch(formElement.action, {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.AdminAlert.success(data.message);
                resetProductSelection();
                fetchProducts(window.location.href);
            } else {
                window.AdminAlert.error(data.message || "Có lỗi xảy ra khi xóa.");
                setLoaderVisible(false);
            }
        })
        .catch((error) => {
            console.error("Delete error:", error);
            setLoaderVisible(false);
        });
}

// Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
// (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

/**
 * Đăng ký tất cả các sự kiện tương tác trên bảng sản phẩm
 */
function initProductTableEvents() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    window.submitBulkDelete = submitBulkDelete;
    window.confirmDeleteProduct = confirmDeleteProduct;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }

    tableContainer.addEventListener("change", function (event) {
        if (event.target.classList.contains("js-select-all")) {
            handleSelectAll(event.target.checked);
            return;
        }

        if (event.target.classList.contains("product-checkbox")) {
            handleProductCheckbox(event.target);
        }
    });

    tableContainer.addEventListener("submit", function (event) {
        const deleteForm = event.target.closest(".js-product-delete-form");
        if (deleteForm) {
            confirmDeleteProduct(event, deleteForm);
        }
    });

    document.addEventListener("tableDataLoaded", () => {
        if (window.pendingScrollY !== undefined) {
            const container = getScrollContainer();
            if (container) container.scrollTop = window.pendingScrollY;
            window.pendingScrollY = undefined;
        }
        syncProductCheckboxes();
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === "childList") {
                syncProductCheckboxes();
            }
        });
    });
    observer.observe(tableWrapper, { childList: true, subtree: true });
}

// Khởi chạy khi tài liệu HTML sẵn sàng
document.addEventListener("DOMContentLoaded", function () {
    filterForm = document.getElementById("filter-form");
    tableContainer = document.getElementById("table-container");
    tableWrapper = document.getElementById("products-table-wrapper");
    btnClearFilter = document.getElementById("btn-clear-filter");
    loader = document.getElementById("table-loader");

    if (!filterForm || !tableContainer || !tableWrapper) return;

    initProductTableEvents();
    syncProductCheckboxes();
});

// Xử lý nút Back/Forward của trình duyệt. 
window.addEventListener("popstate", function () {
    window.location.reload();
});

// Giữ lại vị trí cuộn khi rời trang và khôi phục lại khi quay về
const PRODUCTS_SCROLL_STORAGE_KEY = "admin-products-scroll-y";

window.addEventListener("beforeunload", function () {
    const container = getScrollContainer();
    if (container) sessionStorage.setItem(PRODUCTS_SCROLL_STORAGE_KEY, String(container.scrollTop));
});

window.addEventListener("load", function () {
    const savedScrollY = sessionStorage.getItem(PRODUCTS_SCROLL_STORAGE_KEY);
    const container = getScrollContainer();
    if (savedScrollY !== null && container) {
        container.scrollTop = parseInt(savedScrollY, 10);
        sessionStorage.removeItem(PRODUCTS_SCROLL_STORAGE_KEY);
    }
});
