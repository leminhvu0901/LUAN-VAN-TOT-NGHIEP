<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Khởi tạo ứng dụng Laravel và chỉ định thư mục gốc của dự án
return Application::configure(basePath: dirname(__DIR__))
    // 1. Cấu hình các luồng truy cập (Routing)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',          // Khai báo file xử lý các URL trên trình duyệt web
        commands: __DIR__ . '/../routes/console.php', // Khai báo file xử lý các lệnh từ cửa sổ dòng lệnh (Terminal)
        health: '/up',                                // Endpoint mặc định dùng để kiểm tra server có đang hoạt động tốt không
    )
    // 2. Cấu hình các lớp rào chắn (Middleware) - Xử lý hoặc lọc dữ liệu trước khi đi vào hệ thống
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (và các nền tảng tương tự) kết thúc mã hóa TLS ở proxy biên rồi chuyển tiếp request
        // vào container bằng HTTP thường, kèm header X-Forwarded-Proto: https. Không khai báo tin cậy
        // proxy này thì Laravel tưởng request gốc là http -> asset()/url()/redirect() sinh ra URL
        // http:// trong khi trình duyệt đang ở https://, bị chặn vì lỗi mixed content một cách IM LẶNG
        // -> toàn bộ CSS/JS không tải được, trang hiện thô không có giao diện.
        // Dùng '*' vì địa chỉ IP proxy biên của Railway không cố định để liệt kê sẵn.
        $middleware->trustProxies(at: '*');

        // Loại trừ kiểm tra CSRF cho các đường dẫn test trên Postman và MoMo IPN
        $middleware->validateCsrfTokens(except: [
            'checkout/momo/ipn',
            'login',
            'logout',
            'checkout',
            'checkout/*',
            'profile/*',
            'cart/*',
            'staff/*',
            'admin/*',
        ]);
    })
    // 3. Trung tâm xử lý Lỗi/Ngoại lệ (Exceptions) - Xử lý các tình huống hệ thống bị crash hoặc dính lỗi
    ->withExceptions(function (Exceptions $exceptions): void {
        // Bắt lỗi khi người dùng tải file lên quá nặng (vượt quá cấu hình post_max_size của PHP)
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            // Nếu gửi request dưới dạng API (JSON)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tổng dung lượng file quá lớn. Vui lòng giảm bớt kích thước ảnh.'], 413);
            }
            // Nếu gửi bằng Form qua giao diện Web bình thường: Quay lại trang cũ, giữ lại dữ liệu đã nhập, báo lỗi đỏ
            return redirect()->back()->withInput()->with('error', 'Tổng dung lượng các file tải lên quá lớn (Vượt quá cấu hình máy chủ). Vui lòng chọn ảnh có dung lượng nhỏ hơn (Tối đa 2MB/ảnh) và thử lại.');
        });
    })->create(); // Chính thức khởi tạo và trả ra đối tượng Application hoàn chỉnh
