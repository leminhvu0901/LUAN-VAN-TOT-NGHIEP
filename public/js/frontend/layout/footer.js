// =========================================================================
// XỬ LÝ HIỂN THỊ RESPONSIVE CHO CHÂN TRANG (FOOTER ACCORDION MOBILE)
// =========================================================================
document.addEventListener('DOMContentLoaded', function () {
    // Tìm toàn bộ tiêu đề các cột thông tin ở phần footer (vùng H6)
    const headers = document.querySelectorAll('.footer-column h6');
    
    headers.forEach(header => {
        header.addEventListener('click', function () {
            // Chỉ kích hoạt chức năng Accordion đóng/mở nội dung khi kích thước màn hình nhỏ hơn hoặc bằng 640px (Mobile)
            if (window.innerWidth <= 640) {
                const column = this.closest('.footer-column');
                if (column) {
                    // Bật/Tắt class 'open' để ẩn/hiện danh sách liên kết bên dưới
                    column.classList.toggle('open');
                }
            }
        });
    });
});
