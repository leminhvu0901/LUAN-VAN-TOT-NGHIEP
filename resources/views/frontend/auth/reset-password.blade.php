{{-- Khung Modal Đặt lại mật khẩu (Mặc định được ẩn bằng CSS, tự động hiển thị nếu Session đánh dấu
đã xác thực OTP quên mật khẩu thành công). Trước đây đây là 1 TRANG RIÊNG (route GET /reset-password)
điều hướng cả trang - giờ chuyển thành modal giống hệt Đăng nhập/Đăng ký để không phải rời trang. --}}
{{-- Chỉ dựa vào session('can_reset_password') để quyết định tự mở modal - KHÔNG dùng $errors->has('password')
vì tên trường 'password' bị trùng với modal Đăng nhập/Đăng ký, dùng chung sẽ khiến modal này tự bật sai
lúc modal khác báo lỗi mật khẩu. --}}
<div id="reset-password-modal" data-show-reset-password="{{ session('can_reset_password') ? 'true' : 'false' }}">

    {{-- Lớp nền tối mờ phía sau Modal (Overlay) --}}
    <div id="reset-password-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">

        {{-- Hộp đặt lại mật khẩu chính chứa biểu mẫu --}}
        <div id="reset-password-box" class="l-modal-box">

            {{-- Nút biểu tượng chữ X để đóng Modal --}}
            <button id="close-reset-password" type="button" class="l-close-btn" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            {{-- Icon ổ khóa kèm dấu tích xanh biểu thị bước xác thực bảo mật --}}
            <div class="reset-icon-wrap">
                <div class="reset-icon-bg">
                    <svg width="40" height="40" fill="none" stroke="#1e4a38" stroke-width="2.5" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0110 0v4"></path>
                    </svg>
                </div>
                <div class="reset-icon-badge">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            {{-- Tiêu đề chính của form và phần văn bản mô tả quy định độ phức tạp mật khẩu --}}
            <h2 class="reset-main-title">Mật khẩu mới</h2>
            <p class="reset-desc">
                Mật khẩu của bạn phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
            </p>

            {{-- Biểu mẫu gửi yêu cầu mật khẩu mới lên server --}}
            <form action="{{ route('reset.password.post') }}" method="post" novalidate>
                {{-- Token bảo mật tránh tấn công giả mạo yêu cầu chéo CSRF --}}
                @csrf

                {{-- Hiển thị thông báo lỗi chung (vd phiên xác thực hết hạn, không tìm thấy tài khoản)
                khi server trả về lỗi. Luôn render sẵn (ẩn mặc định) vì form submit qua fetch (xem
                reset-password.js), JS cần chỗ có sẵn để tự hiện lỗi. --}}
                <div id="reset-password-error-alert" class="l-error-alert {{ $errors->has('reset_error') ? '' : 'hidden' }}">
                    {{ $errors->first('reset_error') }}
                </div>

                {{-- Nhóm nhập mật khẩu mới --}}
                <div class="l-form-group">
                    <label class="l-label" for="reset_password">Mật khẩu mới</label>
                    <div class="l-input-wrap">
                        <input type="password" id="reset_password" name="password" class="l-input has-password-toggle"
                            placeholder="Nhập mật khẩu mới" required>
                        {{-- Nút biểu tượng mắt nhấp chuột dùng JS toggle ẩn/hiện ký tự mật khẩu (dùng chung main.js) --}}
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="reset_password" aria-label="Hiện/ẩn mật khẩu">
                            <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                        </button>
                    </div>
                    @error('password')
                        <div class="l-field-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Nhóm nhập lại mật khẩu để xác nhận trùng khớp --}}
                <div class="l-form-group">
                    <label class="l-label" for="reset_password_confirmation">Xác nhận mật khẩu</label>
                    <div class="l-input-wrap">
                        <input type="password" id="reset_password_confirmation" name="password_confirmation" class="l-input has-password-toggle"
                            placeholder="Nhập lại mật khẩu mới" required>
                        <button type="button" class="toggle-password toggle-password-visibility" data-target="reset_password_confirmation" aria-label="Hiện/ẩn mật khẩu">
                            <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                        </button>
                    </div>
                </div>

                {{-- Nút submit gửi biểu mẫu --}}
                <div class="reset-submit-wrap">
                    <button type="submit" class="reset-submit">Đặt lại mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Nạp script điều khiển ẩn hiện Modal + submit form qua fetch --}}
<script src="{{ asset('js/frontend/auth/reset-password.js') }}?v={{ filemtime(public_path('js/frontend/auth/reset-password.js')) }}"></script>
