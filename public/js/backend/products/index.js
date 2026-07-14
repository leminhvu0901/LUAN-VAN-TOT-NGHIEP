let filterTimeout = null;
let filterForm;
let tableContainer;
let tableWrapper;
let btnClearFilter;
let loader;

window.selectedProductIds = new Set();
window.excludedProductIds = new Set();
window.isGlobalProductSelectAll = false;

const swalConfig = {
    icon: "warning",
    width: "320px",
    padding: "1rem",
    showCancelButton: true,
    confirmButtonText: "Xóa ngay",
    cancelButtonText: "Hủy",
    reverseButtons: true,
    customClass: {
        popup: "rounded-xl shadow-xl border border-gray-100",
        title: "text-base font-bold text-gray-800",
        htmlContainer: "text-sm text-gray-500 mt-1",
        confirmButton:
            "px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm border-none outline-none ml-2",
        cancelButton:
            "px-4 py-1.5 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all border-none outline-none mr-2",
        icon: "transform scale-[0.6] -mt-3 -mb-2",
        actions: "mt-3 w-full flex justify-center",
    },
    buttonsStyling: false,
};

function setLoaderVisible(isVisible) {
    if (!loader) return;

    loader.classList.toggle("hidden", !isVisible);
    loader.classList.toggle("flex", isVisible);
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
    const selectedCountSpan = document.getElementById("selected-count");
    const count = getSelectedProductCount();

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count > 0 ? `(${count})` : "";
    }

    if (!bulkDeleteBtn) return;

    if (count > 0) {
        bulkDeleteBtn.classList.remove("hidden");
        bulkDeleteBtn.classList.add("flex");
    } else {
        bulkDeleteBtn.classList.add("hidden");
        bulkDeleteBtn.classList.remove("flex");
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

    const selectAll = document.getElementById("selectAll");
    if (selectAll) {
        if (window.isGlobalProductSelectAll) {
            selectAll.checked = window.excludedProductIds.size === 0;
            selectAll.indeterminate = window.excludedProductIds.size > 0;
        } else {
            selectAll.checked =
                checkboxes.length > 0 &&
                document.querySelectorAll(".product-checkbox:checked").length === checkboxes.length;
            selectAll.indeterminate = false;
        }
    }

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

    if (typeof Swal !== "undefined") {
        Swal.fire({
            ...swalConfig,
            title: titleText,
            text: messageText,
        }).then((result) => {
            if (result.isConfirmed) {
                executeBulkDelete();
            }
        });
    } else if (confirm(messageText)) {
        executeBulkDelete();
    }
}

function executeBulkDelete() {
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    if (!bulkDeleteForm) return;

    bulkDeleteForm
        .querySelectorAll('input:not([name="_token"])')
        .forEach((element) => element.remove());

    if (window.isGlobalProductSelectAll) {
        appendHiddenInput(bulkDeleteForm, "delete_all_pages", "1");

        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams.entries()) {
            if (key !== "page" && value !== "") {
                appendHiddenInput(bulkDeleteForm, key, value);
            }
        }

        window.excludedProductIds.forEach((id) => {
            appendHiddenInput(bulkDeleteForm, "excluded_product_ids[]", id);
        });
    } else {
        window.selectedProductIds.forEach((id) => {
            appendHiddenInput(bulkDeleteForm, "product_ids[]", id);
        });
    }

    bulkDeleteForm.submit();
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

    if (typeof Swal !== "undefined") {
        Swal.fire({
            ...swalConfig,
            title: "Xác nhận xóa sản phẩm?",
            text: "Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        }).then((result) => {
            if (result.isConfirmed) {
                formElement.submit();
            }
        });
    } else if (confirm("Bạn có chắc chắn muốn xóa sản phẩm này?")) {
        formElement.submit();
    }

    return false;
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
        if (event.target.id === "selectAll") {
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

    document.addEventListener("tableDataLoaded", syncProductCheckboxes);

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
