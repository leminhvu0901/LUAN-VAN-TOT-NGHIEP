/**
 * Xử lý trang khuyến mãi: flash message, lọc AJAX, phân trang, checkbox và xoá.
 */
document.addEventListener("DOMContentLoaded", function () {
    // Hiển thị thông báo flash nếu Blade đã đẩy dữ liệu vào DOM
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

    const filterForm = document.getElementById("filter-form");
    const tableContainer = document.getElementById("table-container");
    const btnClearFilter = document.getElementById("btn-clear-filter");
    const bulkDeleteContainer = document.getElementById("bulk-delete-container", ); //nút xóa nhiều
    const selectedCountSpan = document.getElementById("selected-count"); // hien thi so luong da chon

    // =====================
    // Quản lý chọn nhiều dòng trong bảng
    // =====================
    window.selectedPromotionIds = new Set();

    // CAP NHAT TRANG THAI NUI XOA
    function updateBulkDeleteButton() {
        const count = window.selectedPromotionIds.size;
        if (bulkDeleteContainer) {
            bulkDeleteContainer.style.display = count > 0 ? "block" : "none";
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
    }

    // Đồng bộ lại checkbox sau khi bảng được render lại bằng AJAX
    function syncCheckboxes() {
        // Đồng bộ trạng thái checkbox sau khi bảng được render lại bằng AJAX
        const allCheckboxes = document.querySelectorAll(".row-checkbox");
        allCheckboxes.forEach((cb) => {
            cb.checked = window.selectedPromotionIds.has(cb.value);
        });
        const selectAllEl = document.getElementById("selectAll");
        if (selectAllEl && allCheckboxes.length > 0) {
            selectAllEl.checked = document.querySelectorAll(".row-checkbox:checked").length === allCheckboxes.length;
        }
    }

    //LẮNG NGHE SỰ KIỆN THAY ĐỔI
    document.addEventListener("change", function (e) {
        // Chọn/bỏ chọn tất cả
        if (e.target && e.target.id === "selectAll") {
            const checked = e.target.checked;
            document.querySelectorAll(".row-checkbox").forEach((cb) => {
                cb.checked = checked;
                if (checked) window.selectedPromotionIds.add(cb.value);
                else window.selectedPromotionIds.delete(cb.value);
            });
            updateBulkDeleteButton();
        }
        if (e.target && e.target.classList.contains("row-checkbox")) {
            if (e.target.checked)
                window.selectedPromotionIds.add(e.target.value);
            else window.selectedPromotionIds.delete(e.target.value);
            const allCheckboxes = document.querySelectorAll(".row-checkbox");
            const selectAllEl = document.getElementById("selectAll");
            if (selectAllEl) {
                selectAllEl.checked = document.querySelectorAll(".row-checkbox:checked").length === allCheckboxes.length;
            }
            updateBulkDeleteButton();
        }
    });

    //Lấy dữ liệu khuyến mãi mới từ server và cập nhật lại bảng mà không cần tải lại cả trang.
    function fetchPromotions(urlStr = null) {
        let url;
        if (urlStr) {
            // Khi bấm phân trang, dùng luôn URL đã có query string
            url = new URL(urlStr);
        } else {
            // Khi lọc bằng form, chuyển dữ liệu form thành query string
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            url.search = searchParams.toString();
        }


        window.history.pushState({}, "", url);//doi url
        tableContainer.style.opacity = "0.5";//lam mo
        tableContainer.style.pointerEvents = "none"; //khong cho bam

        // Gọi endpoint hiện tại và yêu cầu trả về JSON
        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.html) {
                    tableContainer.innerHTML = data.html;
                }
                if (data.total !== undefined) {
                    const totalInput = document.getElementById(
                        "total-promotions-count",
                    );
                    if (totalInput) totalInput.value = data.total;
                }
                if (btnClearFilter) {
                    // Ẩn/nút xoá lọc tuỳ theo form hiện đang có điều kiện lọc hay không
                    const hasFilters = [
                        ...new URLSearchParams(url.search),
                    ].some(
                        ([key, val]) =>
                            (key === "search" && val !== "") ||(key !== "search" &&  key !== "page" && val !== "all" &&val !== "newest"),
                        );
                    btnClearFilter.style.display = hasFilters ? "inline-block": "none";
                }
                syncCheckboxes();
                updateBulkDeleteButton();
            })
            .catch((error) =>
                console.error("Lỗi khi tải dữ liệu khuyến mãi:", error),
            )
            .finally(() => {
                tableContainer.style.opacity = "1";
                tableContainer.style.pointerEvents = "auto";
            });
    }

    // Tự động lọc lại khi đổi select
    filterForm.querySelectorAll("select").forEach((select) => {
        select.addEventListener("change", () => fetchPromotions());
    });

    let timeout = null;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        // Debounce để không gửi request liên tục khi đang gõ
        searchInput.addEventListener("input", function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchPromotions(), 400);
        });
    }

    if (btnClearFilter) {
        // Reset form về trạng thái mặc định rồi tải lại dữ liệu
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

    // Bắt click vào link phân trang trong vùng bảng để chuyển sang AJAX
    tableContainer.addEventListener("click", function (e) {
        const pageLink = e.target.closest(".pagination-container a");
        if (pageLink) {
            e.preventDefault();
            fetchPromotions(pageLink.href);
        }
    });

    // =====================
    // Xoá hàng loạt
    // =====================
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

    // Xác nhận trước khi gửi form xoá nhiều khuyến mãi
    window.submitBulkDelete = function () {
        const count = window.selectedPromotionIds.size;
        if (count === 0) return;

        // Dùng SweetAlert nếu có, fallback sang confirm mặc định của trình duyệt
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

    // Tạo danh sách ID đã chọn và submit form xoá hàng loạt lên server
    function executeBulkDelete() {
        const form = document.getElementById("bulk-delete-form");
        // Xoá các input động cũ trước khi thêm danh sách ID mới
        form.querySelectorAll(
            'input:not([name="_token"]):not([name="total_promotions_count"])',
        ).forEach((el) => el.remove());

        // Đưa các ID đã chọn vào form để submit lên server
        window.selectedPromotionIds.forEach((id) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "promotion_ids[]";
            input.value = id;
            form.appendChild(input);
        });

        form.submit();
    }
});

// Escape chuỗi để tránh chèn HTML khi hiển thị lỗi validate
function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

/**
 * Xoá một khuyến mãi đơn lẻ
 */
// Hiển thị hộp thoại xác nhận xoá một khuyến mãi
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

// Gửi request DELETE và cập nhật lại dòng trên giao diện sau khi xoá
function doDelete(id) {
    // Lấy CSRF token từ meta hoặc input ẩn để gửi request DELETE hợp lệ
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ||
        document.querySelector('input[name="_token"]')?.value ||
        "";

    // URL xoá theo route backend hiện tại
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
                // Xoá dòng trên UI trước để phản hồi nhanh cho người dùng
                const row = document.getElementById("promo-row-" + id);
                if (row) {
                    row.style.transition = "all 0.3s ease";
                    row.style.opacity = "0";
                    row.style.transform = "translateX(20px)";
                    setTimeout(() => row.remove(), 300);
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
            } else {
                // Hiển thị lỗi trả về từ server nếu có
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "Thất bại",
                        text: data.message || "Xóa thất b ại. Vui lòng thử lại.",
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
                    alert("Xóa thất bại. Vui lòng thử lại.");
                }
            }
        })
        .catch(() => {
            // Bắt lỗi mạng hoặc lỗi không parse được JSON
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Lỗi",
                    text: "Có lỗi xảy ra khi xóa. Vui lòng thử lại.",
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
                alert("Có lỗi xảy ra khi xóa. Vui lòng thử lại.");
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('table-container');
    if (tableContainer) {
        tableContainer.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.js-delete-promotion');
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                const code = deleteBtn.dataset.code;
                if (typeof deletePromotion === 'function') {
                    deletePromotion(id, code);
                }
            }
        });
    }

    const bulkDeleteBtn = document.querySelector('.js-bulk-delete');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof window.submitBulkDelete === 'function') {
                window.submitBulkDelete();
            }
        });
    }
});
