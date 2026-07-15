document.addEventListener('DOMContentLoaded', function() {
    // Lấy dữ liệu từ Backend an toàn qua biến toàn cục
    const userData = window.lockedUserData || { name: '', email: '', reason: '' };

    // Hàm escape HTML để chống XSS
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

    const safeName = escapeHtml(userData.name);
    const safeEmail = escapeHtml(userData.email);
    const safeReason = escapeHtml(userData.reason);

    let reasonHtml = '';
    if (safeReason) {
        reasonHtml = `
            <div class="locked-reason-box">
                <div class="locked-reason-label">Lý do khóa:</div>
                <div class="locked-reason-text">${safeReason}</div>
            </div>
        `;
    }

    const htmlContent = `
        <div class="locked-popup-container">
            <div class="locked-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <div class="locked-badge">Tài khoản đang bị khóa</div>
            <div class="locked-title">Gián đoạn dịch vụ</div>
            <div class="locked-desc">Tài khoản của bạn tạm thời không thể thao tác trên hệ thống. Vui lòng liên hệ Admin.</div>
            
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

    Swal.fire({
        html: htmlContent,
        width: '380px',
        showCancelButton: true,
        confirmButtonText: 'Liên hệ Zalo',
        cancelButtonText: 'Đăng xuất',
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCloseButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'locked-popup',
            htmlContainer: 'locked-swal-html',
            actions: 'locked-swal-actions',
            confirmButton: 'locked-btn-contact',
            cancelButton: 'locked-btn-logout'
        },
        backdrop: `rgba(15, 23, 42, 0.8)`
    }).then((result) => {
        if (result.isConfirmed) {
            window.open('https://zalo.me/0388359330', '_blank');
            setTimeout(() => {
                window.location.href = '/logout';
            }, 500);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            window.location.href = '/logout';
        }
    });
});
