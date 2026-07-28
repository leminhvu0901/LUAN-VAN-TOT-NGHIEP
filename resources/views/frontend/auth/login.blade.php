
{{-- Khung Modal bao quanh màn hình đăng nhập (Mặc định được ẩn bằng CSS, tự động hiển thị nếu có cờ chỉ định từ Backend) --}}
<div id="login-modal" data-show-login="{{ ($errors->has('identity') || $errors->has('password') || $errors->has('login_error') || session('show_login')) ? 'true' : 'false' }}">

    {{-- Lớp nền tối mờ phía sau Modal (Overlay) --}}
    <div id="login-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">

        {{-- Hộp đăng nhập chính chứa biểu mẫu và các nút --}}
        <div id="login-box" class="l-modal-box">

            {{-- Nút biểu tượng chữ X để đóng Modal đăng nhập --}}
            <button id="close-login" type="button" class="l-close-btn" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            {{-- Tiêu đề của Modal --}}
            <h2 class="l-title">Đăng Nhập</h2>

            {{-- Biểu mẫu đăng nhập gửi dữ liệu qua phương thức POST tới route xử lý đăng nhập --}}
            <form action="{{ route('login.post') }}" method="POST">
                {{-- Token bảo mật CSRF bắt buộc của Laravel để chống tấn công giả mạo yêu cầu --}}
                @csrf

                {{-- Hiển thị thông báo lỗi chung nếu đăng nhập thất bại (sai tài khoản/mật khẩu). Luôn
                render sẵn (ẩn mặc định bằng "hidden") thay vì chỉ render khi có lỗi từ server, vì form
                giờ submit qua fetch (xem login.js) -> JS cần 1 chỗ có sẵn để tự hiện lỗi, không tải
                lại trang để server render lại khối này nữa. --}}
                <div id="login-error-alert" class="l-error-alert {{ $errors->has('login_error') ? '' : 'hidden' }}">
                    {{ $errors->first('login_error') }}
                </div>

                {{-- Ô nhập địa chỉ Email --}}
                <div class="l-form-group">
                    <label class="l-label">Email</label>
                    {{-- class "is-invalid" sẽ được kích hoạt nếu Email nhập không đúng định dạng hoặc không hợp lệ --}}
                    {{-- old('email') giữ lại email đã nhập trước đó nếu việc submit bị lỗi, tránh việc user phải gõ lại --}}
                    <input type="email" name="email" placeholder="Nhập địa chỉ email" class="l-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    {{-- Hiển thị lỗi validate chi tiết của trường Email dưới ô nhập --}}
                    @error('email')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Ô nhập Mật khẩu --}}
                <div class="l-form-group">
                    <label class="l-label">Mật khẩu</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu" class="l-input @error('password') is-invalid @enderror" required>
                    {{-- Hiển thị lỗi validate chi tiết của trường Mật khẩu (nếu có) --}}
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
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9c.87-2.6 3.3-4.52 6.16-4.52z" />
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

{{-- Nạp script điều khiển ẩn hiện và chuyển đổi modal đăng nhập/đăng ký --}}
<script src="{{ asset('js/frontend/auth/login.js') }}"></script>
