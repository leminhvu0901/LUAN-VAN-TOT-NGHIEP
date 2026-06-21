document.addEventListener('click', function(e) {
    const registerModal = document.getElementById('register-modal');
    if (!registerModal) return;

    // Xử lý khi nhấn nút đóng register
    const closeRegisterBtn = e.target.closest('#close-register');
    if (closeRegisterBtn) {
        e.preventDefault();
        registerModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Xử lý khi nhấn vào overlay (nền mờ bên ngoài register)
    const overlayRegister = e.target.closest('#register-overlay');
    if (overlayRegister) {
        registerModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Xử lý nút chuyển sang đăng nhập
    const switchToLoginBtn = e.target.closest('#switch-to-login, #switch-to-login-back');
    if (switchToLoginBtn) {
        e.preventDefault();
        registerModal.style.display = 'none';
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            loginModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        return;
    }
});
