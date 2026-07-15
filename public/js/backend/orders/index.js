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
                const tableWrapper = document.getElementById("orders-table-wrapper");
                if (tableWrapper) {
                    tableWrapper.innerHTML = data.table_html;
                } else {
                    tableContainer.innerHTML = data.table_html;
                }
                
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
    // Không cần nữa vì đã dùng event delegation ở dưới
}

function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
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



function submitBulkDelete() {
    const selectAllEls = document.querySelectorAll(".js-select-all");
    const isSelectAll = selectAllEls.length > 0 ? selectAllEls[0].checked : false;

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

    window.AdminAlert.confirm(messageText, function () {
        executeBulkDelete(isSelectAll);
    }, titleText);
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

function deleteOrderAjax(btn) {
    window.AdminAlert.confirm(
        "Đơn hàng này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        function () { executeDeleteOrderAjax(btn); },
        "Xác nhận xóa đơn hàng?"
    );
}

function executeDeleteOrderAjax(btn) {
    const url = btn.dataset.url;
    const orderId = btn.dataset.id;
    const currentScrollY = window.scrollY;

    fetch(url, {
        method: "DELETE",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.AdminAlert.success(data.message || "Xóa đơn hàng thành công!");

                document.querySelectorAll(`[data-order-id="${orderId}"]`).forEach(el => el.remove());

                const remainingCards = document.querySelectorAll('.mobile-card').length;
                const remainingRows = document.querySelectorAll('tbody tr[data-order-id]').length;

                if (remainingCards === 0 && remainingRows === 0) {
                    const urlParams = new URLSearchParams(window.location.search);
                    let page = parseInt(urlParams.get('page')) || 1;
                    if (page > 1) {
                        urlParams.set('page', page - 1);
                        window.history.replaceState({}, "", `${window.location.pathname}?${urlParams.toString()}`);
                    }
                }

                resetOrderSelection();
                window.pendingScrollY = currentScrollY;
                loadTableData(window.location.href);
            } else {
                window.AdminAlert.error(data.message || "Có lỗi xảy ra, không thể xóa đơn hàng!");
            }
        })
        .catch(err => {
            console.error(err);
            window.AdminAlert.error("Có lỗi xảy ra khi xóa đơn hàng! Vui lòng thử lại.");
        });
}

function initSearchAndFilters() {
    form = document.getElementById("search-form");
    tableContainer = document.getElementById("table-container");
    loader = document.getElementById("table-loader");

    document.getElementById("search-input").addEventListener("input", handleLiveSearch);

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
            onChange: function () {
                handleLiveSearch();
            }
        });
    } else {
        document.getElementById("date-from-input").addEventListener("change", handleLiveSearch);
        document.getElementById("date-to-input").addEventListener("change", handleLiveSearch);
    }

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
    window.deleteOrderAjax = deleteOrderAjax;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }
    
    // Thêm event delegation cho phân trang (giống hệt trang products)
    if (tableContainer) {
        tableContainer.addEventListener("click", function (event) {
            const pageLink = event.target.closest(".ajax-pagination a, .pagination-container a");
            if (pageLink) {
                event.preventDefault();
                loadTableData(pageLink.href);
            }
        });
    }

    tableContainer.addEventListener("change", function (e) {
        if (e.target.classList.contains("js-select-all")) {
            const isChecked = e.target.checked;
            window.isGlobalSelectAll = isChecked;

            // Sync all select all checkboxes
            document.querySelectorAll(".js-select-all").forEach(cb => {
                if (cb !== e.target) cb.checked = isChecked;
            });

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
            document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = allChecked);

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

    tableContainer.addEventListener("click", function (e) {
        const deleteBtn = e.target.closest(".delete-order-btn");
        if (deleteBtn) {
            e.preventDefault();
            deleteOrderAjax(deleteBtn);
        }
    });

    document.addEventListener("tableDataLoaded", function () {
        if (window.pendingScrollY !== undefined) {
            window.scrollTo({ top: window.pendingScrollY, behavior: 'instant' });
            window.pendingScrollY = undefined;
        }

        if (window.isGlobalSelectAll) {
            document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = true);

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
                document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = allChecked);
            }
        }

        updateBulkDeleteButton();
        initOrderStatusCustomDropdowns();
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

function initOrderStatusCustomDropdowns() {
    const selects = document.querySelectorAll('.order-status-select:not(.custom-dropdown-initialized)');
    selects.forEach(select => {
        select.classList.add('custom-dropdown-initialized');
        select.classList.add('sr-only');

        select.parentElement.classList.add('relative');

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-order-status-dropdown w-full lg:w-auto relative inline-block';

        const btn = document.createElement('button');
        btn.type = 'button';
        let btnClasses = select.className.replace('order-status-select', '').replace('js-order-status-select', '').replace('sr-only', '').replace('custom-dropdown-initialized', '').trim();
        btn.className = btnClasses + ' flex items-center justify-between gap-1 w-full text-left';

        const selectedOption = select.options[select.selectedIndex];
        btn.innerHTML = `<span class="status-text">${selectedOption.text}</span><span class="material-symbols-outlined text-[16px]">expand_more</span>`;

        const menu = document.createElement('div');
        menu.className = 'status-options-menu hidden absolute left-0 w-full lg:min-w-[160px] lg:w-auto mt-1 bg-white border border-gray-200 rounded-lg organic-shadow z-50 max-h-[220px] overflow-y-auto py-1 shadow-xl text-left';

        Array.from(select.options).forEach(opt => {
            const item = document.createElement('div');
            let bgClass = '';
            let textClass = 'text-gray-700';
            let activeBgClass = '';
            if (opt.value === 'pending') { bgClass = 'hover:bg-yellow-50'; activeBgClass = 'bg-yellow-50'; textClass = 'text-yellow-700'; }
            if (opt.value === 'confirmed') { bgClass = 'hover:bg-blue-50'; activeBgClass = 'bg-blue-50'; textClass = 'text-blue-700'; }
            if (opt.value === 'shipping') { bgClass = 'hover:bg-orange-50'; activeBgClass = 'bg-orange-50'; textClass = 'text-orange-700'; }
            if (opt.value === 'completed') { bgClass = 'hover:bg-emerald-50'; activeBgClass = 'bg-emerald-50'; textClass = 'text-emerald-700'; }
            if (opt.value === 'cancelled') { bgClass = 'hover:bg-red-50'; activeBgClass = 'bg-red-50'; textClass = 'text-red-700'; }

            const isSelected = opt.value === select.dataset.currentStatus;
            const extraClasses = isSelected ? `font-bold ${activeBgClass}` : 'font-medium';

            item.className = `px-3 py-3 lg:py-2 text-sm transition-colors ${bgClass} ${textClass} ${extraClasses}`;

            if (opt.disabled) {
                item.className += ' opacity-50 cursor-not-allowed';
            } else {
                item.className += ' cursor-pointer';
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    select.value = opt.value;
                    menu.classList.add('hidden');
                    btn.querySelector('.status-text').textContent = opt.text;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
            item.textContent = opt.text;
            menu.appendChild(item);
        });

        select.addEventListener('change', () => {
            btn.querySelector('.status-text').textContent = select.options[select.selectedIndex].text;
        });

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.status-options-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');

            if (!menu.classList.contains('hidden')) {
                const rect = btn.getBoundingClientRect();
                if (window.innerHeight - rect.bottom < 250) {
                    menu.classList.remove('top-full', 'mt-1');
                    menu.classList.add('bottom-full', 'mb-1');
                } else {
                    menu.classList.add('top-full', 'mt-1');
                    menu.classList.remove('bottom-full', 'mb-1');
                }
            }
        });

        wrapper.appendChild(btn);
        wrapper.appendChild(menu);
        select.parentNode.insertBefore(wrapper, select);
    });
}

document.addEventListener('click', () => {
    document.querySelectorAll('.status-options-menu').forEach(m => m.classList.add('hidden'));
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.status-options-menu').forEach(m => m.classList.add('hidden'));
    }
});

document.addEventListener("DOMContentLoaded", function () {
    initSearchAndFilters();
    initTableEvents();
    initOrderStatusCustomDropdowns();
});

window.addEventListener("popstate", function () {
    resetOrderSelection();
    loadTableData(window.location.href);
});
