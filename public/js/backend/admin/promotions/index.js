/**
 * index.js - Quản lý trang danh sách chương trình khuyến mãi khu vực Admin
 * Lọc/tìm kiếm/phân trang/xóa/xóa nhiều đều là form thường (tải lại trang), không còn AJAX.
 * Xóa từng dòng dùng onsubmit="return confirm(...)" ngay trong Blade (partials/table.blade.php).
 * JS ở đây chỉ còn: hiển thị Toast SweetAlert2 cho flash message, và quản lý tích chọn nhiều dòng
 * TRONG TRANG HIỆN TẠI để xóa hàng loạt.
 */
document.addEventListener("DOMContentLoaded", function () {
    // === HIỂN THỊ THÔNG BÁO FLASH MESSAGE TỪ SERVER ===
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

    // === CHỌN NHIỀU (chỉ trong trang hiện tại) & XÓA HÀNG LOẠT ===
    const bulkDeleteContainer = document.getElementById("bulk-delete-container");
    const selectedCountSpan = document.getElementById("selected-count");
    const deselectBtn = document.getElementById("bulk-deselect-btn");
    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    function getCheckedIds() {
        return Array.from(document.querySelectorAll(".row-checkbox:checked")).map((cb) => cb.value);
    }

    function updateBulkDeleteButton() {
        const count = getCheckedIds().length;
        if (bulkDeleteContainer) bulkDeleteContainer.classList.toggle("hidden", count === 0);
        if (deselectBtn) deselectBtn.classList.toggle("hidden", count === 0);
        if (selectedCountSpan) selectedCountSpan.textContent = count;
    }

    function syncSelectAllCheckboxes() {
        const allCheckboxes = document.querySelectorAll(".row-checkbox");
        const isAllChecked = allCheckboxes.length > 0 && document.querySelectorAll(".row-checkbox:checked").length === allCheckboxes.length;
        const selectAllEl = document.getElementById("selectAll");
        const selectAllMobileEl = document.getElementById("selectAll-mobile");
        if (selectAllEl) selectAllEl.checked = isAllChecked;
        if (selectAllMobileEl) selectAllMobileEl.checked = isAllChecked;
    }

    document.addEventListener("change", function (e) {
        // 1. Tích chọn nút Check tất cả — chỉ áp dụng cho các dòng đang hiển thị ở trang này
        if (e.target && e.target.classList.contains("js-select-all")) {
            const checked = e.target.checked;
            document.querySelectorAll(".js-select-all").forEach((el) => (el.checked = checked));
            document.querySelectorAll(".row-checkbox").forEach((cb) => (cb.checked = checked));
            updateBulkDeleteButton();
        }
        // 2. Tích chọn checkbox của một hàng khuyến mãi đơn lẻ
        if (e.target && e.target.classList.contains("row-checkbox")) {
            syncSelectAllCheckboxes();
            updateBulkDeleteButton();
        }
    });

    if (deselectBtn) {
        deselectBtn.addEventListener("click", function () {
            document.querySelectorAll(".js-select-all, .row-checkbox").forEach((el) => (el.checked = false));
            updateBulkDeleteButton();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", function () {
            const ids = getCheckedIds();
            if (ids.length === 0) return;
            if (!confirm(`Xóa ${ids.length} mã khuyến mãi đã chọn? Hành động này không thể hoàn tác.`)) return;

            bulkDeleteForm.querySelectorAll('input[name="promotion_ids[]"]').forEach((el) => el.remove());
            ids.forEach((id) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "promotion_ids[]";
                input.value = id;
                bulkDeleteForm.appendChild(input);
            });
            bulkDeleteForm.submit();
        });
    }
});

/**
 * Hàm dọn dẹp chuỗi HTML tránh các lỗ hổng chèn mã độc (XSS) — dùng khi hiển thị flash message lỗi
 */
function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}
