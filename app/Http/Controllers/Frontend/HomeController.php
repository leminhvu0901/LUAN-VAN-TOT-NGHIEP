<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;

class HomeController extends Controller
{
    // Hiển thị trang chủ 
    public function index()
    {
        $now = now();
        $productCount = Product::where('is_active', 1)->count();
        $avgRating = ceil((float) (Review::where('is_visible', 1)->avg('rating') ?? 5) * 10) / 10;
        $todayVisitCount = (int) Setting::getValue('daily_visits:' . today()->toDateString(), 0);

        // Lấy các banner đang active và còn trong thời gian hiển thị
        $banners = Banner::where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('display_order', 'asc')
            ->get();

        // Lấy danh mục đang active, kèm số lượng sản phẩm ĐANG
        $categories = Category::where('is_active', 1)
            ->withCount(['products as product_count' => function ($query) {
                $query->where('is_active', 1);
            }])
            ->orderBy('display_order')
            ->get();

        return view('frontend.home', compact('banners', 'categories', 'productCount', 'avgRating', 'todayVisitCount'));
    }
}
