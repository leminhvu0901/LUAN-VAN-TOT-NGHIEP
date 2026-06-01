<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
</head>
<body class="min-h-screen bg-gray-100 font-['Inter'] text-gray-700">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-10">
        <section class="w-full rounded-2xl bg-white p-7 shadow-xl sm:p-8">
            <div class="login-modal-header">
                <h1 id="loginModalLabel" class="text-2xl font-bold text-gray-900">Đăng Nhập</h1>
                <a href="{{ url('/') }}" class="login-modal-close" aria-label="Đóng">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M18 6l-12 12" />
                        <path d="M6 6l12 12" />
                    </svg>
                </a>
            </div>

            <form class="login-modal-form needs-validation" action="#" method="post" novalidate>
                <div class="login-field">
                    <label for="loginIdentity">Email hoặc Số điện thoại</label>
                    <input id="loginIdentity" name="identity" type="text" placeholder="Nhập địa chỉ email hoặc số điện thoại." autocomplete="username" required />
                </div>

                <div class="login-field">
                    <label for="loginPassword">Mật khẩu</label>
                    <input id="loginPassword" name="password" type="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required />
                </div>

                <div class="login-forgot-row">
                    <a href="{{ route('forgot-password') }}">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="login-submit">Đăng Nhập</button>
            </form>

            <div class="login-separator">
                <span>Hoặc tiếp tục với</span>
            </div>

            <div class="login-socials">
                <button type="button" class="login-social-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9c.87-2.6 3.3-4.52 6.16-4.52z" />
                    </svg>
                    <span>Đăng nhập với Google</span>
                </button>
            </div>

            <div class="login-register">
                <span>Chưa có tài khoản?</span>
                <a href="{{ route('register') }}">Đăng ký</a>
            </div>
        </section>
    </main>
</body>
</html>
