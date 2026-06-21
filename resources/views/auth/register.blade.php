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

            <form action="{{ route('register.post') }}" method="post" novalidate>
                @csrf
                
                @if($errors->has('register_error'))
                    <div style="color: #ef4444; font-size: 0.875rem; margin-bottom: 1rem; text-align: center;">
                        {{ $errors->first('register_error') }}
                    </div>
                @endif

                <div class="l-form-group">
                    <label for="fullName" class="l-label">Họ và tên</label>
                    <input id="fullName" name="full_name" type="text" placeholder="Nhập tên của bạn" class="l-input @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" required />
                    @error('full_name')
                        <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="l-form-group">
                    <label for="email" class="l-label">Email</label>
                    <input id="email" name="email" type="email" placeholder="Nhập email của bạn" class="l-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required />
                    @error('email')
                        <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="l-form-group">
                    <label for="password" class="l-label">Mật khẩu</label>
                    <input id="password" name="password" type="password" placeholder="Nhập mật khẩu" class="l-input @error('password') is-invalid @enderror" required />
                    @error('password')
                        <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
                    @enderror
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

            <!-- Separator -->
            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc tiếp tục với</span>
                <div class="l-divider-line"></div>
            </div>

            <!-- Google Button -->
            <a href="{{ route('auth.google') }}" class="l-google-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; color: #374151; font-weight: 500; background-color: white; transition: all 0.2s;">
                <svg width="22" height="22" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9c.87-2.6 3.3-4.52 6.16-4.52z" />
                </svg>
                Đăng ký với Google
            </a>

            <div class="l-footer">
                Đã có tài khoản? <a href="#" id="switch-to-login">Đăng nhập</a>
            </div>

        </div>
    </div>
</div>

<script src="{{ asset('js/frontend/register.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($errors->has('full_name') || $errors->has('email') || $errors->has('password') || $errors->has('register_error') || session('show_register'))
            const registerModal = document.getElementById('register-modal');
            if (registerModal) {
                registerModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        @endif
    });
</script>
