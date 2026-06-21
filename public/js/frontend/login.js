document.addEventListener('click', function(e) {
    const modal = document.getElementById('login-modal');
    if (!modal) return;

    const loginBtn = e.target.closest('#login-btn');
    if (loginBtn) {
        e.preventDefault();
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        return;
    }

    const closeBtn = e.target.closest('#close-login');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    const overlay = e.target.closest('#login-overlay');
    if (overlay) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    const switchToRegisterBtn = e.target.closest('#switch-to-register');
    if (switchToRegisterBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        const registerModal = document.getElementById('register-modal');
        if (registerModal) {
            registerModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        return;
    }

    const switchToForgotBtn = e.target.closest('#switch-to-forgot');
    if (switchToForgotBtn) {
        e.preventDefault();
        modal.style.display = 'none';
        const forgotModal = document.getElementById('forgot-modal');
        if (forgotModal) {
            forgotModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        return;
    }
});
