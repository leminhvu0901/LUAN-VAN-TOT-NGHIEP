<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    // Hiển thị trang chủ với danh sách banner và danh mục sản phẩm
    public function index()
    {
        $now = now();

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

        return view('frontend.home', compact('banners', 'categories'));
    }
}
