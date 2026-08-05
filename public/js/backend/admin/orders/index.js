/**
 * index.js - Quản lý trang danh sách đơn hàng khu vực Admin
 * Lọc/tìm kiếm/phân trang là form GET/link thường (tải lại trang), xóa/xóa nhiều/đổi trạng thái
 * đều là form POST thường, không còn AJAX.
 * JS ở đây chỉ còn quản lý: tích chọn nhiều dòng TRONG TRANG HIỆN TẠI để xóa hàng loạt, hộp thoại
 * prompt() xin lý do khi đổi trạng thái sang "Đã hủy", và Custom Dropdown giao diện cho ô chọn trạng thái.
 */

let tableContainer;

/**
 * Reset lại toàn bộ bộ nhớ chọn nhiều đơn hàng về trạng thái ban đầu
 */
function resetOrderSelection() {
    if (!window.selectedOrderIds) return;
    window.selectedOrderIds.clear();
    updateBulkDeleteButton();
}

/**
 * Cập nhật số lượng hiển thị và trạng thái ẩn hiện của nút "Xóa nhiều"
 */
function updateBulkDeleteButton() {
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");
    const bulkDeselectBtn = document.getElementById("bulk-deselect-btn");
    const selectedCountSpan = document.getElementById("selected-count");

    if (!bulkDeleteBtn || !selectedCountSpan) return;

    const count = window.selectedOrderIds.size;
    selectedCountSpan.textContent = count;

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

/**
 * Đóng gói và submit form thực thi xóa hàng loạt các đơn hàng đang tích chọn trong trang hiện tại
 */
function submitBulkDelete() {
    if (window.selectedOrderIds.size === 0) return;
    if (!confirm(`Bạn chuẩn bị xóa ${window.selectedOrderIds.size} đơn hàng đã chọn. Tiếp tục?`)) return;

    const bulkDeleteForm = document.getElementById("bulk-delete-form");
    bulkDeleteForm.querySelectorAll('input[name="order_ids[]"]').forEach((el) => el.remove());

    window.selectedOrderIds.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "order_ids[]";
        input.value = id;
        bulkDeleteForm.appendChild(input);
    });

    bulkDeleteForm.submit();
}

/**
 * Khởi tạo ô tìm kiếm và Flatpickr (lọc/tìm kiếm/phân trang submit form GET thường, bấm nút "Lọc")
 */
function initSearchAndFilters() {
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
        });
    }
}

/**
 * Khởi tạo bắt các sự kiện thay đổi dữ liệu bảng (Check All, Chọn dòng, Đổi trạng thái)
 */
function initTableEvents() {
    tableContainer = document.getElementById("table-container");
    const bulkDeleteBtn = document.getElementById("bulk-delete-btn");

    window.selectedOrderIds = new Set();
    window.submitBulkDelete = submitBulkDelete;

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener("click", submitBulkDelete);
    }

    tableContainer.addEventListener("change", function (e) {
        // 1. Checkbox chọn tất cả (chỉ các dòng trong trang hiện tại)
        if (e.target.classList.contains("js-select-all")) {
            const isChecked = e.target.checked;

            document.querySelectorAll(".js-select-all").forEach(cb => cb.checked = isChecked);

            window.selectedOrderIds.clear();
            document.querySelectorAll(".order-checkbox").forEach((cb) => {
                cb.checked = isChecked;
                if (isChecked) window.selectedOrderIds.add(cb.value);
            });

            updateBulkDeleteButton();
            return;
        }

        // 2. Checkbox chọn từng dòng đơn hàng
        if (e.target.classList.contains("order-checkbox")) {
            if (e.target.checked) {
                window.selectedOrderIds.add(e.target.value);
            } else {
                window.selectedOrderIds.delete(e.target.value);
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
            const select = e.target;

            if (select.value !== "cancelled") {
                select.form.submit();
                return;
            }

            // Nếu đổi trạng thái sang HỦY ĐƠN: xin lý do hủy qua prompt() gốc của trình duyệt
            const reason = prompt("Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự):");
            if (reason === null) {
                select.value = select.dataset.currentStatus; // Rollback trạng thái cũ nếu hủy bỏ hộp thoại
                return;
            }
            if (reason.trim().length < 5) {
                alert("Lý do hủy đơn phải có ít nhất 5 ký tự.");
                select.value = select.dataset.currentStatus;
                return;
            }

            const reasonInput = document.createElement("input");
            reasonInput.type = "hidden";
            reasonInput.name = "cancel_reason";
            reasonInput.value = reason.trim();
            select.form.appendChild(reasonInput);
            select.form.submit();
        }
    });

    // Theo dõi thay đổi cấu trúc bảng để tự động cập nhật số lượng nút chọn hàng loạt
    const observer = new MutationObserver(function () {
        updateBulkDeleteButton();
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

document.addEventListener('change', function (e) {
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
