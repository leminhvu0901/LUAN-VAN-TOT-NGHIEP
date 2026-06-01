<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
</head>
<body class="min-h-screen bg-gray-100 font-['Inter'] text-gray-700">
    <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-10">
        <section class="w-full rounded-2xl bg-white p-7 shadow-xl sm:p-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Đăng ký</h1>
                <a href="{{ url('/') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Đóng">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M18 6l-12 12" />
                        <path d="M6 6l12 12" />
                    </svg>
                </a>
            </div>

            <form class="space-y-4" action="#" method="post" novalidate>
                <div>
                    <label for="fullName" class="mb-2 block text-sm font-semibold text-gray-800">Họ và tên</label>
                    <input id="fullName" name="full_name" type="text" placeholder="Nhập tên của bạn" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" required />
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-gray-800">Email hoặc số điện thoại</label>
                    <input id="email" name="email" type="text" placeholder="Nhập email hoặc số điện thoại" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" required />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-gray-800">Mật khẩu</label>
                    <input id="password" name="password" type="password" placeholder="Nhập mật khẩu" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" required />
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-gray-800">Xác nhận mật khẩu</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Nhập lại mật khẩu" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" required />
                </div>

                <p class="text-sm text-gray-500">
                    Bằng việc đăng ký, bạn đồng ý với
                    <a href="#" class="font-semibold text-green-600 hover:text-green-700">Điều khoản dịch vụ</a>
                    và
                    <a href="#" class="font-semibold text-green-600 hover:text-green-700">Chính sách bảo mật</a>.
                </p>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 font-semibold text-white transition hover:bg-green-700">
                    Đăng ký
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-600">
                Đã có tài khoản?
                <a href="{{ route('login')}}" class="font-semibold text-green-600 hover:text-green-700">Đăng nhập</a>
            </p>
        </section>
    </main>
</body>
</html>
