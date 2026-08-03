/**
 * index.js - Quản lý trang danh sách đơn hàng khu vực Admin
 * Các tính năng bao gồm:
 * - Tìm kiếm thời gian thực (Live Search) và bộ lọc nâng cao kết hợp Flatpickr chọn khoảng ngày.
 * - AJAX load lại danh sách bảng đơn hàng và cập nhật số liệu thống kê.
 * - Xóa đơn hàng đơn lẻ bằng AJAX và tự động tính toán quay lại trang trước nếu trang hiện tại hết đơn.
 * - Chọn hàng loạt checkbox (bao gồm cơ chế Select All kết hợp Exclude đặc biệt cho các trang).
 * - Tự động thiết kế thay thế select trạng thái đơn bằng Custom Dropdown đẹp mắt, định vị thông minh chống tràn màn hình.
 * - Tự động hiển thị hộp thoại cảnh báo lý do hủy đơn (SweetAlert2 nhập Textarea) được bo góc đồng nhất.
 * - Phục hồi lại vị trí cuộn chuột sau khi tải AJAX danh sách mới.
 */

let searchTimeout = null;
let form;
let tableContainer;
let loader;
let loadAbortController;

/**
 * Reset lại toàn bộ bộ nhớ chọn nhiều đơn hàng về trạng thái ban đầu
 */
function resetOrderSelection() {
    if (!window.selectedOrderIds) return;
    window.selectedOrderIds.clear();
    window.excludedOrderIds.clear();
    window.isGlobalSelectAll = false;
    updateBulkDeleteButton();
}

/**
 * Gửi request AJAX tải danh sách đơn hàng mới
 * @param {string|null} url - Đường dẫn cần tải, mặc định lấy theo bộ lọc form hiện tại
 */
function loadTableData(url = null) {
    if (!url) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        url = form.action + "?" + params.toString();
    }

    // Cập nhật lại thanh địa chỉ URL trình duyệt
    window.history.pushState({}, "", url);

    loader.classList.remove("hidden");
    loader.classList.add("flex");

    // Hủy bỏ request AJAX đang chạy dở để tránh xung đột dữ liệu trả về
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
            // Cập nhật HTML bảng danh sách
            if (data.table_html) {
                const tableWrapper = document.getElementById("orders-table-wrapper");
                if (tableWrapper) {
                    tableWrapper.innerHTML = data.table_html;
                } else {
                    tableContainer.innerHTML = data.table_html;
                }
                
                attachPaginationListeners();
                document.dispatchEvent(new Event("tableDataLoaded")); // Phát tín hiệu đã nạp bảng xong
            }

            // Cập nhật HTML các thẻ thống kê tổng quan ở trên
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

/**
 * Trì hoãn gửi request tìm kiếm (Debounce 500ms) khi người dùng gõ từ khóa
 */
function handleLiveSearch() {
    resetOrderSelection();
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTableData(), 500);
}

function attachPaginationListeners() {
    // Không dùng nữa vì đã tối ưu chuyển sang Event Delegation ở dưới
}

/**
 * Cập nhật số lượng hiển thị và trạng thái ẩn hiện của nút "Xóa nhiều"
 */
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

/**
 * Hiển thị cảnh báo xác nhận trước khi xóa nhiều đơn hàng
 */
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

/**
 * Đóng gói và submit form thực thi xóa hàng loạt đơn hàng
 */
function executeBulkDelete(isSelectAll) {
    const bulkDeleteForm = document.getElementById("bulk-delete-form");

    // Xóa các input ẩn cũ
    bulkDeleteForm
        .querySelectorAll('input:not([name="_token"])')
        .forEach((el) => el.remove());

    if (isSelectAll) {
        const inputAll = document.createElement("input");
        inputAll.type = "hidden";
        inputAll.name = "delete_all_pages";
        inputAll.value = "1";
        bulkDeleteForm.appendChild(inputAll);

        // Chuyển toàn bộ tham số bộ lọc hiện hành thành các input ẩn gửi kèm form
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
        // Thêm các ID được bỏ tích chọn khỏi danh sách chọn tất cả
        window.excludedOrderIds.forEach((id) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "excluded_order_ids[]";
            input.value = id;
            bulkDeleteForm.appendChild(input);
        });
    } else {
        // Thêm các ID được tích chọn
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

/**
 * Hiển thị cảnh báo confirm trước khi xóa một đơn hàng đơn lẻ
 */
function deleteOrderAjax(btn) {
    window.AdminAlert.confirm(
        "Đơn hàng này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        function () { executeDeleteOrderAjax(btn); },
        "Xác nhận xóa đơn hàng?"
    );
}

/**
 * Thực thi gửi request DELETE AJAX xóa một đơn hàng
 */
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

                // Xóa dòng đơn hàng khỏi view ở cả desktop/mobile
                document.querySelectorAll(`[data-order-id="${orderId}"]`).forEach(el => el.remove());

                const remainingCards = document.querySelectorAll('.mobile-card').length;
                const remainingRows = document.querySelectorAll('tbody tr[data-order-id]').length;

                // Nếu trang hiện tại bị xóa sạch đơn hàng, tự động quay lại trang phân trang trước
                if (remainingCards === 0 && remainingRows === 0) {
                    const urlParams = new URLSearchParams(window.location.search);
                    let page = parseInt(urlParams.get('page')) || 1;
                    if (page > 1) {
                        urlParams.set('page', page - 1);
                        window.history.replaceState({}, "", `${window.location.pathname}?${urlParams.toString()}`);
                    }
                }

                resetOrderSelection();
                window.pendingScrollY = currentScrollY; // Giữ vị trí cuộn màn hình
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

/**
 * Khởi tạo ô tìm kiếm, Flatpickr và lắng nghe submit form lọc
 */
function initSearchAndFilters() {
    form = document.getElementById("search-form");
    tableContainer = document.getElementById("table-container");
    loader = document.getElementById("table-loader");

    document.getElementById("search-input").addEventListener("input", handleLiveSearch);

    // Cấu hình Flatpickr cho khoảng ngày bắt đầu và kết thúc đặt hàng
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

/**
 * Khởi tạo bắt các sự kiện thay đổi dữ liệu bảng (Check All, Chọn dòng, Đổi trạng thái)
 */
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
    
    // Phân trang bằng Event Delegation để không bị mất sự kiện sau khi cập nhật AJAX
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
        // 1. Checkbox chọn tất cả
        if (e.target.classList.contains("js-select-all")) {
            const isChecked = e.target.checked;
            window.isGlobalSelectAll = isChecked;

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

        // 2. Checkbox chọn từng dòng đơn hàng
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

        // 3. Xử lý đổi trạng thái trực tiếp trong bảng danh sách
        if (e.target.classList.contains("js-order-status-select")) {
            if (e.target.value !== "cancelled") {
                e.target.form.submit();
                return;
            }
            const select = e.target;
            
            // Nếu đổi trạng thái sang HỦY ĐƠN: Hiển thị Prompt SweetAlert2 yêu cầu lý do hủy
            Swal.fire({
                title: "Lý do hủy đơn",
                input: "textarea",
                inputPlaceholder: "Nhập lý do hủy (ít nhất 5 ký tự)",
                showCancelButton: true,
                confirmButtonText: "Hủy đơn",
                cancelButtonText: "Đóng",
                buttonsStyling: false,
                width: "360px",
                padding: "1.25rem",
                returnFocus: false,
                customClass: {
                    popup: "rounded-xl shadow-xl border border-gray-100 p-4",
                    title: "text-base font-bold text-gray-800 mb-1",
                    input: "w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:border-emerald-500 transition-colors mb-3 shadow-none resize-none h-20 focus:ring-0 focus:outline-none !outline-none font-normal",
                    validationMessage: "text-xs text-red-500 bg-red-50 p-2.5 rounded-lg mb-3 border border-red-100 text-center w-full shadow-none mt-0 font-medium",
                    actions: "w-full flex gap-2 mt-1",
                    confirmButton: "flex-1 px-4 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer",
                    cancelButton: "flex-1 px-4 py-1.5 bg-white text-gray-700 font-semibold rounded-lg text-xs border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer",
                },
                inputValidator: (value) => !value || value.trim().length < 5 ? "Vui lòng nhập ít nhất 5 ký tự." : undefined,
            }).then((result) => {
                if (!result.isConfirmed) {
                    select.value = select.dataset.currentStatus; // Rollback trạng thái cũ nếu hủy bỏ
                    return;
                }
                // Tạo input lý do ẩn để gửi kèm form
                const reason = document.createElement("input");
                reason.type = "hidden";
                reason.name = "cancel_reason";
                reason.value = result.value.trim();
                select.form.appendChild(reason);
                select.form.submit(); // Submit form chính thức thay đổi trạng thái và ghi nhận lý do hủy đơn
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

    // Đồng bộ lại checkbox sau khi bảng render xong
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
        initOrderStatusCustomDropdowns(); // Khởi tạo lại Custom Dropdown cho trạng thái
    });

    // Theo dõi thay đổi cấu trúc bảng để tự động cập nhật số lượng nút chọn hàng loạt
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === "childList") {
                updateBulkDeleteButton();
            }
        });
    });
    observer.observe(tableContainer, { childList: true, subtree: true });
}

let activeMenu = null;
let activeBtn = null;

/**
 * Đóng Custom Dropdown đang mở
 */
function closeActiveMenu() {
    if (activeMenu) {
        activeMenu.remove();
        activeMenu = null;
    }
    activeBtn = null;
}

/**
 * Tính toán tọa độ vị trí thả xuống để định vị Custom Dropdown thông minh (chống tràn lề)
 */
function positionMenu(btn, menu) {
    if (!btn || !menu) return;
    const rect = btn.getBoundingClientRect();
    const menuHeight = menu.offsetHeight || 180;
    const menuWidth = menu.offsetWidth;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    let top, left;

    left = rect.left;
    if (left + menuWidth > window.innerWidth - 8) {
        left = window.innerWidth - menuWidth - 8;
    }
    if (left < 8) left = 8;

    if (spaceBelow < menuHeight && spaceAbove > spaceBelow) {
        top = rect.top - menuHeight - 4; // Mở ngược lên trên nếu phía dưới không đủ chỗ hiển thị
    } else {
        top = rect.bottom + 4; // Mở xuôi xuống dưới bình thường
    }

    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
}

/**
 * Tự động tìm các thẻ select trạng thái đơn hàng để bọc thành Custom Dropdown giao diện Material Design
 */
function initOrderStatusCustomDropdowns() {
    const selects = document.querySelectorAll('.order-status-select:not(.custom-dropdown-initialized)');
    selects.forEach(select => {
        select.classList.add('custom-dropdown-initialized');
        select.classList.add('sr-only'); // Ẩn select gốc của trình duyệt

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-order-status-dropdown w-full lg:w-auto relative inline-block';

        const btn = document.createElement('button');
        btn.type = 'button';
        let btnClasses = select.className.replace('order-status-select', '').replace('js-order-status-select', '').replace('sr-only', '').replace('custom-dropdown-initialized', '').trim();
        btn.className = btnClasses + ' js-custom-dropdown-btn flex items-center justify-between gap-1 w-full text-left';
        
        btn.selectEl = select;

        const selectedOption = select.options[select.selectedIndex];
        btn.innerHTML = `<span class="status-text">${selectedOption.text}</span><span class="material-symbols-outlined text-[16px]">expand_more</span>`;

        wrapper.appendChild(btn);
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
    });
}

// 1. Đăng ký Click delegation cho các sự kiện mở menu và chọn trạng thái
document.addEventListener('click', function (e) {
    const clickedItem = e.target.closest('.status-option-item');
    if (clickedItem) {
        e.stopPropagation();
        if (clickedItem.classList.contains('disabled')) return;
        
        const value = clickedItem.dataset.value;
        if (activeBtn && activeBtn.selectEl) {
            const select = activeBtn.selectEl;
            select.value = value;
            activeBtn.querySelector('.status-text').textContent = clickedItem.textContent;
            closeActiveMenu();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return;
    }

    const btn = e.target.closest('.js-custom-dropdown-btn');
    if (btn) {
        e.stopPropagation();
        if (activeBtn === btn) {
            closeActiveMenu();
            return;
        }
        closeActiveMenu();

        activeBtn = btn;
        const select = btn.selectEl;

        // Tạo menu tùy chọn động gắn trực tiếp vào cuối thẻ body (Portal Menu) để không bị cắt bởi overflow của bảng
        const menu = document.createElement('div');
        menu.className = 'status-options-menu portal-menu fixed py-1 shadow-xl text-left';
        
        if (document.querySelector('.staff-orders-page')) {
            menu.classList.add('staff-status-options-menu');
        } else if (document.querySelector('.admin-orders-page')) {
            menu.classList.add('admin-status-options-menu');
        }

        menu.style.zIndex = '9999';
        menu.style.maxHeight = '260px';
        menu.style.overflowY = 'auto';

        const rect = btn.getBoundingClientRect();
        let width = rect.width;
        if (width < 190) width = 190;
        if (window.innerWidth < 640) {
            const maxWidth = window.innerWidth - 24;
            if (width > maxWidth) width = maxWidth;
        }
        menu.style.width = width + 'px';

        const currentStatus = select.dataset.currentStatus || select.value;

        Array.from(select.options).forEach(opt => {
            const item = document.createElement('div');
            item.dataset.value = opt.value;
            item.textContent = opt.text;

            let specificOptClass = 'opt-' + opt.value;
            const isSelected = opt.value === currentStatus;
            
            item.className = `status-option-item ${specificOptClass}`;
            if (isSelected) {
                item.className += ' active';
            }

            if (opt.disabled) {
                item.className += ' disabled';
            }
            
            menu.appendChild(item);
        });

        document.body.appendChild(menu);
        activeMenu = menu;

        positionMenu(btn, menu);
        return;
    }

    closeActiveMenu();
});

// 2. Lắng nghe các sự kiện cuộn trang, đổi kích thước, bấm nút Esc để đóng menu đang mở
window.addEventListener('scroll', closeActiveMenu, { capture: true, passive: true });
window.addEventListener('resize', closeActiveMenu, { passive: true });

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeActiveMenu();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('js-order-status-select')) {
        const select = e.target;
        const wrapper = select.parentNode;
        if (wrapper) {
            const btn = wrapper.querySelector('.js-custom-dropdown-btn');
            if (btn) {
                btn.querySelector('.status-text').textContent = select.options[select.selectedIndex].text;
            }
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    initSearchAndFilters();
    initTableEvents();
    initOrderStatusCustomDropdowns();
});

// Xử lý nạp popstate (Reload cứng trang khi bấm nút Quay lại/Tiến để tránh lỗi JSON thô)
window.addEventListener("popstate", function () {
    window.location.reload();
});
