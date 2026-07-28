{{-- Khung Modal bao quanh màn hình xác thực OTP (Mặc định được ẩn bằng CSS, hiển thị khi cần thiết qua JS) --}}
<div id="otp-modal" data-show-otp="{{ (session('show_otp') || $errors->has('otp') || $errors->has('otp_error') || session('verify_email')) ? 'true' : 'false' }}">
    
    {{-- Lớp nền tối mờ phía sau Modal (Overlay) để ngăn người dùng tương tác với trang chính phía sau --}}
    <div id="otp-overlay"></div>

    {{-- Khung căn giữa màn hình cho nội dung Modal --}}
    <div class="l-modal-wrapper">
        
        {{-- Hộp xác thực OTP chính chứa biểu mẫu nhập mã và bộ đếm ngược --}}
        <div id="otp-box" class="l-modal-box">
            
            {{-- Nút biểu tượng chữ X để đóng Modal OTP khi cần --}}
            <button id="close-otp" type="button" class="l-close-btn" aria-label="Đóng">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            {{-- Phần hiển thị biểu tượng xác thực (Khiên bảo mật kèm biểu tượng phong bì thư) --}}
            <div class="otp-icon-wrap">
                {{-- Biểu tượng chiếc khiên xanh lá cây --}}
                <div class="otp-icon-bg">
                    <svg width="40" height="40" fill="none" stroke="#1e4a38" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                {{-- Huy hiệu nhỏ hình chiếc phong bì thư ở góc phải phía dưới --}}
                <div class="otp-icon-badge">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>

            {{-- Tiêu đề và nội dung hướng dẫn của Modal --}}
            <h2 class="otp-main-title">Kiểm tra Email</h2>
            <p class="otp-desc">
                Nhập mã OTP đã được gửi đến<br>
                {{-- Hiển thị Email đang chờ xác thực được lưu trong Session của Laravel, mặc định là "email" --}}
                email của bạn: <span class="otp-email" id="otp-email-display">{{ session('verify_email', 'email') }}</span>
            </p>

            {{-- Biểu mẫu gửi mã OTP xác nhận, thực hiện qua phương thức POST gửi đến Route verify.otp.post --}}
            <form action="{{ route('verify.otp.post') }}" method="POST">
                {{-- Token bảo mật CSRF bắt buộc của Laravel nhằm chống tấn công giả mạo --}}
                @csrf
                
                {{-- Hiển thị thông báo lỗi nếu xảy ra sai sót khi xác nhận mã OTP. Luôn render sẵn (ẩn
                mặc định) vì form submit qua fetch (xem verify-otp.js), JS cần chỗ có sẵn để tự hiện lỗi. --}}
                @php
                    $otpErrorMsg = $errors->has('otp_error')
                        ? $errors->first('otp_error')
                        : (($errors->has('otp') || $errors->has('otp.*')) ? 'Vui lòng nhập đủ 4 số OTP.' : '');
                @endphp
                <div id="otp-error-alert" class="otp-error {{ $otpErrorMsg ? '' : 'hidden' }}">{{ $otpErrorMsg }}</div>

                {{-- Hộp chứa 4 ô nhập số OTP tương ứng với 4 chữ số mã xác thực --}}
                <div class="otp-inputs">
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                </div>

                {{-- Bộ phận đếm ngược thời gian và nút kích hoạt gửi lại mã OTP --}}
                <div class="otp-resend">
                    {{-- Dòng chữ đếm ngược số giây --}}
                    <p class="otp-timer-text" id="timer-text">Gửi lại mã sau <span id="timer" class="otp-timer">00:58</span></p>
                    {{-- Đường link gửi yêu cầu gửi lại OTP, ban đầu sẽ bị khóa bằng CSS (pointer-events: none) và kích hoạt sau khi đếm ngược xong --}}
                    <a href="{{ route('resend.otp') }}" class="otp-resend-btn" id="resend-btn">Gửi lại</a>
                </div>

                {{-- Nút Xác nhận gửi biểu mẫu đi --}}
                <button type="submit" class="otp-submit">Xác nhận</button>
            </form>
        </div>
    </div>
</div>

{{-- Nhúng tệp tin Script JS xử lý tương tác nhập mã OTP và đếm ngược thời gian --}}
<script src="{{ asset('js/frontend/auth/verify-otp.js') }}"></script>
