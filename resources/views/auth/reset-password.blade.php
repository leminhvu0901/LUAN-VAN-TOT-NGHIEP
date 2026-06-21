<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; margin: 0; padding: 0; }
        .reset-container {
            max-width: 28rem;
            margin: 0 auto;
            min-height: 100vh;
            background-color: #f9fafb;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .reset-header { display: flex; align-items: center; padding: 1.5rem 1rem; position: sticky; top: 0; z-index: 10; }
        .reset-back { color: #064e3b; padding: 0.5rem; text-decoration: none; }
        .reset-title { flex: 1; text-align: center; font-size: 1.125rem; font-weight: 700; color: #064e3b; padding-right: 2rem; }
        
        .reset-icon-wrap { position: relative; margin-bottom: 2rem; margin-top: 1rem; }
        .reset-icon-bg { width: 6rem; height: 6rem; background-color: #d1fae5; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
        .reset-icon-badge { position: absolute; bottom: 0; right: 50%; transform: translateX(2.5rem); width: 2rem; height: 2rem; background-color: #1e4a38; border-radius: 9999px; border: 2px solid #f9fafb; display: flex; align-items: center; justify-content: center; }
        
        .reset-main-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; text-align: center; }
        .reset-desc { text-align: center; color: #4b5563; font-size: 0.875rem; margin-bottom: 2.5rem; line-height: 1.5; padding: 0 1rem; }
        
        .reset-form { padding: 0 1.5rem; display: flex; flex-direction: column; flex: 1; }
        .form-group { margin-bottom: 1.25rem; position: relative; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .form-input { width: 100%; box-sizing: border-box; padding: 0.875rem 1rem; border: 1px solid #d1d5db; border-radius: 0.75rem; font-size: 0.9375rem; transition: all 0.2s; background-color: white; }
        .form-input:focus { outline: none; border-color: #1e4a38; box-shadow: 0 0 0 1px #1e4a38; }
        .toggle-password { position: absolute; right: 1rem; top: 2.3rem; cursor: pointer; color: #6b7280; display: flex; align-items: center; justify-content: center; background: none; border: none; padding: 0; }
        
        .reset-submit-wrap { margin-top: auto; padding-bottom: 2rem; padding-top: 2rem;}
        .reset-submit { width: 100%; background-color: #1e4a38; border: none; color: white; font-weight: 700; font-size: 0.9375rem; padding: 1rem; border-radius: 0.75rem; cursor: pointer; transition: background-color 0.2s; }
        .reset-submit:hover { background-color: #153628; }
        
        .error-message { color: #dc2626; font-size: 0.8125rem; margin-top: 0.5rem; display: block; }
    </style>
</head>
<body>

<div class="reset-container">
    {{-- Header --}}
    <div class="reset-header">
        <a href="/" class="reset-back">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <h1 class="reset-title">Đặt lại mật khẩu</h1>
    </div>

    {{-- Icon --}}
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

    {{-- Title & Desc --}}
    <h2 class="reset-main-title">Mật khẩu mới</h2>
    <p class="reset-desc">
        Mật khẩu của bạn phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
    </p>

    <form action="{{ route('reset.password.post') }}" method="POST" class="reset-form">
        @csrf
        
        <div class="form-group">
            <label class="form-label" for="password">Mật khẩu mới</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Nhập mật khẩu mới" required>
            <button type="button" class="toggle-password" onclick="toggleVisibility('password')">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </button>
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Nhập lại mật khẩu mới" required>
            <button type="button" class="toggle-password" onclick="toggleVisibility('password_confirmation')">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </button>
            @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="reset-submit-wrap">
            <button type="submit" class="reset-submit">Đặt lại mật khẩu</button>
        </div>
    </form>
</div>

<script src="{{ asset('js/frontend/reset-password.js') }}"></script>

</body>
</html>
