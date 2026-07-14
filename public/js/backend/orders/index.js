let searchTimeout = null;
let form;
let tableContainer;
let loader;
let loadAbortController;

function resetOrderSelection() {
    if (!window.selectedOrderIds) return;
    window.selectedOrderIds.clear();
    window.excludedOrderIds.clear();
    window.isGlobalSelectAll = false;
    updateBulkDeleteButton();
}

function loadTableData(url = null) {
    if (!url) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        url = form.action + "?" + params.toString();
    }

    window.history.pushState({}, "", url);

    loader.classList.remove("hidden");
    loader.classList.add("flex");

    if (loadAbortController) loadAbortController.abort();
    loadAbortController = new AbortController();
    fetch(url, {
        signal: loadAbortController.signal,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((res) => {
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json();
            }

            return res.text().then((text) => ({ table_html: text }));
        })
        .then((data) => {
            if (data.table_html) {
                const wrapper = tableContainer.querySelector(".overflow-x-auto");
                wrapper.innerHTML = data.table_html;
                attachPaginationListeners();
                document.dispatchEvent(new Event("tableDataLoaded"));
            }

            if (data.stats_html) {
                const statsContainer = document.getElementById("stats-container");
                if (statsContainer) statsContainer.innerHTML = data.stats_html;
            }

            loader.classList.add("hidden");
            loader.classList.remove("flex");
        })
        .catch((err) => {
            if (err.name === "AbortError") return;
            console.error(err);
            loader.classList.add("hidden");
            loader.classList.remove("flex");
        });
}

function handleLiveSearch() {
    resetOrderSelection();
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTableData(), 500);
}

function attachPaginationListeners() {
    const wrapper = tableContainer.querySelector(".overflow-x-auto");
    wrapper.querySelectorAll(".ajax-pagination a").forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            loadTableData(this.href);
        });
    });
}

function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const selectedCountSpan = document.getElementById("selected-count");

    if (!bulkDeleteBtn || !selectedCountSpan) return;

    let countText = window.selectedOrderIds.size;

    if (window.isGlobalSelectAll) {
        const totalInput = document.getElementById("total-orders-count");
        if (totalInput) countText = totalInput.value;
    }

    if (countText > 0) {
        selectedCountSpan.textContent = `(${countText})`;
        bulkDeleteBtn.classList.remove("hidden");
        bulkDeleteBtn.classList.add("flex");
    } else {
        bulkDeleteBtn.classList.add("hidden");
        bulkDeleteBtn.classList.remove("flex");
    }
}

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

function submitBulkDelete() {
    const isSelectAll = document.getElementById("selectAll")
        ? document.getElementById("selectAll").checked
        : false;

    let titleText = "Xác nhận xóa nhiều?";
    let messageText = `Bạn chuẩn bị xóa ${window.selectedOrderIds.size} đơn hàng đã chọn.`;

    if (isSelectAll) {
        titleText = "Xác nhận xóa tất cả?";
        let countText = window.selectedOrderIds.size;
        const totalInput = document.getElementById("total-orders-count");
        if (totalInput) {
            countText = totalInput.value;
        }
        messageText = `Bạn chuẩn bị xóa TẤT CẢ ${countText} đơn hàng trùng khớp với bộ lọc (bao gồm các trang khác).`;
    }

    if (typeof Swal !== "undefined") {
        Swal.fire({
            ...swalConfig,
            title: titleText,
            text: messageText,
        }).then((result) => {
            if (result.isConfirmed) {
                executeBulkDelete(isSelectAll);
            }
        });
    } else if (confirm(messageText)) {
        executeBulkDelete(isSelectAll);
    }
}

function executeBulkDelete(isSelectAll) {
    const bulkDeleteForm = document.getElementById("bulk-delete-form");

    bulkDeleteForm
        .querySelectorAll('input:not([name="_token"])')
        .forEach((el) => el.remove());

    if (isSelectAll) {
        const inputAll = document.createElement("input");
        inputAll.type = "hidden";
        inputAll.name = "delete_all_pages";
        inputAll.value = "1";
        bulkDeleteForm.appendChild(inputAll);

        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams.entries()) {
            if (key !== "page" && value !== "") {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = key;
                input.value = value;
                bulkDeleteForm.appendChild(input);
            }
        }
        window.excludedOrderIds.forEach((id) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "excluded_order_ids[]";
            input.value = id;
            bulkDeleteForm.appendChild(input);
        });
    } else {
        window.selectedOrderIds.forEach((id) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "order_ids[]";
            input.value = id;
            bulkDeleteForm.appendChild(input);
        });
    }

    bulkDeleteForm.submit();
}

function confirmDeleteOrder(event, formElement) {
    event.preventDefault();

    if (typeof Swal !== "undefined") {
        Swal.fire({
            ...swalConfig,
            title: "Xác nhận xóa đơn hàng?",
            text: "Đơn hàng này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        }).then((result) => {
            if (result.isConfirmed) {
                formElement.submit();
            }
        });
    } else if (confirm("Bạn có chắc chắn muốn xóa đơn hàng này?")) {
        formElement.submit();
    }

    return false;
}

function initSearchAndFilters() {
    form = document.getElementById("search-form");
    tableContainer = document.getElementById("table-container");
    loader = document.getElementById("table-loader");

    document.getElementById("search-input").addEventListener("input", handleLiveSearch);
    document.getElementById("date-from-input").addEventListener("change", handleLiveSearch);
    document.getElementById("date-to-input").addEventListener("change", handleLiveSearch);

    const statusSelect = form.querySelector('select[name="status"]');
    if (statusSelect) statusSelect.addEventListener("change", handleLiveSearch);

    const sortSelect = form.querySelector('select[name="sort"]');
    if (sortSelect) sortSelect.addEventListener("change", handleLiveSearch);

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        loadTableData();
    });

    attachPaginationListeners();
}

function initTableEvents() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    window.isGlobalSelectAll = false;
    window.selectedOrderIds = new Set();
    window.excludedOrderIds = new Set();
    window.submitBulkDelete = submitBulkDelete;
    window.confirmDeleteOrder = confirmDeleteOrder;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }

    tableContainer.addEventListener("change", function (e) {
        if (e.target.id === "selectAll") {
            const isChecked = e.target.checked;
            window.isGlobalSelectAll = isChecked;

            if (!isChecked) {
                window.selectedOrderIds.clear();
                window.excludedOrderIds.clear();
            }

            document.querySelectorAll(".order-checkbox").forEach((cb) => {
                cb.checked = isChecked;
                if (isChecked) window.selectedOrderIds.add(cb.value);
            });

            updateBulkDeleteButton();
            return;
        }

        if (e.target.classList.contains("order-checkbox")) {
            if (window.isGlobalSelectAll) {
                if (e.target.checked) window.excludedOrderIds.delete(e.target.value);
                else window.excludedOrderIds.add(e.target.value);
                updateBulkDeleteButton();
                return;
            }
            if (e.target.checked) {
                window.selectedOrderIds.add(e.target.value);
            } else {
                window.selectedOrderIds.delete(e.target.value);
                window.isGlobalSelectAll = false;
            }

            const allCheckboxes = document.querySelectorAll(".order-checkbox");
            const allChecked =
                document.querySelectorAll(".order-checkbox:checked").length === allCheckboxes.length;
            const selectAll = document.getElementById("selectAll");
            if (selectAll) selectAll.checked = allChecked;

            updateBulkDeleteButton();
            return;
        }

        if (e.target.classList.contains("js-order-status-select")) {
            if (e.target.value !== "cancelled") {
                e.target.form.submit();
                return;
            }
            const select = e.target;
            Swal.fire({
                title: "Lý do hủy đơn",
                input: "textarea",
                inputPlaceholder: "Nhập lý do hủy (ít nhất 5 ký tự)",
                showCancelButton: true,
                confirmButtonText: "Hủy đơn",
                cancelButtonText: "Đóng",
                inputValidator: (value) => !value || value.trim().length < 5 ? "Vui lòng nhập ít nhất 5 ký tự." : undefined,
            }).then((result) => {
                if (!result.isConfirmed) {
                    select.value = select.dataset.currentStatus;
                    return;
                }
                const reason = document.createElement("input");
                reason.type = "hidden";
                reason.name = "cancel_reason";
                reason.value = result.value.trim();
                select.form.appendChild(reason);
                select.form.submit();
            });
        }
    });

    tableContainer.addEventListener("submit", function (e) {
        const deleteForm = e.target.closest(".js-order-delete-form");
        if (deleteForm) {
            confirmDeleteOrder(e, deleteForm);
        }
    });

    document.addEventListener("tableDataLoaded", function () {
        if (window.isGlobalSelectAll) {
            const selectAll = document.getElementById("selectAll");
            if (selectAll) selectAll.checked = true;

            document.querySelectorAll(".order-checkbox").forEach((cb) => {
                cb.checked = true;
                window.selectedOrderIds.add(cb.value);
            });
        } else {
            document.querySelectorAll(".order-checkbox").forEach((cb) => {
                if (window.selectedOrderIds.has(cb.value)) {
                    cb.checked = true;
                }
            });

            const allCheckboxes = document.querySelectorAll(".order-checkbox");
            if (allCheckboxes.length > 0) {
                const allChecked =
                    document.querySelectorAll(".order-checkbox:checked").length === allCheckboxes.length;
                const selectAll = document.getElementById("selectAll");
                if (selectAll) selectAll.checked = allChecked;
            }
        }

        updateBulkDeleteButton();
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === "childList") {
                updateBulkDeleteButton();
            }
        });
    });
    observer.observe(tableContainer, { childList: true, subtree: true });
}

document.addEventListener("DOMContentLoaded", function () {
    initSearchAndFilters();
    initTableEvents();
});

window.addEventListener("popstate", function () {
    resetOrderSelection();
    loadTableData(window.location.href);
});
