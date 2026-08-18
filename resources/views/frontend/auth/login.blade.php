{{-- Khung Modal bao quanh màn hình đăng nhập, ẩn mặc định bằng CSS và tự bung khi Backend bật cờ --}}
<div id="login-modal"
    data-show-login="{{ $errors->has('identity') || $errors->has('password') || $errors->has('login_error') || session('show_login') ? 'true' : 'false' }}">

    {{-- Lớp nền tối mờ phía sau Modal --}}
    <div id="login-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">

        {{-- Hộp đăng nhập chính chứa biểu mẫu và các nút --}}
        <div id="login-box" class="l-modal-box">

            {{-- Nút biểu tượng chữ X để đóng Modal đăng nhập --}}
            <button id="close-login" type="button" class="l-close-btn" aria-label="Đóng">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            {{-- Tiêu đề của Modal --}}
            <h2 class="l-title">Đăng Nhập</h2>

            {{-- Biểu mẫu đăng nhập gửi dữ liệu qua phương thức POST tới route xử lý đăng nhập --}}
            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                {{-- Luôn render sẵn, ẩn bằng "hidden" vì form submit qua fetch, JS cần sẵn chỗ để hiện lỗi --}}
                <div id="login-error-alert" class="l-error-alert {{ $errors->has('login_error') ? '' : 'hidden' }}">
                    {{ $errors->first('login_error') }}
                </div>

                {{-- Ô nhập địa chỉ Email --}}
                <div class="l-form-group">
                    <label class="l-label">Email</label>
                    {{-- old('email') giữ lại email đã nhập nếu submit lỗi, tránh user phải gõ lại --}}
                    <input type="email" name="email" placeholder="Nhập địa chỉ email"
                        class="l-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    {{-- Hiển thị lỗi validate chi tiết của trường Email dưới ô nhập --}}
                    @error('email')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ô nhập Mật khẩu --}}
                <div class="l-form-group">
                    <label class="l-label">Mật khẩu</label>
                    <div class="l-input-wrap">
                        <input type="password" id="login-password" name="password" placeholder="Nhập mật khẩu"
                            class="l-input has-password-toggle @error('password') is-invalid @enderror" required>
                        {{-- Nút biểu tượng mắt nhấp chuột dùng JS toggle --}}
                        <button type="button" class="toggle-password toggle-password-visibility"
                            data-target="login-password" aria-label="Hiện/ẩn mật khẩu">
                            <i class="fa-regular fa-eye text-base"></i>
                        </button>
                    </div>
                    {{-- Hiển thị lỗi validate chi tiết của trường Mật khẩu --}}
                    @error('password')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Nút chuyển hướng sang Popup Quên mật khẩu --}}
                <div class="l-forgot-wrap">
                    <a href="#" id="switch-to-forgot" class="l-forgot-link">Quên mật khẩu?</a>
                </div>

                {{-- Nút submit gửi form đăng nhập --}}
                <button type="submit" class="l-submit-btn">Đăng Nhập</button>
            </form>

            {{-- Thanh phân cách giữa đăng nhập thường và đăng nhập bên thứ 3 --}}
            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc tiếp tục với</span>
                <div class="l-divider-line"></div>
            </div>

            {{-- Nút đăng nhập nhanh bằng tài khoản Google --}}
            <a href="{{ route('auth.google') }}" class="l-google-btn">
                {{-- Biểu tượng Google dạng SVG vẽ bằng code đồ họa vector --}}
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
                Đăng nhập với Google
            </a>

            {{-- Chuyển đổi nhanh sang Modal Đăng ký nếu chưa có tài khoản --}}
            <div class="l-footer">
                Chưa có tài khoản? <a href="#" id="switch-to-register">Đăng ký ngay</a>
            </div>

        </div>
    </div>
</div>

<script>
    // Tự bung modal ngay khi tải trang nếu Backend đánh dấu data-show-login="true", vd login lỗi
    document.addEventListener('DOMContentLoaded', function() {
        const loginModal = document.getElementById('login-modal');
        if (loginModal && loginModal.getAttribute('data-show-login') === 'true') {
            loginModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Khóa cuộn trang nền trong lúc modal đang mở
        }
    });

    // Event delegation trên document để bắt được cả nút "#login-btn" nằm ở file layout khác
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('login-modal');
        if (!modal) return; // Trang hiện tại không có modal đăng nhập -> bỏ qua, không xử lý gì thêm

        // Bấm nút/link mở modal đăng nhập
        const loginBtn = e.target.closest('#login-btn');
        if (loginBtn) {
            e.preventDefault();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            return;
        }

        // Bấm nút "X" để đóng modal
        const closeBtn = e.target.closest('#close-login');
        if (closeBtn) {
            e.preventDefault();
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Trả lại khả năng cuộn trang nền như bình thường
            return;
        }

        // Bấm ra ngoài vùng modal cũng coi như đóng modal
        const overlay = e.target.closest('#login-overlay');
        if (overlay) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            return;
        }

        // Bấm link "Đăng ký ngay" ở cuối modal -> đóng modal Đăng nhập, mở modal Đăng ký thay vào
        const switchToRegisterBtn = e.target.closest('#switch-to-register');
        if (switchToRegisterBtn) {
            e.preventDefault();
            modal.style.display = 'none';
            const registerModal = document.getElementById('register-modal');
            if (registerModal) {
                registerModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            return;
        }

        // Bấm link "Quên mật khẩu?" trong form -> đóng modal Đăng nhập, mở modal Quên mật khẩu thay vào
        const switchToForgotBtn = e.target.closest('#switch-to-forgot');
        if (switchToForgotBtn) {
            e.preventDefault();
            modal.style.display = 'none';
            const forgotModal = document.getElementById('forgot-modal');
            if (forgotModal) {
                forgotModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            return;
        }
    });
</script>
