// Lắng nghe sự kiện trang đã tải xong cấu trúc DOM để kiểm tra trạng thái khởi chạy Modal
document.addEventListener('DOMContentLoaded', function() {
    const loginModal = document.getElementById('login-modal');
    // Nếu tồn tại Modal đăng nhập và có cờ yêu cầu hiển thị (data-show-login="true") từ Backend gửi về
    if (loginModal && loginModal.getAttribute('data-show-login') === 'true') {
        loginModal.style.display = 'block'; // Hiển thị Modal đăng nhập ngay lập tức
        document.body.style.overflow = 'hidden'; // Khóa thanh cuộn màn hình chính phía sau
    }
});

// Lắng nghe sự kiện click trên toàn bộ tài liệu (Event Delegation) để xử lý các hành động liên quan đến Modal
document.addEventListener('click', function(e) {
    // Tìm phần tử Modal đăng nhập bằng ID
    const modal = document.getElementById('login-modal');
    if (!modal) return; // Nếu không tồn tại modal trên trang hiện tại thì bỏ qua

    // 1. Xử lý khi nhấn nút Đăng Nhập ở Header (#login-btn)
    const loginBtn = e.target.closest('#login-btn');
    if (loginBtn) {
        e.preventDefault(); // Ngăn chặn hành vi mặc định của thẻ (ví dụ chuyển hướng link)
        modal.style.display = 'block'; // Hiển thị modal đăng nhập
        document.body.style.overflow = 'hidden'; // Khóa thanh cuộn trang chính phía dưới
        return;
    }

    // 2. Xử lý khi nhấn nút Đóng (dấu X) bên trong Modal (#close-login)
    const closeBtn = e.target.closest('#close-login');
    if (closeBtn) {
        e.preventDefault();
        modal.style.display = 'none'; // Ẩn modal đăng nhập đi
        document.body.style.overflow = ''; // Mở lại thanh cuộn trang chính
        return;
    }

    // 3. Xử lý khi nhấn vào lớp nền mờ tối bên ngoài hộp đăng nhập (#login-overlay)
    const overlay = e.target.closest('#login-overlay');
    if (overlay) {
        modal.style.display = 'none'; // Tự động đóng modal
        document.body.style.overflow = ''; // Mở lại thanh cuộn trang chính
        return;
    }

    // 4. Xử lý khi nhấn nút "Đăng ký ngay" (#switch-to-register) để chuyển sang form đăng ký
    const switchToRegisterBtn = e.target.closest('#switch-to-register');
    if (switchToRegisterBtn) {
        e.preventDefault();
        modal.style.display = 'none'; // Ẩn modal đăng nhập hiện tại
        const registerModal = document.getElementById('register-modal');
        if (registerModal) {
            registerModal.style.display = 'block'; // Hiển thị modal đăng ký
            document.body.style.overflow = 'hidden'; // Đảm bảo khóa thanh cuộn nền vẫn được bật
        }
        return;
    }

    // 5. Xử lý khi nhấn nút "Quên mật khẩu?" (#switch-to-forgot) để chuyển sang form quên mật khẩu
    const switchToForgotBtn = e.target.closest('#switch-to-forgot');
    if (switchToForgotBtn) {
        e.preventDefault();
        modal.style.display = 'none'; // Ẩn modal đăng nhập hiện tại
        const forgotModal = document.getElementById('forgot-modal');
        if (forgotModal) {
            forgotModal.style.display = 'block'; // Hiển thị modal quên mật khẩu
            document.body.style.overflow = 'hidden'; // Đảm bảo khóa thanh cuộn nền vẫn được bật
        }
        return;
    }
});

// Submit form đăng nhập qua fetch — trước đây sai email/mật khẩu thì tải lại cả trang (bất kể đang ở
// trang nào khi mở modal), rồi phải tự phát hiện cờ data-show-login để mở lại modal + hiện lỗi. Giờ
// submit qua fetch: sai thì modal đứng yên tại chỗ, hiện lỗi ngay; đúng thì điều hướng thật tới đích
// server trả về (trang chủ/dashboard tùy vai trò — y hệt logic cũ, chỉ chuyển từ redirect() sang JSON).
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.querySelector('#login-modal form');
    if (!loginForm) return;

    loginForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const btn = loginForm.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        fetch(loginForm.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: new FormData(loginForm),
        })
            .then(function (response) {
                return response.json().then(function (data) { return { status: response.status, data: data }; });
            })
            .then(function (result) {
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Đăng nhập thất bại, vui lòng thử lại.';
                    const errorEl = document.getElementById('login-error-alert');
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
