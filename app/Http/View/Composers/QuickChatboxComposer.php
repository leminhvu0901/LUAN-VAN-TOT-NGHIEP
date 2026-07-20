<?php

namespace App\Http\View\Composers;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Cấp dữ liệu cho component chatbox trả lời nhanh (resources/views/frontend/components/quick-chatbox.blade.php).
// Chạy mỗi khi view đó được render, trên MỌI trang frontend — không có route/endpoint riêng, không AJAX.
class QuickChatboxComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'quickChatFeaturedProducts' => $this->getFeaturedProducts(),
            'quickChatPromotions' => $this->getActivePromotions(),
            'quickChatBusinessHours' => [
                'open' => Setting::getValue('store_open_time', config('quick_chat.defaults.open_time')),
                'close' => Setting::getValue('store_close_time', config('quick_chat.defaults.close_time')),
            ],
            'quickChatContact' => [
                'phone' => Setting::getValue('store_phone', config('quick_chat.defaults.phone')),
                'email' => Setting::getValue('store_email', config('quick_chat.defaults.email')),
                'address' => Setting::getValue('store_address', config('quick_chat.defaults.address')),
                'map_url' => Setting::getValue('store_map_url', null),
            ],
            'quickChatPaymentMethods' => [
                'cod_enabled' => (bool) Setting::getValue('cod_enabled', true),
                'momo_enabled' => (bool) Setting::getValue('momo_enabled', false),
            ],
        ]);
    }

    // Không có cột "sản phẩm nổi bật" trong bảng products — dùng tiêu chí bán chạy nhất (tổng số lượng
    // đã bán) làm proxy hợp lý, tái sử dụng đúng subquery mà ProductController@show đang dùng.
    private function getFeaturedProducts()
    {
        return Product::query()
            ->select(
                'products.*',
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin(
                DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold
                          FROM order_items oi
                          JOIN orders o ON o.id = oi.order_id
                          WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL
                          GROUP BY oi.product_id) as o"),
                'products.id',
                '=',
                'o.product_id'
            )
            ->where('products.is_active', 1)
            ->orderByDesc('total_sold')
            ->limit(4)
            ->get();
    }

    // Chỉ khuyến mãi đang bật, còn hiệu lực theo thời gian (điều kiện y hệt PromotionController::index),
    // và áp dụng được cho khách hàng online (applies_to = all|delivery) — không lộ mã chỉ dành tại quầy.
    private function getActivePromotions()
    {
        $now = now();

        return Promotion::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->whereIn('applies_to', ['all', 'delivery'])
            ->limit(4)
            ->get();
    }
}
