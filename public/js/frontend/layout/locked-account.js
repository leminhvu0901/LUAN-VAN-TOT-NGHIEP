/**
 * locked-account.js - Xử lý hiển thị hộp thoại cảnh báo khi tài khoản bị khóa ở Frontend
 * Các tính năng bao gồm:
 * - Tự động chặn và hiển thị hộp thoại SweetAlert2 khi người dùng đăng nhập bằng tài khoản đang bị khóa.
 * - Lấy thông tin tài khoản (Họ tên, Email) và Lý do khóa từ biến toàn cục window.lockedUserData.
 * - Triển khai hàm làm sạch HTML (escapeHtml) để loại bỏ nguy cơ tấn công chèn mã XSS.
 * - Xây dựng giao diện popup cảnh báo dạng thẻ Card phẳng bo góc hiện đại.
 * - Cung cấp hai nút lựa chọn: "Liên hệ Hỗ trợ Zalo" (mở cửa sổ Zalo) hoặc "Đăng xuất" (thoát tài khoản xóa session).
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Nhận thông tin người dùng từ Backend truyền qua biến toàn cục an toàn
    const userData = window.lockedUserData || { name: '', email: '', reason: '' };

    /**
     * Hàm làm sạch chuỗi nhập liệu (HTML Entity Escape) để ngăn chặn chèn mã độc hại (XSS)
     * @param {string} unsafe - Chuỗi chưa qua lọc
     * @returns {string} - Chuỗi an toàn
     */
    const escapeHtml = (unsafe) => {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    // Làm sạch dữ liệu trước khi chèn vào giao diện HTML
    const safeName = escapeHtml(userData.name);
    const safeEmail = escapeHtml(userData.email);
    const safeReason = escapeHtml(userData.reason);

    // 2. Tạo phần hiển thị Lý do khóa nếu Backend có trả về lý do cụ thể
    let reasonHtml = '';
    if (safeReason) {
        reasonHtml = `
            <div class="locked-reason-box">
                <div class="locked-reason-label">Lý do khóa:</div>
                <div class="locked-reason-text">${safeReason}</div>
            </div>
        `;
    }

    // 3. Xây dựng cấu trúc HTML cho Modal cảnh báo khóa tài khoản
    const htmlContent = `
        <div class="locked-popup-container">
            <div class="locked-icon-wrapper">
                <!-- Icon ổ khóa khóa SVG -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <div class="locked-badge">Tài khoản đang bị khóa</div>
            <div class="locked-title">Gián đoạn dịch vụ</div>
            <div class="locked-desc">Tài khoản của bạn tạm thời không thể thao tác trên hệ thống. Vui lòng liên hệ Admin.</div>
            
            <!-- Hiển thị bảng thông tin tài khoản bị khóa -->
            <div class="locked-info-grid">
                <div class="locked-info-card">
                    <div class="locked-info-label">Họ tên</div>
                    <div class="locked-info-value" title="${safeName}">${safeName}</div>
                </div>
                <div class="locked-info-card">
                    <div class="locked-info-label">Email</div>
                    <div class="locked-info-value" title="${safeEmail}">${safeEmail}</div>
                </div>
            </div>
            
            ${reasonHtml}
        </div>
    `;

    // 4. Kích hoạt hiển thị hộp thoại cảnh báo SweetAlert2 đè toàn bộ màn hình
    Swal.fire({
        html: htmlContent,
        width: '380px',
        showCancelButton: true,
        confirmButtonText: 'Liên hệ Zalo',
        cancelButtonText: 'Đăng xuất',
        reverseButtons: true, // Đưa nút liên hệ (Confirm) sang bên phải, Đăng xuất (Cancel) sang bên trái
        allowOutsideClick: false, // Ngăn chặn đóng hộp thoại khi click ra ngoài
        allowEscapeKey: false, // Ngăn chặn đóng hộp thoại khi bấm nút ESC
        showCloseButton: false, // Ẩn nút dấu X đóng góc trên
        buttonsStyling: false, // Vô hiệu hóa style mặc định của SweetAlert2 để tự custom CSS riêng
        customClass: {
            popup: 'locked-popup',
            htmlContainer: 'locked-swal-html',
            actions: 'locked-swal-actions',
            confirmButton: 'locked-btn-contact',
            cancelButton: 'locked-btn-logout'
        },
        backdrop: `rgba(15, 23, 42, 0.8)` // Màu nền che toàn bộ trang phía sau (slate-900 mờ)
    }).then((result) => {
        if (result.isConfirmed) {
            // Mở link chat Zalo hỗ trợ trong tab mới
            window.open('https://zalo.me/0388359330', '_blank');
            // Tự động chuyển hướng đăng xuất tài khoản sau 500ms
            setTimeout(() => {
                window.location.href = '/logout';
            }, 500);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Chuyển hướng người dùng thẳng tới đường dẫn Đăng xuất tài khoản
            window.location.href = '/logout';
        }
    });
});
