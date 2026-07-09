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
