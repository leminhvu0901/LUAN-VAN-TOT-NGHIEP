document.addEventListener('DOMContentLoaded', function () {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileCloseBtn = document.getElementById('mobile-close-btn');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const collapseBtn = document.getElementById('desktop-collapse-btn');
    const collapseIcon = document.getElementById('collapse-icon');

    // Mở / đóng sidebar trên Mobile
    function toggleMobileSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
    if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', toggleMobileSidebar);
    if (overlay) overlay.addEventListener('click', toggleMobileSidebar);

    // Thu nhỏ / Phóng to sidebar trên Desktop
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            sidebar.classList.toggle('is-collapsed');
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-[80px]');

            if (collapseIcon) {
                collapseIcon.style.transform = sidebar.classList.contains('is-collapsed') ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        });
    }

    // Tự động khởi tạo tất cả các Custom Select có class "custom-select-init"
    if (typeof window.initCustomSelects === 'function') {
        window.initCustomSelects();
    }
});

// ---------------------------------------------------------
// CUSTOM SELECT DROPDOWN (Mobile Responsive Fix)
// ---------------------------------------------------------
window.initCustomSelects = function () {
    const selects = document.querySelectorAll('select.custom-select-init');
    selects.forEach(select => {
        // Tránh khởi tạo 2 lần
        if (select.dataset.customSelectInitialized) return;
        select.dataset.customSelectInitialized = "true";
        select.style.display = 'none';

        const container = document.createElement('div');
        container.className = 'custom-select-container relative shrink-0 z-10 ' + (select.getAttribute('data-width-class') || 'w-full sm:w-auto');

        select.parentNode.insertBefore(container, select);
        container.appendChild(select);

        let selectedOption = select.options[select.selectedIndex];

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 w-full flex items-center justify-between cursor-pointer transition-colors hover:border-emerald-300 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 min-h-[40px] sm:min-h-[38px]';
        button.innerHTML = `<span class="truncate pr-2 select-label-text">${selectedOption ? selectedOption.text : ''}</span><span class="material-symbols-outlined text-[18px] text-gray-400 shrink-0">expand_more</span>`;
        container.appendChild(button);

        const menu = document.createElement('div');
        menu.className = 'absolute top-[calc(100%+4px)] left-0 w-full min-w-[160px] bg-white border border-gray-200 rounded-lg organic-shadow hidden flex-col z-[100] max-h-[220px] overflow-y-auto py-1 shadow-lg';
        container.appendChild(menu);

        select.addEventListener('change', () => {
            let currentOpt = select.options[select.selectedIndex];
            if (currentOpt) {
                button.querySelector('.select-label-text').textContent = currentOpt.text;
                Array.from(menu.children).forEach(child => {
                    if (child.dataset.value === currentOpt.value) {
                        child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 bg-emerald-50 text-emerald-700 font-semibold';
                    } else {
                        child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 text-gray-700';
                    }
                });
            }
        });

        Array.from(select.options).forEach(option => {
            const item = document.createElement('div');
            item.dataset.value = option.value;
            item.className = `px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 ${option.selected ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700'}`;
            item.textContent = option.text;

            item.addEventListener('click', (e) => {
                e.stopPropagation();
                select.value = option.value;
                button.querySelector('.select-label-text').textContent = option.text;

                Array.from(menu.children).forEach(child => {
                    child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 text-gray-700';
                });
                item.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 bg-emerald-50 text-emerald-700 font-semibold';

                menu.classList.add('hidden');
                menu.classList.remove('flex');
                container.style.zIndex = '10';

                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            menu.appendChild(item);
        });

        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('.custom-select-options').forEach(m => {
                m.classList.add('hidden');
                m.classList.remove('flex');
            });
            document.querySelectorAll('.custom-select-container').forEach(c => {
                c.style.zIndex = '10';
            });
            if (isHidden) {
                menu.classList.remove('hidden');
                menu.classList.add('flex');
                container.style.zIndex = '50';
            }
        });
        menu.classList.add('custom-select-options');
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-options').forEach(m => {
            m.classList.add('hidden');
            m.classList.remove('flex');
        });
        document.querySelectorAll('.custom-select-container').forEach(c => {
            c.style.zIndex = '10';
        });
    }, { once: false });
};

// ---------------------------------------------------------
// THƯ VIỆN THÔNG BÁO GLOBAL (AdminAlert)
// ---------------------------------------------------------
window.AdminAlert = {
    success: function (message, title = 'Thành công!') {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            return Toast.fire({
                icon: 'success',
                title: message || title
            });
        }
    },
    error: function (message, title = 'Lỗi') {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            return Toast.fire({
                icon: 'error',
                title: title,
                html: message
            });
        }
    },
    confirm: function (message, confirmCallback, title = 'Xác nhận') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                buttonsStyling: false,
                width: '340px',
                padding: '1.25rem',
                returnFocus: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100 p-4',
                    title: 'text-base font-bold text-gray-800 mb-1',
                    htmlContainer: 'text-xs text-gray-500 mb-4',
                    actions: 'w-full flex gap-2 mt-1',
                    confirmButton: 'flex-1 px-4 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer',
                    cancelButton: 'flex-1 px-4 py-1.5 bg-white text-gray-700 font-semibold rounded-lg text-xs border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer'
                }
            }).then((result) => {
                if (result.isConfirmed && typeof confirmCallback === 'function') {
                    confirmCallback();
                }
            });
        }
    },
    loading: function (message = 'Vui lòng đợi trong giây lát', title = 'Đang xử lý...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                html: message,
                allowOutsideClick: false,
                width: '280px',
                padding: '1rem',
                showConfirmButton: false,
                returnFocus: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100 p-4',
                    title: 'text-sm font-bold text-gray-800 mb-1',
                    htmlContainer: 'text-xs text-gray-500 mt-2'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    },
    prompt: function (title, message, placeholder, confirmCallback, validatorMessage = 'Vui lòng nhập thông tin!', confirmText = 'Xác nhận', minLength = 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                input: 'text',
                inputPlaceholder: placeholder,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Hủy',
                buttonsStyling: false,
                width: '340px',
                padding: '1.25rem',
                returnFocus: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100 p-4',
                    title: 'text-base font-bold text-gray-800 mb-1',
                    htmlContainer: 'text-xs text-gray-500 mb-3',
                    input: 'w-full px-3 py-2 border border-gray-200 rounded-lg outline-none text-xs focus:border-emerald-500 transition-colors mb-3 shadow-none text-center',
                    actions: 'w-full flex gap-2 mt-1',
                    confirmButton: 'flex-1 px-4 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer',
                    cancelButton: 'flex-1 px-4 py-1.5 bg-white text-gray-700 font-semibold rounded-lg text-xs border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer'
                },
                inputValidator: (value) => {
                    if (!value) {
                        return validatorMessage;
                    }
                    if (minLength && value.trim().length < minLength) {
                        return `Vui lòng nhập ít nhất ${minLength} ký tự.`;
                    }
                }
            }).then((result) => {
                if (typeof confirmCallback === 'function') {
                    confirmCallback(result.value, result.isConfirmed);
                }
            });
        } else {
            const reason = prompt(message);
            if (typeof confirmCallback === 'function') {
                if (reason !== null && reason.trim() !== "") {
                    confirmCallback(reason.trim(), true);
                } else {
                    confirmCallback(null, false);
                }
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // ---------------------------------------------------------
    // HIỂN THỊ THÔNG BÁO FLASH MESSAGE (Global)
    // ---------------------------------------------------------
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        if (window.flashSuccessMessage) {
            Toast.fire({
                icon: 'success',
                title: window.flashSuccessMessage
            });
        }
        if (window.flashErrorMessages) {
            let htmlContent = '<div class="text-left text-xs font-semibold space-y-0.5 mt-1">';
            window.flashErrorMessages.forEach(msg => {
                htmlContent += `<div>• ${msg}</div>`;
            });
            htmlContent += '</div>';

            Toast.fire({
                icon: 'error',
                title: 'Có lỗi xảy ra!',
                html: htmlContent,
                timer: 5000
            });
        }
    }
});
