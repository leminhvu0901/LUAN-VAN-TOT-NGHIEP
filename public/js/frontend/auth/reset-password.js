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

// Mở Modal Đặt lại mật khẩu bằng JS thuần (không tải lại trang) - gọi từ verify-otp.js ngay sau khi
// xác nhận OTP quên mật khẩu thành công, tương tự cách openOtpModal() được gọi từ register.js.
window.openResetPasswordModal = function () {
    const resetModal = document.getElementById('reset-password-modal');
    if (!resetModal) return;

    const errorEl = document.getElementById('reset-password-error-alert');
    if (errorEl) errorEl.classList.add('hidden');

    resetModal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    const firstInput = resetModal.querySelector('input');
    if (firstInput) setTimeout(function () { firstInput.focus(); }, 100);
};

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

// Submit form đặt lại mật khẩu qua fetch - sai thì modal đứng yên, hiện lỗi tại chỗ; đúng thì điều
// hướng thật tới trang chủ (đã tự đăng nhập luôn), giống hệt luồng đăng ký/đăng nhập.
document.addEventListener('DOMContentLoaded', function () {
    const resetForm = document.querySelector('#reset-password-modal form');
    if (!resetForm) return;

    resetForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const btn = resetForm.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        fetch(resetForm.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: new FormData(resetForm),
        })
            .then(function (response) {
                return response.json().then(function (data) { return { status: response.status, data: data }; });
            })
            .then(function (result) {
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Đặt lại mật khẩu thất bại, vui lòng thử lại.';
                    const errorEl = document.getElementById('reset-password-error-alert');
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    } else {
                        if (window.FrontendAlert) window.FrontendAlert.error(message); else alert(message);
                    }
                    if (btn) btn.disabled = false;
                    return;
                }

                if (result.data && result.data.redirect_url) {
                    window.location.href = result.data.redirect_url;
                    return;
                }

                if (btn) btn.disabled = false;
            })
            .catch(function () {
                if (window.FrontendAlert) window.FrontendAlert.error('Không thể kết nối máy chủ, vui lòng thử lại.'); else alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                if (btn) btn.disabled = false;
            });
    });
});
