<div id="register-modal" style="display: none; position: fixed; inset: 0; z-index: 99999; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Overlay -->
    <div id="register-overlay" style="position: absolute; inset: 0; background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

    <!-- Modal Wrapper -->
    <div class="l-modal-wrapper">

        <!-- Register Box -->
        <div id="register-box" class="l-modal-box">

            <!-- Back Button -->
            <button id="switch-to-login-back" type="button" class="l-back-btn" aria-label="Quay lại Đăng nhập">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>

            <!-- Close Button -->
            <button id="close-register" type="button" class="l-close-btn" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <!-- Title -->
            <h2 class="l-title">Đăng Ký</h2>

            <form action="#" method="post" novalidate>
                @csrf
                
                <div class="l-form-group">
                    <label for="fullName" class="l-label">Họ và tên</label>
                    <input id="fullName" name="full_name" type="text" placeholder="Nhập tên của bạn" class="l-input" required />
                </div>

                <div class="l-form-group">
                    <label for="email" class="l-label">Email hoặc số điện thoại</label>
                    <input id="email" name="email" type="text" placeholder="Nhập email hoặc số điện thoại" class="l-input" required />
                </div>

                <div class="l-form-group">
                    <label for="password" class="l-label">Mật khẩu</label>
                    <input id="password" name="password" type="password" placeholder="Nhập mật khẩu" class="l-input" required />
                </div>

                <div class="l-form-group">
                    <label for="password_confirmation" class="l-label">Xác nhận mật khẩu</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Nhập lại mật khẩu" class="l-input" required />
                </div>

                <div class="l-form-group" style="text-align: center;">
                    <p style="font-size: 0.85rem; color: #6b7280; line-height: 1.5;">
                        Bằng việc đăng ký, bạn đồng ý với
                        <a href="#" style="color: #10b981; font-weight: 600; text-decoration: none;">Điều khoản dịch vụ</a>
                        và
                        <a href="#" style="color: #10b981; font-weight: 600; text-decoration: none;">Chính sách bảo mật</a>.
                    </p>
                </div>

                <button type="submit" class="l-submit-btn">
                    Đăng Ký Tài Khoản
                </button>
            </form>

            <div class="l-footer">
                Đã có tài khoản? <a href="#" id="switch-to-login">Đăng nhập</a>
            </div>

        </div>
    </div>
</div>

<script>
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
</script>
