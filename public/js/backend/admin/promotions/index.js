/**
 * index.js - Quản lý trang danh sách chương trình khuyến mãi khu vực Admin
 * Các tính năng bao gồm:
 * - Hiển thị thông báo Toast SweetAlert2 khi có Flash Session thành công hoặc lỗi từ server gửi xuống.
 * - AJAX tải lại bảng dữ liệu danh sách khuyến mãi và cập nhật các con số thống kê ở đầu trang.
 * - Lọc AJAX thời gian thực (Live Search) với debounce 400ms.
 * - Tích chọn hàng loạt checkbox (đồng bộ cả desktop và mobile), hỗ trợ gọi API nhận mảng ID toàn bộ các trang để chọn tất cả.
 * - Xóa khuyến mãi đơn lẻ bằng AJAX kèm hiệu ứng trượt CSS và tự động lùi trang phân trang nếu trang hiện tại bị xóa trống.
 * - Xóa hàng loạt khuyến mãi đã chọn bằng AJAX có hộp thoại cảnh báo SweetAlert2 bo tròn hiện đại.
 */
document.addEventListener("DOMContentLoaded", function () {
    // === 1. HIỂN THỊ THÔNG BÁO FLASH MESSAGE TỪ SERVER ===
    const flashData = document.getElementById("promotion-flash-data");
    if (flashData && typeof Swal !== "undefined") {
        const successMessage = flashData.dataset.success || "";
        const errors = flashData.dataset.errors ? JSON.parse(flashData.dataset.errors) : [];

        if (successMessage) {
            Swal.fire({
                icon: "success",
                title: "Thành công!",
                text: successMessage,
                timer: 2000,
                showConfirmButton: false,
                width: "320px",
                padding: "1rem",
                customClass: {
                    popup: "rounded-xl shadow-xl border border-gray-100",
                    title: "text-base font-bold text-gray-800",
                    htmlContainer: "text-sm text-gray-500 mt-1",
                    icon: "transform scale-[0.6] -mt-3 -mb-2",
                },
            });
        } else if (errors.length > 0) {
            const errorHtml = errors
                .map((error) => `<li>${escapeHtml(error)}</li>`)
                .join("");

            Swal.fire({
                icon: "error",
                title: "Lỗi",
                html: `<ul class="text-left text-sm text-gray-600 list-disc pl-5 space-y-1">${errorHtml}</ul>`,
                width: "320px",
                padding: "1rem",
                confirmButtonText: "Đóng",
                buttonsStyling: false,
                customClass: {
                    popup: "rounded-xl shadow-xl border border-gray-100",
                    title: "text-base font-bold text-gray-800",
                    confirmButton:
                        "px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm",
                    icon: "transform scale-[0.6] -mt-3 -mb-2",
                    actions: "mt-3 w-full flex justify-center",
                },
            });
        }
    }

    // === PHẦN TỬ DOM CHÍNH ===
    const filterForm = document.getElementById("filter-form");
    const tableContainer = document.getElementById("table-container");
    const btnClearFilter = document.getElementById("btn-clear-filter");
    const bulkDeleteContainer = document.getElementById("bulk-delete-container");
    const selectedCountSpan = document.getElementById("selected-count");
    const deselectBtn = document.getElementById("bulk-deselect-btn");

    // =========================================================
    // XỬ LÝ CHỌN CHECKBOX HÀNG LOẠT (BULK ACTIONS SELECTION)
    // =========================================================
    window.selectedPromotionIds = new Set(); // Set lưu trữ ID các mã khuyến mãi được chọn

    /**
     * Cập nhật hiển thị số lượng và trạng thái ẩn hiện nút "Xóa nhiều"
     */
    function updateBulkDeleteButton() {
        const count = window.selectedPromotionIds.size;
        if (bulkDeleteContainer) {
            if (count > 0) {
                bulkDeleteContainer.classList.remove("hidden");
            } else {
                bulkDeleteContainer.classList.add("hidden");
            }
        }
        if (deselectBtn) {
            if (count > 0) {
                deselectBtn.classList.remove("hidden");
            } else {
                deselectBtn.classList.add("hidden");
            }
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
    }

    /**
     * Đồng bộ hóa lại trạng thái tích chọn checkbox các dòng trong bảng
     */
    function syncCheckboxes() {
        const allCheckboxes = document.querySelectorAll(".row-checkbox");
        let checkedCount = 0;
        allCheckboxes.forEach((cb) => {
            const hasId = window.selectedPromotionIds.has(cb.value);
            cb.checked = hasId;
            if (hasId) checkedCount++;
        });

        // Đồng bộ nút "Chọn tất cả" ở cả giao diện Desktop và Mobile
        const selectAllEl = document.getElementById("selectAll");
        const selectAllMobileEl = document.getElementById("selectAll-mobile");
        
        const isAllChecked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
        if (selectAllEl) selectAllEl.checked = isAllChecked;
        if (selectAllMobileEl) selectAllMobileEl.checked = isAllChecked;
    }

    // Đăng ký sự kiện thay đổi check/uncheck (sử dụng Event Delegation)
    document.addEventListener("change", function (e) {
        // 1. Tích chọn nút Check tất cả
        if (e.target && e.target.classList.contains("js-select-all")) {
            const checked = e.target.checked;
            if (checked) {
                // Gọi API lấy toàn bộ danh sách ID thỏa mãn bộ lọc hiện tại trên server
                let url = new URL(filterForm.action);
                let params = new URLSearchParams(new FormData(filterForm));
                params.set("fetch_all_ids", "1");
                url.search = params.toString();

                fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.ids && Array.isArray(data.ids)) {
                            data.ids.forEach((id) => window.selectedPromotionIds.add(String(id)));
                            syncCheckboxes();
                            updateBulkDeleteButton();
                        }
                    })
                    .catch((err) => console.error("Lỗi lấy danh sách ID khuyến mãi:", err));
            } else {
                // Bỏ tích chọn toàn bộ
                window.selectedPromotionIds.clear();
                syncCheckboxes();
                updateBulkDeleteButton();
            }
        }

        // 2. Tích chọn checkbox của một hàng khuyến mãi đơn lẻ
        if (e.target && e.target.classList.contains("row-checkbox")) {
            const val = String(e.target.value);
            if (e.target.checked) {
                window.selectedPromotionIds.add(val);
            } else {
                window.selectedPromotionIds.delete(val);
            }
            syncCheckboxes();
            updateBulkDeleteButton();
        }
    });

    if (deselectBtn) {
        deselectBtn.addEventListener("click", function () {
            window.selectedPromotionIds.clear();
            syncCheckboxes();
            updateBulkDeleteButton();
        });
    }

    // === TẢI DỮ LIỆU BẢNG KHUYẾN MÃI BẰNG AJAX ===
    function fetchPromotions(urlStr = null) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            url.search = searchParams.toString();
        }

        tableContainer.style.opacity = "0.5";
        tableContainer.style.pointerEvents = "none";

        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.html) {
                    tableContainer.innerHTML = data.html; // Đè bảng mới
                }

                // Cập nhật lại các con số thống kê ở đầu trang
                if (data.stats) {
                    const totalEl = document.getElementById("stat-total");
                    const activeEl = document.getElementById("stat-active");
                    const expiredEl = document.getElementById("stat-expired");
                    if (totalEl) totalEl.textContent = data.stats.total;
                    if (activeEl) activeEl.textContent = data.stats.active;
                    if (expiredEl) expiredEl.textContent = data.stats.expired;
                }

                // Tự động lùi trang phân trang nếu trang hiện tại bị trống sau khi load lại
                const checkboxes = tableContainer.querySelectorAll(".row-checkbox");
                if (checkboxes.length === 0) {
                    const pageParam = url.searchParams.get("page");
                    if (pageParam && parseInt(pageParam) > 1) {
                        url.searchParams.set("page", parseInt(pageParam) - 1);
                        fetchPromotions(url.toString());
                        return;
                    }
                }

                if (data.total !== undefined) {
                    const totalInput = document.getElementById("total-promotions-count");
                    if (totalInput) totalInput.value = data.total;
                }
                
                // Ẩn hiện nút xóa lọc
                if (btnClearFilter) {
                    const hasFilters = [
                        ...new URLSearchParams(url.search),
                    ].some(
                        ([key, val]) =>
                            (key === "search" && val !== "") ||
                            (key !== "search" && key !== "page" && val !== "all" && val !== "newest"),
                    );
                    btnClearFilter.style.display = hasFilters ? "flex" : "none";
                }
                syncCheckboxes();
                updateBulkDeleteButton();
            })
            .catch((error) => console.error("Lỗi khi tải dữ liệu khuyến mãi:", error))
            .finally(() => {
                tableContainer.style.opacity = "1";
                tableContainer.style.pointerEvents = "auto";
            });
    }

    // Tự động tải lại bảng khi thay đổi bộ lọc dropdown
    filterForm.querySelectorAll("select").forEach((select) => {
        select.addEventListener("change", () => fetchPromotions());
    });

    // Debounce tìm kiếm tự động sau khi dừng gõ phím 400ms
    let timeout = null;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchPromotions(), 400);
        });
    }

    // Đặt lại form lọc về trạng thái ban đầu khi click "Xóa lọc"
    if (btnClearFilter) {
        btnClearFilter.addEventListener("click", function (e) {
            e.preventDefault();
            filterForm.reset();
            filterForm.querySelectorAll("select").forEach((s) => {
                if (s.name === "sort") s.value = "newest";
                else s.value = "all";
            });
            if (searchInput) searchInput.value = "";
            fetchPromotions();
        });
    }

    // Bắt sự kiện click link phân trang trong bảng
    tableContainer.addEventListener("click", function (e) {
        const pageLink = e.target.closest(".pagination-container a");
        if (pageLink) {
            e.preventDefault();
            fetchPromotions(pageLink.href);
        }
    });

    // === CẤU HÌNH POPUP SWEETALERT2 DÙNG CHUNG ===
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

    // Hiển thị hộp thoại xác nhận trước khi xóa nhiều khuyến mãi
    window.submitBulkDelete = function () {
        const count = window.selectedPromotionIds.size;
        if (count === 0) return;

        if (typeof Swal !== "undefined") {
            Swal.fire({
                ...swalConfig,
                title: "Xóa nhiều khuyến mãi?",
                text: `Bạn chuẩn bị xóa ${count} mã khuyến mãi đã chọn. Hành động này không thể hoàn tác.`,
            }).then((result) => {
                if (result.isConfirmed) {
                    executeBulkDelete();
                }
            });
        } else {
            if (confirm(`Xóa ${count} khuyến mãi đã chọn?`)) {
                executeBulkDelete();
            }
        }
    };

    /**
     * Gửi request POST AJAX thực thi xóa hàng loạt mã khuyến mãi
     */
    function executeBulkDelete() {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="_token"]')?.value ||
            "";

        const bulkUrl = filterForm.action.replace("/promotions", "/promotions/bulk-delete");

        tableContainer.style.opacity = "0.5";
        tableContainer.style.pointerEvents = "none";

        fetch(bulkUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                promotion_ids: Array.from(window.selectedPromotionIds),
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    window.selectedPromotionIds.clear();
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Thành công!",
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                            width: "320px",
                            padding: "1rem",
                            customClass: {
                                popup: "rounded-xl shadow-xl border border-gray-100",
                                title: "text-base font-bold text-gray-800",
                                icon: "transform scale-[0.6] -mt-3 -mb-2",
                            },
                        });
                    }
                    fetchPromotions(); // Tải lại bảng dữ liệu
                } else {
                    throw new Error(data.message || "Xóa thất bại");
                }
            })
            .catch((err) => {
                console.error(err);
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "Lỗi",
                        text: err.message || "Có lỗi xảy ra khi xóa.",
                        width: "320px",
                        padding: "1rem",
                        confirmButtonText: "Đóng",
                        buttonsStyling: false,
                        customClass: {
                            popup: "rounded-xl shadow-xl border border-gray-100",
                            title: "text-base font-bold text-gray-800",
                            confirmButton:
                                "px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm",
                            icon: "transform scale-[0.6] -mt-3 -mb-2",
                            actions: "mt-3 w-full flex justify-center",
                        },
                    });
                }
            })
            .finally(() => {
                tableContainer.style.opacity = "1";
                tableContainer.style.pointerEvents = "auto";
            });
    }
});

/**
 * Hàm dọn dẹp chuỗi HTML tránh các lỗ hổng chèn mã độc (XSS)
 */
function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

/**
 * Hiển thị hộp thoại xác nhận xóa một chương trình khuyến mãi đơn lẻ
 * @param {number} id - ID khóa chính của khuyến mãi
 * @param {string} code - Mã code khuyến mãi (ví dụ: KMABCD)
 */
function deletePromotion(id, code) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "Xóa khuyến mãi?",
            html: `Bạn có chắc muốn xóa mã <strong class="font-mono">${code}</strong>?<br><span class="text-xs text-gray-400">Hành động này không thể hoàn tác.</span>`,
            icon: "warning",
            width: "320px",
            padding: "1rem",
            showCancelButton: true,
            confirmButtonText: "Xóa ngay",
            cancelButtonText: "Hủy",
            reverseButtons: true,
            buttonsStyling: false,
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
        }).then((result) => {
            if (result.isConfirmed) {
                doDelete(id);
            }
        });
    } else {
        if (confirm(`Xóa mã "${code}"?`)) {
            doDelete(id);
        }
    }
}

/**
 * Gửi request DELETE AJAX xóa khuyến mãi đơn lẻ và làm mờ dòng biến mất
 */
function doDelete(id) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ||
        document.querySelector('input[name="_token"]')?.value ||
        "";

    const deleteUrl = window.location.origin + "/admin/promotions/" + id;

    fetch(deleteUrl, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "Content-Type": "application/json",
        },
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                window.selectedPromotionIds.delete(String(id));
                const row = document.getElementById("promo-row-" + id);
                const card = document.getElementById("promo-card-" + id);
                
                // Thực thi hiệu ứng CSS trượt biến mất mượt mà trước khi xóa khỏi DOM
                if (row) {
                    row.style.transition = "all 0.3s ease";
                    row.style.opacity = "0";
                    row.style.transform = "translateX(20px)";
                    setTimeout(() => row.remove(), 300);
                }
                if (card) {
                    card.style.transition = "all 0.3s ease";
                    card.style.opacity = "0";
                    card.style.transform = "translateX(20px)";
                    setTimeout(() => card.remove(), 300);
                }

                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "success",
                        title: "Đã xóa!",
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false,
                        width: "320px",
                        padding: "1rem",
                        customClass: {
                            popup: "rounded-xl shadow-xl border border-gray-100",
                            title: "text-base font-bold text-gray-800",
                            icon: "transform scale-[0.6] -mt-3 -mb-2",
                        },
                    });
                }
                
                // Gọi AJAX cập nhật lại thống kê số lượng và dữ liệu bảng sau khi hiệu ứng kết thúc
                setTimeout(() => {
                    const tableContainer = document.getElementById("table-container");
                    if (tableContainer) {
                        const filterForm = document.getElementById("filter-form");
                        const url = new URL(filterForm.action);
                        const formData = new FormData(filterForm);
                        url.search = new URLSearchParams(formData).toString();
                        
                        // Lùi về trang phân trang trước nếu xóa dòng cuối cùng
                        const checkboxes = tableContainer.querySelectorAll(".row-checkbox");
                        if (checkboxes.length === 0) {
                            const pageParam = url.searchParams.get("page");
                            if (pageParam && parseInt(pageParam) > 1) {
                                url.searchParams.set("page", parseInt(pageParam) - 1);
                            }
                        }

                        fetch(url.toString(), {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "application/json",
                            },
                        })
                            .then((response) => response.json())
                            .then((resData) => {
                                if (resData.html) {
                                    tableContainer.innerHTML = resData.html;
                                }
                                if (resData.stats) {
                                    const totalEl = document.getElementById("stat-total");
                                    const activeEl = document.getElementById("stat-active");
                                    const expiredEl = document.getElementById("stat-expired");
                                    if (totalEl) totalEl.textContent = resData.stats.total;
                                    if (activeEl) activeEl.textContent = resData.stats.active;
                                    if (expiredEl) expiredEl.textContent = resData.stats.expired;
                                }
                                syncCheckboxes();
                                updateBulkDeleteButton();
                            });
                    }
                }, 310);
            } else {
                throw new Error(data.message || "Xóa thất bại. Vui lòng thử lại.");
            }
        })
        .catch((err) => {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Lỗi",
                    text: err.message || "Có lỗi xảy ra khi xóa.",
                    width: "320px",
                    padding: "1rem",
                    confirmButtonText: "Đóng",
                    buttonsStyling: false,
                    customClass: {
                        popup: "rounded-xl shadow-xl border border-gray-100",
                        title: "text-base font-bold text-gray-800",
                        confirmButton:
                            "px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm",
                        icon: "transform scale-[0.6] -mt-3 -mb-2",
                        actions: "mt-3 w-full flex justify-center",
                    },
                });
            } else {
                alert(err.message || "Có lỗi xảy ra khi xóa.");
            }
        });
}

// Lắng nghe sự kiện click nút xóa khuyến mãi ngoài bảng (sử dụng Event Delegation)
document.addEventListener("DOMContentLoaded", function () {
    const tableContainer = document.getElementById("table-container");
    if (tableContainer) {
        tableContainer.addEventListener("click", function (e) {
            const deleteBtn = e.target.closest(".js-delete-promotion");
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                const code = deleteBtn.dataset.code;
                deletePromotion(id, code);
            }
        });
    }

    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", function (e) {
            e.preventDefault();
            if (typeof window.submitBulkDelete === "function") {
                window.submitBulkDelete();
            }
        });
    }
});
