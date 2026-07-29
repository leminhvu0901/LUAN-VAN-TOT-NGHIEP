// Lắng nghe sự kiện trang đã tải xong cấu trúc DOM để khởi chạy trạng thái ban đầu của Modal
document.addEventListener('DOMContentLoaded', function () {
    const registerModal = document.getElementById('register-modal');
    // Nếu tồn tại Modal đăng ký và Backend yêu cầu hiển thị trực tiếp (qua thuộc tính data-show-register="true")
    if (registerModal && registerModal.getAttribute('data-show-register') === 'true') {
        registerModal.style.display = 'block'; // Hiển thị Modal đăng ký ngay lập tức
        document.body.style.overflow = 'hidden'; // Khóa thanh cuộn trang chính phía dưới
    }
});

// Lắng nghe sự kiện click trên toàn bộ tài liệu (Event Delegation) để quản lý tương tác với Modal Đăng ký
document.addEventListener('click', function(e) {
    const registerModal = document.getElementById('register-modal');
    if (!registerModal) return; // Nếu không tồn tại modal trên trang hiện tại thì thoát

    // 1. Xử lý khi nhấn nút Đóng (dấu X) trong Modal (#close-register)
    const closeRegisterBtn = e.target.closest('#close-register');
    if (closeRegisterBtn) {
        e.preventDefault();
        registerModal.style.display = 'none'; // Ẩn modal đăng ký
        document.body.style.overflow = ''; // Mở lại thanh cuộn trang chính
        return;
    }

    // 2. Xử lý khi nhấn vào phần nền tối mờ bên ngoài hộp đăng ký (#register-overlay)
    const overlayRegister = e.target.closest('#register-overlay');
    if (overlayRegister) {
        registerModal.style.display = 'none'; // Ẩn modal đăng ký
        document.body.style.overflow = ''; // Mở lại thanh cuộn trang chính
        return;
    }

    // 3. Xử lý khi nhấn nút "Đăng nhập" hoặc nút quay lại "Quay lại Đăng nhập" để chuyển đổi nhanh sang Modal Đăng nhập
    const switchToLoginBtn = e.target.closest('#switch-to-login, #switch-to-login-back');
    if (switchToLoginBtn) {
        e.preventDefault();
        registerModal.style.display = 'none'; // Ẩn modal đăng ký hiện tại
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            loginModal.style.display = 'block'; // Hiển thị modal đăng nhập
            document.body.style.overflow = 'hidden'; // Tiếp tục giữ khóa thanh cuộn trang chính
        }
        return;
    }
});

// Submit form đăng ký qua fetch — trước đây sai (email đã dùng, mật khẩu không đủ mạnh...) thì tải
// lại cả trang mới thấy lỗi, phải gõ lại toàn bộ. Giờ sai thì modal đứng yên, hiện lỗi tại chỗ; đúng
// thì JS tự chuyển sang modal OTP luôn (openOtpModal(), định nghĩa ở verify-otp.js) không cần tải lại
// trang để server render lại cờ show_otp nữa.
document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.querySelector('#register-modal form');
    if (!registerForm) return;

    registerForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const btn = registerForm.querySelector('button[type="submit"]');
        // Lưu lại chữ gốc để khôi phục sau — nếu không có bước này, khi SMTP chậm/lỗi thì nút bấm chỉ
        // disable im lìm không có phản hồi gì, người dùng tưởng web bị đứng.
        const originalBtnText = btn ? btn.textContent : '';
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Đang xử lý...';
        }

        fetch(registerForm.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: new FormData(registerForm),
        })
            .then(function (response) {
                return response.json().then(function (data) { return { status: response.status, data: data }; });
            })
            .then(function (result) {
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Đăng ký thất bại, vui lòng kiểm tra lại.';
                    const errorEl = document.getElementById('register-error-alert');
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    } else {
                        if (window.FrontendAlert) window.FrontendAlert.error(message); else alert(message);
                    }
                    if (btn) { btn.disabled = false; btn.textContent = originalBtnText; }
                    return;
                }

                if (result.data && result.data.otp_required) {
                    const registerModal = document.getElementById('register-modal');
                    if (registerModal) registerModal.style.display = 'none';
                    if (typeof window.openOtpModal === 'function') window.openOtpModal(result.data.email);
                    if (btn) { btn.disabled = false; btn.textContent = originalBtnText; }
                    return;
                }

                if (btn) { btn.disabled = false; btn.textContent = originalBtnText; }
            })
            .catch(function () {
                if (window.FrontendAlert) window.FrontendAlert.error('Không thể kết nối máy chủ, vui lòng thử lại.'); else alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                if (btn) { btn.disabled = false; btn.textContent = originalBtnText; }
            });
    });
});
