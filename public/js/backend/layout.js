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
});

// ---------------------------------------------------------
// THƯ VIỆN THÔNG BÁO GLOBAL (AdminAlert)
// ---------------------------------------------------------
window.AdminAlert = {
    success: function (message, title = 'Thành công!') {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'success',
                title: title,
                text: message,
                timer: 2000,
                showConfirmButton: false,
                width: '320px',
                padding: '1rem',
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-base font-bold text-gray-800',
                    htmlContainer: 'text-sm text-gray-500 mt-1',
                    icon: 'transform scale-[0.6] -mt-3 -mb-2',
                }
            });
        }
    },
    error: function (message, title = 'Lỗi') {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'error',
                title: title,
                html: message,
                width: '320px',
                padding: '1rem',
                confirmButtonText: 'Đóng',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-base font-bold text-gray-800',
                    confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                    icon: 'transform scale-[0.6] -mt-3 -mb-2',
                    actions: 'mt-3 w-full flex justify-center'
                }
            });
        }
    },
    confirm: function (message, confirmCallback, title = 'Xác nhận') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
                width: '360px',
                padding: '1.25rem',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-lg font-bold text-gray-900',
                    htmlContainer: 'text-sm text-gray-600',
                    icon: 'transform scale-[0.7] -mt-2 -mb-2',
                    actions: 'w-full flex gap-3 px-2 mt-4',
                    confirmButton: 'flex-1 px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors shadow-sm',
                    cancelButton: 'flex-1 px-4 py-2 bg-white text-gray-700 font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm'
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
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // ---------------------------------------------------------
    // HIỂN THỊ THÔNG BÁO FLASH MESSAGE (Global)
    // ---------------------------------------------------------
    if (typeof Swal !== 'undefined') {
        if (window.flashSuccessMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: window.flashSuccessMessage,
                timer: 2000,
                showConfirmButton: false,
                width: '320px',
                padding: '1rem',
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-base font-bold text-gray-800',
                    htmlContainer: 'text-sm text-gray-500 mt-1',
                    icon: 'transform scale-[0.6] -mt-3 -mb-2',
                }
            });
        }
        if (window.flashErrorMessages) {
            let htmlContent = '<ul class="text-left text-sm text-gray-600 list-disc pl-5 space-y-1">';
            window.flashErrorMessages.forEach(msg => {
                htmlContent += `<li>${msg}</li>`;
            });
            htmlContent += '</ul>';
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                html: htmlContent,
                width: '320px',
                padding: '1rem',
                confirmButtonText: 'Đóng',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-xl shadow-xl border border-gray-100',
                    title: 'text-base font-bold text-gray-800',
                    confirmButton: 'px-4 py-1.5 rounded-lg text-sm font-semibold bg-red-500 text-white hover:bg-red-600 transition-all shadow-sm',
                    icon: 'transform scale-[0.6] -mt-3 -mb-2',
                    actions: 'mt-3 w-full flex justify-center'
                }
            });
        }
    }
});
