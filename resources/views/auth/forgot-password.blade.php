<div id="forgot-modal" style="display: none; position: fixed; inset: 0; z-index: 99999; font-family: 'Inter', system-ui, sans-serif;">

    <!-- Overlay -->
    <div id="forgot-overlay" style="position: absolute; inset: 0; background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"></div>

    <!-- Modal Wrapper -->
    <div class="l-modal-wrapper">

        <!-- Forgot Password Box -->
        <div id="forgot-box" class="l-modal-box">

            <!-- Back Button to Login -->
            <button id="switch-to-login-from-forgot" type="button" class="l-back-btn" aria-label="Quay lại Đăng nhập">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>

            <!-- Close Button -->
            <button id="close-forgot" type="button" class="l-close-btn" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <!-- Icon -->
            <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
                <img src="{{ asset('images/icons/quenmk.png') }}" alt="Quên mật khẩu" style="width: 80px; height: 80px; object-fit: contain;" />
            </div>

            <!-- Title -->
            <h2 class="l-title" style="margin-bottom: 1rem;">Quên mật khẩu</h2>

            <p style="text-align: center; color: #4b5563; font-size: 0.95rem; line-height: 1.5; margin-bottom: 2rem;">
                Vui lòng nhập email  để nhận mã khôi phục mật khẩu.
            </p>

            <form action="#" method="post" novalidate>
                @csrf
                
                <div class="l-form-group">
                    <label for="recoveryContact" class="l-label">Email hoặc số điện thoại</label>
                    <input id="recoveryContact" name="recovery_contact" type="text" placeholder="Nhập email hoặc số điện thoại" class="l-input" required />
                </div>

                <button type="submit" class="l-submit-btn" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    Gửi mã xác nhận
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M5 12h14" />
                        <path d="M13 6l6 6l-6 6" />
                    </svg>
                </button>
            </form>

            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc</span>
                <div class="l-divider-line"></div>
            </div>

            <div class="l-footer" style="margin-top: 0;">
                Chưa có tài khoản? <a href="#" id="switch-to-register-from-forgot">Đăng ký ngay</a>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('show_forgot'))
            const forgotModal = document.getElementById('forgot-modal');
            if (forgotModal) {
                forgotModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        @endif
    });

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
</script>
