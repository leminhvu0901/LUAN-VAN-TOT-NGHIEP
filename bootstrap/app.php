<?php

use App\Http\Middleware\CleanupReorderSession;
use App\Http\Middleware\TrackDailyVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // Cấu hình Routing
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            TrackDailyVisit::class,
            CleanupReorderSession::class,
        ]);

        $middleware->validateCsrfTokens(except: [
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
    // Xử lý Ngoại lệ
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tổng dung lượng file quá lớn. Vui lòng giảm bớt kích thước ảnh.'], 413);
            }

            return redirect()->back()->withInput()->with('error', 'Tổng dung lượng các file tải lên quá lớn (Vượt quá cấu hình máy chủ). Vui lòng chọn ảnh có dung lượng nhỏ hơn (Tối đa 2MB/ảnh) và thử lại.');
        });
    })->create();
