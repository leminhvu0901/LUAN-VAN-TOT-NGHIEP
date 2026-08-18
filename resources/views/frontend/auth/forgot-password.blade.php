<div id="forgot-modal">

    <div id="forgot-overlay"></div>

    <div class="l-modal-wrapper">

        <div id="forgot-box" class="l-modal-box">

            <button id="switch-to-login-from-forgot" type="button" class="l-back-btn" aria-label="Quay lại Đăng nhập">
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>

            <button id="close-forgot" type="button" class="l-close-btn" aria-label="Đóng">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="l-forgot-icon-wrap">
                <img src="{{ asset('images/icons/quenmk.png') }}" alt="Quên mật khẩu" class="l-forgot-icon" />
            </div>

            <h2 class="l-title">Quên mật khẩu</h2>

            <p class="l-forgot-desc">
                Vui lòng nhập email để nhận mã khôi phục mật khẩu.
            </p>

            <form action="{{ route('forgot-password.post') }}" method="post" novalidate>
                @csrf
                
                <div id="forgot-error-alert" class="l-error-alert {{ $errors->has('forgot_error') ? '' : 'hidden' }}">
                    {{ $errors->first('forgot_error') }}
                </div>

                <div class="l-form-group">
                    <label for="recoveryContact" class="l-label">Email</label>
                    <input id="recoveryContact" name="recovery_contact" type="text" placeholder="Nhập email của bạn"
                        class="l-input" required value="{{ old('recovery_contact') }}" />
                    @error('recovery_contact')
                        <div class="l-field-error-large">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="l-submit-btn">
                    Gửi mã xác nhận
                    <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
                </button>
            </form>

            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc</span>
                <div class="l-divider-line"></div>
            </div>

            <div class="l-footer">
                Chưa có tài khoản? <a href="#" id="switch-to-register-from-forgot">Đăng ký ngay</a>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function(e) {
        const forgotModal = document.getElementById('forgot-modal');
        if (!forgotModal) return;

        const closeForgotBtn = e.target.closest('#close-forgot');
        if (closeForgotBtn) {
            e.preventDefault();
            forgotModal.style.display = 'none';
            document.body.style.overflow = '';
            return;
        }

        const overlayForgot = e.target.closest('#forgot-overlay');
        if (overlayForgot) {
            forgotModal.style.display = 'none';
            document.body.style.overflow = '';
            return;
        }

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

    document.addEventListener('DOMContentLoaded', function() {
        @if (session('show_forgot'))
            const forgotModal = document.getElementById('forgot-modal');
            if (forgotModal) {
                forgotModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        @endif
    });
</script>
