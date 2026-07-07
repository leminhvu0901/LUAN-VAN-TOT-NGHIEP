{{-- Khung Modal bao quanh màn hình Quên mật khẩu (Mặc định được ẩn bằng CSS) --}}
<div id="forgot-modal">

    {{-- Lớp nền tối mờ phía sau Modal (Overlay) --}}
    <div id="forgot-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">

        {{-- Hộp quên mật khẩu chính chứa biểu mẫu và các nút --}}
        <div id="forgot-box" class="l-modal-box">

            {{-- Nút biểu tượng mũi tên quay lại màn hình đăng nhập --}}
            <button id="switch-to-login-from-forgot" type="button" class="l-back-btn" aria-label="Quay lại Đăng nhập">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </button>

            {{-- Nút biểu tượng chữ X để đóng Modal --}}
            <button id="close-forgot" type="button" class="l-close-btn" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            {{-- Biểu tượng hình ảnh của Modal Quên mật khẩu --}}
            <div class="l-forgot-icon-wrap">
                <img src="{{ asset('images/icons/quenmk.png') }}" alt="Quên mật khẩu" class="l-forgot-icon" />
            </div>

            {{-- Tiêu đề của Modal --}}
            <h2 class="l-title">Quên mật khẩu</h2>

            {{-- Dòng mô tả ngắn hướng dẫn cho người dùng --}}
            <p class="l-forgot-desc">
                Vui lòng nhập email để nhận mã khôi phục mật khẩu.
            </p>

            {{-- Biểu mẫu gửi email yêu cầu đặt lại mật khẩu, gửi dữ liệu POST tới route xử lý --}}
            <form action="{{ route('forgot-password.post') }}" method="post" novalidate>
                {{-- Token bảo mật CSRF bắt buộc của Laravel để chống tấn công giả mạo yêu cầu --}}
                @csrf
                
                {{-- Hiển thị thông báo lỗi chung nếu việc gửi yêu cầu quên mật khẩu bị lỗi trên server --}}
                @error('forgot_error')
                    <div class="l-error-alert">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Ô nhập Email để khôi phục tài khoản --}}
                <div class="l-form-group">
                    <label for="recoveryContact" class="l-label">Email</label>
                    {{-- old('recovery_contact') giữ lại email đã nhập nếu submit form gặp lỗi để user không phải gõ lại --}}
                    <input id="recoveryContact" name="recovery_contact" type="text" placeholder="Nhập email của bạn" class="l-input" required value="{{ old('recovery_contact') }}" />
                    {{-- Hiển thị thông báo lỗi xác thực riêng của trường email --}}
                    @error('recovery_contact')
                        <div class="l-field-error-large">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Nút submit gửi yêu cầu lấy lại mật khẩu --}}
                <button type="submit" class="l-submit-btn">
                    Gửi mã xác nhận
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M5 12h14" />
                        <path d="M13 6l6 6l-6 6" />
                    </svg>
                </button>
            </form>

            {{-- Thanh phân cách giữa form chính và footer --}}
            <div class="l-divider">
                <div class="l-divider-line"></div>
                <span class="l-divider-text">Hoặc</span>
                <div class="l-divider-line"></div>
            </div>

            {{-- Nút chuyển đổi nhanh sang popup Đăng ký nếu chưa có tài khoản --}}
            <div class="l-footer">
                Chưa có tài khoản? <a href="#" id="switch-to-register-from-forgot">Đăng ký ngay</a>
            </div>

        </div>
    </div>
</div>

{{-- Nạp script điều khiển ẩn hiện và chuyển đổi popup quên mật khẩu --}}
<script src="{{ asset('js/frontend/forgot-password.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        {{-- Nếu Session có cờ báo hiển thị trực tiếp popup quên mật khẩu (sau khi redirect) --}}
        @if(session('show_forgot'))
            const forgotModal = document.getElementById('forgot-modal');
            if (forgotModal) {
                {{-- Hiển thị Modal ngay lập tức --}}
                forgotModal.style.display = 'block';
                {{-- Khóa thanh cuộn màn hình trang chủ phía dưới --}}
                document.body.style.overflow = 'hidden';
            }
        @endif
    });
</script>
