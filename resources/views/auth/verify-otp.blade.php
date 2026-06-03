<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; margin: 0; padding: 0; }
        .otp-container {
            max-width: 28rem;
            margin: 0 auto;
            min-height: 100vh;
            background-color: #f9fafb;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .otp-header { display: flex; align-items: center; padding: 1.5rem 1rem; position: sticky; top: 0; z-index: 10; }
        .otp-back { color: #064e3b; padding: 0.5rem; text-decoration: none; }
        .otp-title { flex: 1; text-align: center; font-size: 1.125rem; font-weight: 700; color: #064e3b; padding-right: 2rem; }
        
        .otp-icon-wrap { position: relative; margin-bottom: 2rem; margin-top: 1rem; }
        .otp-icon-bg { width: 6rem; height: 6rem; background-color: #d1fae5; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
        .otp-icon-badge { position: absolute; bottom: 0; right: 50%; transform: translateX(2.5rem); width: 2rem; height: 2rem; background-color: #1e4a38; border-radius: 9999px; border: 2px solid #f9fafb; display: flex; align-items: center; justify-content: center; }
        
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
        
        .otp-submit-wrap { margin-top: auto; width: 100%; padding: 0 1.5rem 2rem 1.5rem; box-sizing: border-box; }
        .otp-submit { width: 100%; background-color: #1e4a38; border: none; color: white; font-weight: 700; font-size: 0.9375rem; padding: 1rem; border-radius: 0.75rem; cursor: pointer; transition: background-color 0.2s; }
        .otp-submit:hover { background-color: #153628; }
        
        .otp-error { width: calc(100% - 3rem); background-color: #fee2e2; color: #dc2626; font-size: 0.875rem; padding: 0.75rem; border-radius: 0.75rem; margin: 0 auto 1rem auto; text-align: center; font-weight: 500; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="otp-container">
    {{-- Header --}}
    <div class="otp-header">
        <a href="/" class="otp-back">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <h1 class="otp-title">Xác minh OTP</h1>
    </div>

    {{-- Icon --}}
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

    {{-- Title & Desc --}}
    <h2 class="otp-main-title">Kiểm tra Email</h2>
    <p class="otp-desc">
        Nhập mã OTP đã được gửi đến<br>
        email của bạn: <span class="otp-email">{{ $email }}</span>
    </p>

    <form action="{{ route('verify.otp.post') }}" method="POST" style="display:flex; flex-direction:column; flex:1;">
        @csrf
        
        {{-- Error Message --}}
        @if($errors->has('otp_error'))
            <div class="otp-error">{{ $errors->first('otp_error') }}</div>
        @endif
        @if($errors->has('otp') || $errors->has('otp.*'))
            <div class="otp-error">Vui lòng nhập đủ 4 số OTP.</div>
        @endif

        {{-- OTP Inputs --}}
        <div class="otp-inputs">
            <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
            <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
            <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
            <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" required>
        </div>

        {{-- Resend --}}
        <div class="otp-resend">
            <p class="otp-timer-text" id="timer-text">Gửi lại mã sau <span id="timer" class="otp-timer">00:58</span></p>
            <a href="{{ route('resend.otp') }}" class="otp-resend-btn" id="resend-btn">Gửi lại</a>
        </div>

        {{-- Submit Button --}}
        <div class="otp-submit-wrap">
            <button type="submit" class="otp-submit">Xác nhận</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 4);
            if (pastedData) {
                for (let i = 0; i < pastedData.length; i++) {
                    if (inputs[i]) {
                        inputs[i].value = pastedData[i];
                        if (i < 3) inputs[i + 1].focus();
                    }
                }
            }
        });
    });

    if(inputs.length > 0) { setTimeout(() => inputs[0].focus(), 300); }

    let timeLeft = 58;
    const timerEl = document.getElementById('timer');
    const timerText = document.getElementById('timer-text');
    const resendBtn = document.getElementById('resend-btn');

    const countdown = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            timerText.style.display = 'none';
            resendBtn.classList.add('active');
        } else {
            const seconds = timeLeft < 10 ? '0' + timeLeft : timeLeft;
            timerEl.textContent = '00:' + seconds;
        }
    }, 1000);
});
</script>

</body>
</html>
