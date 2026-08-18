{{-- Khung Modal bao quanh màn hình Đăng ký --}}
<div id="register-modal" data-show-register="{{ ($errors->has('full_name') || $errors->has('email') || $errors->has('password') || $errors->has('register_error') || session('show_register')) ? 'true' : 'false' }}">

    {{-- Modal --}}
    <div id="register-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">

        {{-- Hộp đăng ký chính chứa biểu mẫu đăng ký và các --}}
        <div id="register-box" class="l-modal-box">

            {{-- Nút quay lại màn hình đăng nhập --}}
            <button id="switch-to-login-back" type="button" class="l-back-btn" aria-label="Quay lại Đăng nhập">
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>

            {{-- Nút dấu X dùng để đóng Modal đăng ký --}}
            <button id="close-register" type="button" class="l-close-btn" aria-label="Đóng">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            {{-- Tiêu đề của biểu mẫu --}}
            <h2 class="l-title">Đăng Ký</h2>

            {{-- Biểu mẫu gửi thông tin đăng ký lên hệ thống qua --}}
            <form action="{{ route('register.post') }}" method="post" novalidate>
                {{-- Token bảo mật bắt buộc của Laravel để phòng chống --}}
                @csrf

                {{-- Hiển thị thông báo lỗi tổng quát khi có lỗi xảy --}}
                <div id="register-error-alert" class="l-error-alert {{ $errors->has('register_error') ? '' : 'hidden' }}">
                    {{ $errors->first('register_error') }}
                </div>

                {{-- Ô nhập thông tin Họ và tên của người dùng --}}
                <div class="l-form-group">
                    <label for="fullName" class="l-label">Họ và tên</label>
                    <input id="fullName" name="full_name" type="text" placeholder="Nhập tên của bạn"
                        class="l-input @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}"
                        required />
                    {{-- Hiển thị lỗi xác thực cụ thể cho trường Họ và tên --}}
                    @error('full_name')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ô nhập thông tin Địa chỉ Email --}}
                <div class="l-form-group">
                    <label for="email" class="l-label">Email</label>
                    <input id="email" name="email" type="email" placeholder="Nhập email của bạn"
                        class="l-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required />
                    {{-- Hiển thị lỗi xác thực cụ thể cho trường Email --}}
                    @error('email')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ô nhập Mật khẩu bảo mật --}}
                <div class="l-form-group">
                    <label for="password" class="l-label">Mật khẩu</label>
                    {{-- L-input-wrap: bọc riêng ô input để nút mắt canh --}}
                    <div class="l-input-wrap">
                        <input id="password" name="password" type="password" placeholder="Nhập mật khẩu"
                            class="l-input has-password-toggle @error('password') is-invalid @enderror" required />
                        
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="password" aria-label="Hiện/ẩn mật khẩu">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                    {{-- Hiển thị lỗi xác thực cụ thể cho trường Mật khẩu --}}
                    @error('password')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ô xác nhận lại mật khẩu nhằm kiểm tra lỗi gõ phím --}}
                <div class="l-form-group">
                    <label for="password_confirmation" class="l-label">Xác nhận mật khẩu</label>
                    <div class="l-input-wrap">
                        <input id="password_confirmation" name="password_confirmation" type="password"
                            placeholder="Nhập lại mật khẩu" class="l-input has-password-toggle" required />
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="password_confirmation" aria-label="Hiện/ẩn mật khẩu">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- Điều khoản dịch vụ và Chính sách bảo mật đồng ý khi đăng ký --}}
                <div class="l-form-group l-register-terms-group">
                    <p class="l-register-terms-text">
                        Bằng việc đăng ký, bạn đồng ý với
                        <a href="#" class="l-register-terms-link">Điều khoản dịch vụ</a>
                        và
                        <a href="#" class="l-register-terms-link">Chính sách bảo mật</a>.
                    </p>
                </div>

                {{-- Nút Submit xác nhận việc đăng ký tài khoản --}}
                <button type="submit" class="l-submit-btn">
                    Đăng Ký Tài Khoản
                </button>
            </form>

            {{-- Dòng kẻ ngăn cách giữa đăng ký thông thường và bên thứ 3 --}}
            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc tiếp tục với</span>
                <div class="l-divider-line"></div>
            </div>

            {{-- Nút Đăng ký nhanh liên kết với dịch vụ Google OAuth --}}
            <a href="{{ route('auth.google') }}" class="l-google-btn">
                <svg width="22" height="22" viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05"
                        d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z" />
                    <path fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9c.87-2.6 3.3-4.52 6.16-4.52z" />
                </svg>
                Đăng ký với Google
            </a>

            {{-- Chuyển đổi nhanh sang Popup Đăng nhập nếu đã có tài khoản --}}
            <div class="l-footer">
                Đã có tài khoản? <a href="#" id="switch-to-login">Đăng nhập</a>
            </div>

        </div>
    </div>
</div>

<script>
// Tự động mở modal đăng ký nếu có thông báo lỗi từ server
document.addEventListener('DOMContentLoaded', function () {
    const registerModal = document.getElementById('register-modal');
    if (registerModal && registerModal.getAttribute('data-show-register') === 'true') {
        registerModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
});

// Xử lý sự kiện đóng modal hoặc chuyển đổi sang modal đăng nhập
document.addEventListener('click', function(e) {
    const registerModal = document.getElementById('register-modal');
    if (!registerModal) return;

    // Đóng modal đăng ký khi bấm nút đóng
    const closeRegisterBtn = e.target.closest('#close-register');
    if (closeRegisterBtn) {
        e.preventDefault();
        registerModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Đóng modal đăng ký khi click vào lớp nền overlay
    const overlayRegister = e.target.closest('#register-overlay');
    if (overlayRegister) {
        registerModal.style.display = 'none';
        document.body.style.overflow = '';
        return;
    }

    // Chuyển sang modal đăng nhập khi bấm liên kết
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