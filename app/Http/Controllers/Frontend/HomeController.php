<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    // Hiển thị trang chủ với danh sách banner và danh mục sản phẩm
    public function index()
    {
        $now = now();

        // Số liệu thật cho khối thống kê ở hero banner (thay vì số cố định)
        $productCount = Product::where('is_active', 1)->count();

        // Làm tròn LÊN đến 1 chữ số thập phân (vd: 4.61 -> 4.7) để điểm hiển thị luôn có phần thập phân
        $avgRating = ceil((float) (Review::where('is_visible', 1)->avg('rating') ?? 5) * 10) / 10;

        $avgDeliveryMinutes = (int) round((float) (Order::where('delivery_type', 'delivery')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_minutes')
            ->value('avg_minutes') ?? 30));

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

        // Lấy danh mục đang active, kèm số lượng sản phẩm trong mỗi danh mục
        $categories = Category::query()
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(products.id) as product_count'))
            ->where('categories.is_active', 1)
            ->groupBy('categories.id', 'categories.name', 'categories.display_order')
            ->orderBy('categories.display_order')
            ->get();

        return view('frontend.home', compact('banners', 'categories', 'productCount', 'avgRating', 'avgDeliveryMinutes'));
    }
}
