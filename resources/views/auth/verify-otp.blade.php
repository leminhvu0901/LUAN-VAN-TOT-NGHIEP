<style>
    .otp-icon-wrap { position: relative; margin-bottom: 2rem; margin-top: 1rem; }
    .otp-icon-bg { width: 6rem; height: 6rem; background-color: #d1fae5; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
    .otp-icon-badge { position: absolute; bottom: 0; right: 50%; transform: translateX(2.5rem); width: 2rem; height: 2rem; background-color: #1e4a38; border-radius: 9999px; border: 2px solid #ffffff; display: flex; align-items: center; justify-content: center; }
    
    .otp-main-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; text-align: center; }
    .otp-desc { text-align: center; color: #4b5563; font-size: 0.875rem; margin-bottom: 2.5rem; line-height: 1.5; }
    .otp-email { color: #064e3b; font-weight: 600; }
    
    .otp-inputs { display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 2.5rem; width: 100%; }
    .otp-input { width: 3.5rem; height: 4rem; border-radius: 0.75rem; border: 1px solid #d1d5db; text-align: center; font-size: 1.5rem; font-weight: 600; color: #111827; background-color: transparent; transition: all 0.2s; }
    .otp-input:focus { outline: none; border-color: #1e4a38; box-shadow: 0 0 0 1px #1e4a38; }
    
    .otp-resend { text-align: center; margin-bottom: 2.5rem; }
    .otp-timer-text { font-size: 0.8125rem; color: #4b5563; margin-bottom: 0.25rem; }
    .otp-timer { font-weight: 700; color: #111827; }
    .otp-resend-btn { font-size: 0.8125rem; font-weight: 600; color: #9ca3af; text-decoration: none; pointer-events: none; transition: color 0.2s; }
    .otp-resend-btn.active { color: #1e4a38; pointer-events: auto; }
    
    .otp-submit { width: 100%; background-color: #1e4a38; border: none; color: white; font-weight: 700; font-size: 0.9375rem; padding: 1rem; border-radius: 0.75rem; cursor: pointer; transition: background-color 0.2s; }
    .otp-submit:hover { background-color: #153628; }
    
    .otp-error { width: 100%; background-color: #fee2e2; color: #dc2626; font-size: 0.875rem; padding: 0.75rem; border-radius: 0.75rem; margin: 0 auto 1rem auto; text-align: center; font-weight: 500; box-sizing: border-box; }
</style>

<div id="otp-modal" style="display: none;">
    <!-- Overlay -->
    <div id="otp-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>

    <!-- Modal Wrapper -->
    <div class="l-modal-wrapper" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 100%; max-width: 28rem; padding: 1rem;">
        <!-- Box -->
        <div id="otp-box" class="l-modal-box" style="background: #ffffff; border-radius: 1rem; padding: 2rem; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            
            <!-- Close Button -->
            <button id="close-otp" type="button" class="l-close-btn" aria-label="Đóng" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; cursor: pointer; color: #6b7280;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Icon -->
            <div class="otp-icon-wrap">
                <div class="otp-icon-bg">
                    <svg width="40" height="40" fill="none" stroke="#1e4a38" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="otp-icon-badge">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>

            <h2 class="otp-main-title">Kiểm tra Email</h2>
            <p class="otp-desc">
                Nhập mã OTP đã được gửi đến<br>
                email của bạn: <span class="otp-email">{{ session('verify_email', 'email') }}</span>
            </p>

            <form action="{{ route('verify.otp.post') }}" method="POST">
                @csrf
                
                @if($errors->has('otp_error'))
                    <div class="otp-error">{{ $errors->first('otp_error') }}</div>
                @endif
                @if($errors->has('otp') || $errors->has('otp.*'))
                    <div class="otp-error">Vui lòng nhập đủ 4 số OTP.</div>
                @endif

                <div class="otp-inputs">
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
                </div>

                <div class="otp-resend">
                    <p class="otp-timer-text" id="timer-text">Gửi lại mã sau <span id="timer" class="otp-timer">00:58</span></p>
                    <a href="{{ route('resend.otp') }}" class="otp-resend-btn" id="resend-btn">Gửi lại</a>
                </div>

                <button type="submit" class="otp-submit">Xác nhận</button>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/frontend/verify-otp.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(session('show_otp') || $errors->has('otp') || $errors->has('otp_error') || session('verify_email'))
        const otpModal = document.getElementById('otp-modal');
        if (otpModal) {
            otpModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                const firstInput = otpModal.querySelector('.otp-input');
                if(firstInput) firstInput.focus();
            }, 100);
        }
    @endif
});
</script>
