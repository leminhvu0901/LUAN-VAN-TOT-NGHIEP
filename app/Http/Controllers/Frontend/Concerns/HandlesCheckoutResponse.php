<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Illuminate\Http\Request;

/**
 * Dùng chung cho mọi controller cổng thanh toán online (MoMo, VNPay, ...) — trả lỗi/điều hướng
 * đúng định dạng theo kiểu request: JSON cho fetch() (checkout.js submit qua AJAX) để JS hiện lỗi
 * tại chỗ không cần tải lại trang; redirect cổ điển cho request thường.
 */
trait HandlesCheckoutResponse
{
    /**
     * Trả lỗi đúng định dạng theo kiểu request. JSON 422 cho fetch, redirect-back kèm withInput()
     * (không mất thông tin khách đã nhập) cho request thường.
     */
    private function checkoutError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message)->withInput();
    }

    /**
     * Điều hướng đúng định dạng theo kiểu request — dùng cho các đích đến "thành công" (sang cổng
     * thanh toán, hoặc quay lại trang đăng nhập). AJAX nhận URL để tự window.location.href, request
     * thường nhận redirect thật.
     */
    private function checkoutRedirect(Request $request, string $url)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $url]);
        }
        return redirect()->away($url);
    }
}
