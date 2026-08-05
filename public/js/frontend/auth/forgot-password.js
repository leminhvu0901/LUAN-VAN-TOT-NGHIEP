document.addEventListener('click', function(e) {
    const forgotModal = document.getElementById('forgot-modal');
    if (!forgotModal) return;

    // Đóng modal
    const closeForgotBtn = e.target.closest('#close-forgot');
    if (closeForgotBtn) {
        e.preventDefault();
        forgotModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Click ra ngoài overlay
    const overlayForgot = e.target.closest('#forgot-overlay');
    if (overlayForgot) {
        forgotModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Chuyển sang đăng nhập (nút back)
    const switchToLoginFromForgotBtn = e.target.closest('#switch-to-login-from-forgot');
    if (switchToLoginFromForgotBtn) {
        e.preventDefault();
        forgotModal.style.display = 'none';
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            loginModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        return;
    }

    // Chuyển sang đăng ký
    const switchToRegisterFromForgotBtn = e.target.closest('#switch-to-register-from-forgot');
    if (switchToRegisterFromForgotBtn) {
        e.preventDefault();
        forgotModal.style.display = 'none';
        const registerModal = document.getElementById('register-modal');
        if (registerModal) {
            registerModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        return;
    }
});

// Form quên mật khẩu submit thật (tải lại trang) — sai (email không tồn tại) thì quay lại kèm lỗi
// ($errors->has('forgot_error')), script inline cuối forgot-password.blade.php tự mở lại modal dựa
// vào session('show_forgot'); đúng thì server flash show_otp=true, modal OTP tự mở sau khi tải lại.
