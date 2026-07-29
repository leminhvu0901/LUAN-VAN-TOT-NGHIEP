(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        const page = document.getElementById("materials-index-page");
        const filterForm = document.getElementById("filter-form");
        const tableContainer = document.getElementById("table-container");
        const loader = document.getElementById("table-loader");
        const searchInput = document.getElementById("search-input");
        const statusSelect = document.getElementById("status-select");
        const sortSelect = document.getElementById("sort-select");
        const clearFilterButton = document.getElementById("btn-clear-filter");
        const bulkDeleteButton = document.getElementById("bulk-delete-btn");
        const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
        const selectedCount = document.getElementById("selected-count");
        const bulkDeleteForm = document.getElementById("bulk-delete-form");

        if (!page || !filterForm || !tableContainer) return;

        const state = {
            globalSelectAll: false,
            selectedIds: new Set(),
            excludedIds: new Set(),
        };
        let searchTimeout = null;
        let activeRequest = null;

        MaterialsCommon.bindCurrencyInput(
            document.getElementById("add-formatted-price"),
            document.getElementById("add-raw-price"),
        );

        function setLoaderVisible(visible) {
            if (!tableContainer) return;
            if (visible) {
                tableContainer.style.opacity = '0.5';
                tableContainer.style.pointerEvents = 'none';
            } else {
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            }
        }

        function getTotalCount() {
            return Number.parseInt(document.getElementById("total-materials-count")?.value || "0", 10) || 0;
        }

        function getSelectedCount() {
            return state.globalSelectAll
                ? Math.max(getTotalCount() - state.excludedIds.size, 0)
                : state.selectedIds.size;
        }

        function resetSelection() {
            state.globalSelectAll = false;
            state.selectedIds.clear();
            state.excludedIds.clear();
            syncCheckboxes();
        }

        function updateBulkDeleteButton() {
            const count = getSelectedCount();

            if (selectedCount) selectedCount.textContent = count > 0 ? `(${count})` : "";
            if (!bulkDeleteButton) return;

            bulkDeleteButton.classList.toggle("hidden", count === 0);
            bulkDeleteButton.classList.toggle("flex", count > 0);
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.toggle("hidden", count === 0);
                bulkDeselectBtn.classList.toggle("flex", count > 0);
            }
        }

        function syncCheckboxes() {
            const rowCheckboxes = [
                ...tableContainer.querySelectorAll(".material-checkbox:not(:disabled)"),
            ];

            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = state.globalSelectAll
                    ? !state.excludedIds.has(checkbox.value)
                    : state.selectedIds.has(checkbox.value);
            });

            const selectAllEls = tableContainer.querySelectorAll(".js-select-all");
            if (selectAllEls.length > 0) {
                selectAllEls.forEach((selectAll) => {
                    if (state.globalSelectAll) {
                        selectAll.checked = state.excludedIds.size === 0;
                        selectAll.indeterminate = state.excludedIds.size > 0;
                    } else {
                        selectAll.checked =
                            rowCheckboxes.length > 0 && rowCheckboxes.every((checkbox) => checkbox.checked);
                        selectAll.indeterminate = false;
                    }
                });
            }

            updateBulkDeleteButton();
        }

        function buildFilterUrl() {
            const url = new URL(filterForm.action, window.location.origin);
            const params = new URLSearchParams(new FormData(filterForm));

            for (const [key, value] of [...params.entries()]) {
                if (!value || value === "all" || value === "newest") params.delete(key);
            }

            url.search = params.toString();
            return url;
        }

        function syncFilterFormFromUrl(url) {
            if (searchInput) searchInput.value = url.searchParams.get("search") || "";
            if (statusSelect) {
                statusSelect.value = url.searchParams.get("status") || "all";
                // Trigger change to update custom UI if it exists
                statusSelect.dispatchEvent(new Event('change'));
            }
            if (sortSelect) {
                sortSelect.value = url.searchParams.get("sort") || "newest";
                // Trigger change to update custom UI if it exists
                sortSelect.dispatchEvent(new Event('change'));
            }
        }

        function updateClearFilterButton() {
            if (!clearFilterButton) return;
            const hasFilters =
                Boolean(searchInput?.value.trim()) ||
                (statusSelect && statusSelect.value !== "all") ||
                (sortSelect && sortSelect.value !== "newest");
            clearFilterButton.style.display = hasFilters ? "flex" : "none";
        }

        async function loadTableData(url = buildFilterUrl(), options = {}) {
            const { preserveSelection = false, pushHistory = true } = options;
            const targetUrl = new URL(url, window.location.origin);

            if (!preserveSelection) resetSelection();
            if (activeRequest) activeRequest.abort();
            const requestController = new AbortController();
            activeRequest = requestController;
            setLoaderVisible(true);

            try {
                const response = await fetch(targetUrl, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                    signal: requestController.signal,
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                tableContainer.innerHTML = data.html;
                syncCheckboxes();
                updateClearFilterButton();
                document.dispatchEvent(new Event("tableDataLoaded"));

                if (pushHistory) window.history.pushState({}, "", targetUrl);
            } catch (error) {
                if (error.name !== "AbortError") {
                    console.error("Lỗi khi tải danh sách vật tư:", error);
                }
            } finally {
                if (activeRequest === requestController) {
                    activeRequest = null;
                    setLoaderVisible(false);
                }
            }
        }

        function appendHiddenInput(form, name, value) {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        async function executeBulkDelete() {
            if (!bulkDeleteForm) return;

            const url = bulkDeleteForm.action;
            const formData = new FormData(bulkDeleteForm);

            // Xóa các input cũ
            bulkDeleteForm.querySelectorAll('input:not([name="_token"])').forEach(input => input.remove());

            if (state.globalSelectAll) {
                formData.append("delete_all_pages", "1");
                const params = new URLSearchParams(window.location.search);
                for (const [key, value] of params.entries()) {
                    if (key !== "page" && value !== "") formData.append(key, value);
                }
                state.excludedIds.forEach((id) => formData.append("excluded_material_ids[]", id));
            } else {
                state.selectedIds.forEach((id) => formData.append("material_ids[]", id));
            }
            
            const currentScrollY = window.scrollY;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });
                
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.pendingScrollY = currentScrollY;
                    resetSelection();
                    loadTableData();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Không thể xóa',
                            text: data.message
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (error) {
                console.error("Lỗi khi xóa hàng loạt:", error);
            }
        }

        async function submitBulkDelete() {
            const count = getSelectedCount();
            if (count === 0) return;

            const confirmed = await MaterialsCommon.confirmAction(
                state.globalSelectAll ? "Xác nhận xóa tất cả?" : "Xác nhận xóa nhiều?",
                state.globalSelectAll
                    ? `Bạn chuẩn bị xóa ${count} vật tư trùng khớp với bộ lọc trên tất cả các trang. Các vật tư đang bị ràng buộc sẽ được giữ lại.`
                    : `Bạn chuẩn bị xóa ${count} vật tư đã chọn. Các vật tư đang bị ràng buộc sẽ được giữ lại.`,
            );

            if (confirmed) executeBulkDelete();
        }

        filterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            loadTableData();
        });

        searchInput?.addEventListener("input", function () {
            resetSelection();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadTableData(), 400);
        });

        statusSelect?.addEventListener("change", () => loadTableData());
        sortSelect?.addEventListener("change", () => loadTableData());

        clearFilterButton?.addEventListener("click", function (event) {
            event.preventDefault();
            filterForm.reset();
            if (searchInput) searchInput.value = "";
            if (statusSelect) {
                statusSelect.value = "all";
                statusSelect.dispatchEvent(new Event('change'));
            }
            if (sortSelect) {
                sortSelect.value = "newest";
                sortSelect.dispatchEvent(new Event('change'));
            }
            loadTableData();
        });

        bulkDeleteButton?.addEventListener("click", submitBulkDelete);
        bulkDeselectBtn?.addEventListener("click", resetSelection);

        tableContainer.addEventListener("click", function (event) {
            const paginationLink = event.target.closest(".ajax-pagination a");
            if (!paginationLink) return;

            event.preventDefault();
            loadTableData(paginationLink.href, { preserveSelection: true });
        });

        tableContainer.addEventListener("change", function (event) {
            if (event.target.classList.contains("js-select-all")) {
                state.globalSelectAll = event.target.checked;
                state.selectedIds.clear();
                state.excludedIds.clear();
                syncCheckboxes();
                return;
            }

            if (!event.target.classList.contains("material-checkbox")) return;

            if (state.globalSelectAll) {
                if (event.target.checked) state.excludedIds.delete(event.target.value);
                else state.excludedIds.add(event.target.value);
            } else if (event.target.checked) {
                state.selectedIds.add(event.target.value);
            } else {
                state.selectedIds.delete(event.target.value);
            }

            syncCheckboxes();
        });

        tableContainer.addEventListener("submit", async function (event) {
            const deleteForm = event.target.closest(".js-material-delete-form");
            if (!deleteForm) return;

            event.preventDefault();
            const confirmed = await MaterialsCommon.confirmAction(
                "Xác nhận xóa vật tư?",
                "Vật tư này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
            );
            
            if (confirmed) {
                if (deleteForm.dataset.ajax === "true") {
                    const currentScrollY = window.scrollY;
                    const url = deleteForm.action;
                    
                    try {
                        const response = await fetch(url, {
                            method: "POST", // Vì Laravel dùng DELETE qua method spoofing _method=DELETE
                            body: new FormData(deleteForm),
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                "Accept": "application/json"
                            }
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            window.pendingScrollY = currentScrollY;
                            loadTableData();
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: data.message
                                });
                            } else {
                                alert(data.message);
                            }
                        }
                    } catch (error) {
                        console.error("Lỗi khi xóa vật tư:", error);
                    }
                } else {
                    deleteForm.submit();
                }
            }
        });

        // Xử lý nút điều hướng Back/Forward trình duyệt (popstate). Trước đây gọi lại loadTableData()
        // (fetch kèm Accept: application/json) để "đồng bộ" bảng theo URL vừa quay lại — nhưng có
        // trường hợp (vd bfcache khôi phục trang) khiến JSON trả về bị hiển thị thẳng ra thành nội
        // dung trang thay vì được JS chèn vào bảng. Tải lại thẳng trang an toàn hơn nhiều.
        window.addEventListener("popstate", function () {
            window.location.reload();
        });

        document.addEventListener("tableDataLoaded", function () {
            if (window.pendingScrollY !== undefined) {
                window.scrollTo({ top: window.pendingScrollY, behavior: 'instant' });
                window.pendingScrollY = undefined;
            }
        });

        updateClearFilterButton();
        syncCheckboxes();
    });
})();
