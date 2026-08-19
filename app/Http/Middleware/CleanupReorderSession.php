<?php

namespace App\Http\Middleware;

use App\Models\CartItem;
use App\Models\CartItemTopping;
use Closure;
use Illuminate\Http\Request;

class CleanupReorderSession
{
    // Tự động dọn dẹp các sản phẩm tạm của phiên "Mua lại" khi khách rời khỏi trang thanh toán
    public function handle(Request $request, Closure $next)
    {
        // Tuyệt đối KHÔNG xóa trên:
        // 1. Các request AJAX, JSON, API (như tải tỉnh thành /administrative/*, tính cước /checkout/*, toggle favorite...)
        // 2. Các request không phải GET (POST, PUT, DELETE...)
        // 3. Các trang thuộc quy trình thanh toán / kiểm tra đơn: checkout*, administrative*, payment*
        $isSafeRequest = $request->ajax()
            || $request->expectsJson()
            || !$request->isMethod('GET')
            || $request->is('checkout*')
            || $request->is('administrative*')
            || $request->is('payment*');

        if (!$isSafeRequest) {
            $reorderIds = session('reorder_cart_item_ids');
            if (!empty($reorderIds) && is_array($reorderIds)) {
                CartItemTopping::query()->whereIn('cart_item_id', $reorderIds)->delete();
                CartItem::query()->whereIn('id', $reorderIds)->delete();
                session()->forget('reorder_cart_item_ids');

                $selected = session('selected_cart_item_ids');
                if ($selected === $reorderIds) {
                    session()->forget('selected_cart_item_ids');
                }
            }
        }

        return $next($request);
    }
}
