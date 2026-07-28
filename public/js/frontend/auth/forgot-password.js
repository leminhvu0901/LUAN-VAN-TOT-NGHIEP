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

// Submit form quên mật khẩu qua fetch — trước đây email không tồn tại thì tải lại cả trang mới thấy
// lỗi. Giờ sai thì modal đứng yên, hiện lỗi tại chỗ; đúng thì JS tự chuyển sang modal OTP luôn
// (openOtpModal(), định nghĩa ở verify-otp.js) không cần tải lại trang.
document.addEventListener('DOMContentLoaded', function () {
    const forgotForm = document.querySelector('#forgot-modal form');
    if (!forgotForm) return;

    forgotForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const btn = forgotForm.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        fetch(forgotForm.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: new FormData(forgotForm),
        })
            .then(function (response) {
                return response.json().then(function (data) { return { status: response.status, data: data }; });
            })
            .then(function (result) {
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Không thể gửi yêu cầu, vui lòng kiểm tra lại.';
                    const errorEl = document.getElementById('forgot-error-alert');
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    } else {
                        alert(message);
                    }
                    if (btn) btn.disabled = false;
                    return;
                }

                if (result.data && result.data.otp_required) {
                    const forgotModal = document.getElementById('forgot-modal');
                    if (forgotModal) forgotModal.style.display = 'none';
                    if (typeof window.openOtpModal === 'function') window.openOtpModal(result.data.email);
                    if (btn) btn.disabled = false;
                    return;
                }

                if (btn) btn.disabled = false;
            })
            .catch(function () {
                alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                if (btn) btn.disabled = false;
            });
    });
});
