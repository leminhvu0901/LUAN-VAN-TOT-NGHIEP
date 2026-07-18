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
