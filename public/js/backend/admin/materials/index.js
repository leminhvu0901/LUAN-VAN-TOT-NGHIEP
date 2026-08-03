/**
 * index.js - Quản lý trang danh sách vật tư khu vực Admin
 * Các tính năng bao gồm:
 * - Lọc dữ liệu AJAX (fetch) theo ô tìm kiếm (debounce 400ms), trạng thái và sắp xếp.
 * - Khởi tạo định dạng tiền tệ cho ô nhập Giá vốn ở form Thêm mới vật tư.
 * - Phân trang AJAX và giữ nguyên trạng thái tích chọn checkbox giữa các trang.
 * - Xóa đơn lẻ vật tư bằng AJAX, tự động cuộn lại đúng vị trí cuộn cũ (`pendingScrollY`).
 * - Xóa hàng loạt vật tư đã chọn (Bulk Delete) có hộp thoại xác nhận chi tiết.
 * - Đồng bộ trạng thái Checkbox của bảng (bao gồm hỗ trợ trạng thái bán chọn Indeterminate).
 * - Tự động tải lại trang khi người dùng nhấn nút Back/Forward của trình duyệt để tránh lỗi hiển thị chuỗi JSON thô (Popstate).
 */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        // === PHẦN TỬ DOM ===
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

        // Quản lý trạng thái tích chọn checkbox hàng loạt (State)
        const state = {
            globalSelectAll: false, // true khi tích chọn toàn bộ tất cả bản ghi trên mọi trang
            selectedIds: new Set(), // Set chứa ID được chọn thủ công
            excludedIds: new Set(), // Set chứa ID bị loại trừ (khi bật globalSelectAll nhưng bỏ tích một vài dòng)
        };
        let searchTimeout = null;
        let activeRequest = null; // Quản lý AbortController để hủy các request AJAX cũ nếu người dùng thao tác nhanh

        // Liên kết định dạng tiền tệ cho trường Giá vốn ở form Thêm mới
        MaterialsCommon.bindCurrencyInput(
            document.getElementById("add-formatted-price"),
            document.getElementById("add-raw-price"),
        );

        /**
         * Hiện/Ẩn hiệu ứng mờ loading trên bảng dữ liệu
         */
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

        /**
         * Lấy tổng số lượng vật tư khớp bộ lọc lưu trong input ẩn
         */
        function getTotalCount() {
            return Number.parseInt(document.getElementById("total-materials-count")?.value || "0", 10) || 0;
        }

        /**
         * Tính toán chính xác số lượng vật tư đang được tích chọn
         */
        function getSelectedCount() {
            return state.globalSelectAll
                ? Math.max(getTotalCount() - state.excludedIds.size, 0)
                : state.selectedIds.size;
        }

        /**
         * Đặt lại trạng thái lựa chọn hàng loạt về ban đầu
         */
        function resetSelection() {
            state.globalSelectAll = false;
            state.selectedIds.clear();
            state.excludedIds.clear();
            syncCheckboxes();
        }

        /**
         * Cập nhật hiển thị số lượng và ẩn/hiện nút "Xóa nhiều"
         */
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

        /**
         * Đồng bộ hóa lại hiển thị checkbox của bảng theo dữ liệu trong State
         */
        function syncCheckboxes() {
            const rowCheckboxes = [
                ...tableContainer.querySelectorAll(".material-checkbox:not(:disabled)"),
            ];

            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = state.globalSelectAll
                    ? !state.excludedIds.has(checkbox.value)
                    : state.selectedIds.has(checkbox.value);
            });

            // Đồng bộ nút "Chọn tất cả" ở đầu bảng (gồm cả trạng thái gạch ngang bán chọn Indeterminate)
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

        /**
         * Xây dựng URL chứa các tham số lọc hiện hành
         */
        function buildFilterUrl() {
            const url = new URL(filterForm.action, window.location.origin);
            const params = new URLSearchParams(new FormData(filterForm));

            // Loại bỏ các tham số mặc định để URL thanh lịch hơn
            for (const [key, value] of [...params.entries()]) {
                if (!value || value === "all" || value === "newest") params.delete(key);
            }

            url.search = params.toString();
            return url;
        }

        /**
         * Đồng bộ ngược form lọc từ tham số trên URL (phục vụ Popstate)
         */
        function syncFilterFormFromUrl(url) {
            if (searchInput) searchInput.value = url.searchParams.get("search") || "";
            if (statusSelect) {
                statusSelect.value = url.searchParams.get("status") || "all";
                statusSelect.dispatchEvent(new Event('change'));
            }
            if (sortSelect) {
                sortSelect.value = url.searchParams.get("sort") || "newest";
                sortSelect.dispatchEvent(new Event('change'));
            }
        }

        /**
         * Hiện/Ẩn nút Xóa lọc
         */
        function updateClearFilterButton() {
            if (!clearFilterButton) return;
            const hasFilters =
                Boolean(searchInput?.value.trim()) ||
                (statusSelect && statusSelect.value !== "all") ||
                (sortSelect && sortSelect.value !== "newest");
            clearFilterButton.style.display = hasFilters ? "flex" : "none";
        }

        /**
         * Gửi request AJAX tải lại bảng dữ liệu vật tư
         */
        async function loadTableData(url = buildFilterUrl(), options = {}) {
            const { preserveSelection = false, pushHistory = true } = options;
            const targetUrl = new URL(url, window.location.origin);

            if (!preserveSelection) resetSelection();
            if (activeRequest) activeRequest.abort(); // Hủy request cũ để tránh Race Condition nếu gõ phím quá nhanh
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

        // Helper thêm trường ẩn vào form trước khi submit
        function appendHiddenInput(form, name, value) {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        /**
         * Gửi POST AJAX thực hiện xóa hàng loạt vật tư
         */
        async function executeBulkDelete() {
            if (!bulkDeleteForm) return;

            const url = bulkDeleteForm.action;
            const formData = new FormData(bulkDeleteForm);

            // Dọn dẹp các trường ẩn cũ
            bulkDeleteForm.querySelectorAll('input:not([name="_token"])').forEach(input => input.remove());

            // Đóng gói danh sách ID cần xóa vào FormData
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
                    window.pendingScrollY = currentScrollY; // Lưu vị trí cuộn màn hình để phục hồi sau khi render bảng mới
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

        /**
         * Hiển thị cảnh báo confirm trước khi xóa hàng loạt
         */
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

        // Đăng ký sự kiện submit form bộ lọc
        filterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            loadTableData();
        });

        // Debounce tìm kiếm vật tư tự động sau khi ngừng gõ 400ms
        searchInput?.addEventListener("input", function () {
            resetSelection();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadTableData(), 400);
        });

        statusSelect?.addEventListener("change", () => loadTableData());
        sortSelect?.addEventListener("change", () => loadTableData());

        // Nhấn nút xóa bộ lọc đưa các ô nhập về giá trị mặc định ban đầu
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

        // Bắt sự kiện click nút phân trang AJAX
        tableContainer.addEventListener("click", function (event) {
            const paginationLink = event.target.closest(".ajax-pagination a");
            if (!paginationLink) return;

            event.preventDefault();
            loadTableData(paginationLink.href, { preserveSelection: true }); // Giữ lại tích chọn khi sang trang khác
        });

        // Lắng nghe sự kiện check/uncheck các dòng dữ liệu để cập nhật danh sách chọn
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

        // Xác nhận xóa vật tư đơn lẻ
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
                            method: "POST", // Laravel spoofing _method=DELETE
                            body: new FormData(deleteForm),
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                "Accept": "application/json"
                            }
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            window.pendingScrollY = currentScrollY; // Giữ vị trí cuộn màn hình
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

        // Xử lý reload cứng khi popstate (Back/Forward trình duyệt) để tránh lỗi render ra chuỗi JSON thô
        window.addEventListener("popstate", function () {
            window.location.reload();
        });

        // Đợi bảng render xong thì phục hồi lại đúng vị trí cuộn chuột trước khi xóa/thay đổi trạng thái
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
