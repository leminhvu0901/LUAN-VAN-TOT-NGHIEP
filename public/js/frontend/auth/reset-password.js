// Tự động hiển thị Modal Đặt lại mật khẩu nếu Backend đánh dấu qua thuộc tính data-show-reset-password
// (session('can_reset_password') còn hiệu lực - vd người dùng F5 lại trang giữa chừng).
document.addEventListener('DOMContentLoaded', function () {
    const resetModal = document.getElementById('reset-password-modal');
    if (resetModal && resetModal.getAttribute('data-show-reset-password') === 'true') {
        resetModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        const firstInput = resetModal.querySelector('input');
        if (firstInput) setTimeout(function () { firstInput.focus(); }, 100);
    }
});

// Đóng modal khi nhấn nút X hoặc nhấp ra ngoài vùng nền tối
document.addEventListener('click', function (e) {
    const modal = document.getElementById('reset-password-modal');
    if (!modal) return;

    const closeBtn = e.target.closest('#close-reset-password');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    const overlay = e.target.closest('#reset-password-overlay');
    if (overlay) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }
});

// Form đặt lại mật khẩu submit thật (tải lại trang) — sai thì quay lại kèm lỗi
// ($errors->has('reset_error')), modal tự mở lại nhờ session('show_reset_password'); đúng thì server
// redirect thật tới trang chủ (đã tự đăng nhập luôn).
