let filterTimeout = null;
let filterForm;
let tableContainer;
let tableWrapper;
let btnClearFilter;
let loader;

// Layout admin KHÔNG cuộn ở <body>/window (body có class 'overflow-hidden' khóa cứng) — phần tử
// thực sự cuộn là #main-content-area (xem resources/views/backend/layouts/app.blade.php). Mọi chỗ
// cần đọc/ghi vị trí cuộn trong file này phải dùng phần tử này, không phải window.scrollY/scrollTo.
function getScrollContainer() {
    return document.getElementById("main-content-area");
}

window.selectedProductIds = new Set();
window.excludedProductIds = new Set();
window.isGlobalProductSelectAll = false;


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

function fetchProducts(urlStr = null) {
    let url;

    if (urlStr) {
        url = new URL(urlStr);
    } else {
        resetProductSelection();
        url = new URL(filterForm.action);
        const formData = new FormData(filterForm);
        url.search = new URLSearchParams(formData).toString();
    }

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
            if (data.total !== undefined) {
                const totalInput = document.getElementById("total-products-count");
                if (totalInput) totalInput.value = data.total;
            }

            if (data.html) {
                tableWrapper.innerHTML = data.html;
                document.dispatchEvent(new Event("tableDataLoaded"));
            }

            updateClearFilterButton(url);
        })
        .catch((error) => console.error("Lỗi khi tải dữ liệu sản phẩm:", error))
        .finally(() => setLoaderVisible(false));
}

function updateClearFilterButton(url) {
    if (!btnClearFilter) return;

    const hasFilters = [...new URLSearchParams(url.search)].some(
        ([key, value]) =>
            (key === "search" && value !== "") ||
            (key !== "search" && key !== "page" && value !== "all" && value !== "newest"),
    );

    btnClearFilter.style.display = hasFilters ? "flex" : "none";
}

function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");
    const count = getSelectedProductCount();

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

function getTotalProductsCount() {
    const totalInput = document.getElementById("total-products-count");
    return totalInput ? Number.parseInt(totalInput.value, 10) || 0 : 0;
}

function getSelectedProductCount() {
    if (window.isGlobalProductSelectAll) {
        return Math.max(getTotalProductsCount() - window.excludedProductIds.size, 0);
    }

    return window.selectedProductIds.size;
}

function resetProductSelection() {
    window.isGlobalProductSelectAll = false;
    window.selectedProductIds.clear();
    window.excludedProductIds.clear();
    updateBulkDeleteButton();
}

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

function handleSelectAll(checked) {
    window.isGlobalProductSelectAll = checked;
    window.selectedProductIds.clear();
    window.excludedProductIds.clear();

    document.querySelectorAll(".product-checkbox").forEach((checkbox) => {
        checkbox.checked = checked;
    });

    updateBulkDeleteButton();
}

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

function executeBulkDelete() {
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    if (!bulkDeleteForm) return;

    const formData = new FormData();
    formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);


    if (window.isGlobalProductSelectAll) {
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

function appendHiddenInput(form, name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

function confirmDeleteProduct(event, formElement) {
    event.preventDefault();

    window.AdminAlert.confirm(
        "Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        function () { executeSingleDelete(formElement); },
        "Xác nhận xóa sản phẩm?"
    );

    return false;
}

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

function initProductFilters() {
    filterForm.querySelectorAll("select").forEach((select) => {
        select.addEventListener("change", () => fetchProducts());
    });

    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => fetchProducts(), 400);
        });
    }

    if (btnClearFilter) {
        btnClearFilter.addEventListener("click", function (event) {
            event.preventDefault();
            filterForm.reset();

            filterForm.querySelectorAll("select").forEach((select) => {
                select.value = select.name === "sort" ? "newest" : "all";
            });

            if (searchInput) searchInput.value = "";

            fetchProducts();
        });
    }
}

function initProductTableEvents() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    window.submitBulkDelete = submitBulkDelete;
    window.confirmDeleteProduct = confirmDeleteProduct;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }

    tableContainer.addEventListener("click", function (event) {
        const pageLink = event.target.closest(".ajax-pagination a, .pagination-container a");
        if (pageLink) {
            event.preventDefault();
            fetchProducts(pageLink.href);
        }
    });

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

document.addEventListener("DOMContentLoaded", function () {
    filterForm = document.getElementById("filter-form");
    tableContainer = document.getElementById("table-container");
    tableWrapper = document.getElementById("products-table-wrapper");
    btnClearFilter = document.getElementById("btn-clear-filter");
    loader = document.getElementById("table-loader");

    if (!filterForm || !tableContainer || !tableWrapper) return;

    initProductFilters();
    initProductTableEvents();
    syncProductCheckboxes();
});

// Xử lý nút Back/Forward của trình duyệt. Trước đây gọi lại fetchProducts() (fetch kèm
// Accept: application/json) để "đồng bộ" bảng theo URL vừa quay lại — nhưng có trường hợp (vd bfcache
// khôi phục trang) khiến JSON trả về bị hiển thị thẳng ra thành nội dung trang thay vì được JS chèn
// vào bảng. Tải lại thẳng trang an toàn hơn nhiều (đã tự kiểm chứng luôn trả đúng HTML đầy đủ).
window.addEventListener("popstate", function () {
    window.location.reload();
});

// Giữ lại vị trí cuộn khi rời trang (bấm Sửa 1 sản phẩm đang ở giữa danh sách dài) và khôi phục lại
// SAU KHI quay về từ trang Sửa (redirect tải lại trang thật, khác với window.pendingScrollY ở trên
// chỉ sống được trong bộ nhớ JS nên không sống sót qua 1 lần tải trang mới — phải dùng sessionStorage).
// Cũng phải đọc/ghi scrollTop của #main-content-area (xem getScrollContainer() ở đầu file) — KHÔNG
// phải window.scrollY, vì <body> khóa cứng overflow-hidden nên window không bao giờ thật sự cuộn.
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
