<?php
namespace App\Http\Controllers\Frontend;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\Topping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController
{
    // HIỂN THỊ CHI TIẾT SẢN PHẨM
    public function show($slug, Request $request)
    {
        // lay het thong tin tu san pham ban chọn
        $product = Product::query()
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.review_count, 0) as review_count'),
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            ->leftJoin(DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL GROUP BY oi.product_id) as o"), 'products.id', '=', 'o.product_id')
            ->where('categories.is_active', 1)
            ->where('products.slug', $slug)
            ->first();

        // Nếu không tìm thấy sản phẩm, quăng lỗi 404 Không tìm
        if (!$product) {
            abort(404);
        }
        // Lấy danh sách các kích cỡ, Sizes của sản phẩm này
        $sizes = ProductSize::query()
            ->where('product_id', $product->id)
            ->orderBy('price_adjustment')
            ->get();

        // Lấy danh sách các loại Topping được liên kết với
        $toppings = Topping::query()
            ->join('product_toppings', 'toppings.id', '=', 'product_toppings.topping_id')
            ->where('product_toppings.product_id', $product->id)
            ->where('toppings.is_available', 1)
            ->select('toppings.*')
            ->get();

        // Lấy danh sách đánh giá mới nhất kèm thông tin người dùng
        $reviewsQuery = Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $product->id)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name', 'users.avatar as user_avatar');

        $reviewRating = $request->query('rating');
        if (in_array($reviewRating, ['1', '2', '3', '4', '5'], true)) {
            $reviewsQuery->where('reviews.rating', (int) $reviewRating);
        }
        if ($request->boolean('has_image')) {
            $reviewsQuery->whereNotNull('reviews.image');
        }
        $isFiltered = $request->filled('rating') || $request->boolean('has_image');

        $reviews = $reviewsQuery->orderBy('reviews.created_at', 'desc')
            ->paginate(self::REVIEWS_PER_PAGE)
            ->withQueryString();

        // Phân phối điểm số đánh giá đếm xem có bao nhiêu
        $ratingDistribution = Review::query()
            ->where('product_id', $product->id)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Số đánh giá có kèm hình ảnh, dùng cho nút lọc "Có
        $hasImageCount = Review::query()
            ->where('product_id', $product->id)
            ->where('is_visible', 1)
            ->whereNotNull('image')
            ->count();

        // Tìm các sản phẩm liên quan cùng danh mục, loại trừ
        $relatedProducts = Product::query()
            ->select(
                'products.*',
                DB::raw('COALESCE(r2.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(o2.total_sold, 0) as total_sold')
            )
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r2'), 'products.id', '=', 'r2.product_id')
            ->leftJoin(DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL GROUP BY oi.product_id) as o2"), 'products.id', '=', 'o2.product_id')
            ->where('products.category_id', $product->category_id)
            ->where('products.id', '!=', $product->id)
            ->orderByDesc('products.is_active')
            ->orderByDesc('total_sold')
            ->limit(4)
            ->get();

        // Kiểm tra xem người dùng hiện tại đã lưu sản phẩm
        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = Favorite::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
        }

        // Xác định xem sản phẩm này có phải là Bán chạy
        $top6HotProductIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->where('orders.status', 'completed')
            ->where('orders.payment_status', 'paid')
            ->whereNull('orders.deleted_at')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id')->toArray();

        // Nếu ID sản phẩm nằm trong top 6 bán chạy -> đánh dấu là HOT
        $isHot = in_array($product->id, $top6HotProductIds);
        // Nếu sản phẩm được tạo ra cách đây dưới 15 ngày -> đánh
        $isNew = (Carbon::parse($product->created_at)->diffInDays(now()) <= 15);

        // Trả về view và truyền toàn bộ dữ liệu đã tính toán
        return view('frontend.products.show', compact(
            'product',
            'sizes',
            'toppings',
            'reviews',
            'ratingDistribution',
            'hasImageCount',
            'relatedProducts',
            'isFavorite',
            'isHot',
            'isNew',
            'isFiltered',
            'top6HotProductIds'
        ));
    }


    // Hiển thị danh sách tất cả sản phẩm kèm tính năng tìm
    public function index(Request $request)
    {
        // Nhận dữ liệu đầu vào phục vụ cho việc lọc sản phẩm
        $categoryIds = $request->input('category', []);
        if (!is_array($categoryIds)) {
            $categoryIds = empty($categoryIds) ? [] : [$categoryIds];
        }
        $maxPrice = $request->input('max_price', 600000);
        $minRating = $request->input('rating');

        // Xử lý chuẩn hóa từ khóa tìm kiếm chuyển chữ thường
        $rawSearch = $request->input('search');
        $searchQuery = '';
        if (!empty($rawSearch)) {
            $searchQuery = trim($rawSearch);
            if (class_exists('Normalizer')) {
                $searchQuery = \Normalizer::normalize($searchQuery, \Normalizer::FORM_C);//chuan hoa
            }
            $searchQuery = mb_strtolower($searchQuery, 'UTF-8');
        }

        // Lấy danh sách các danh mục sản phẩm đang mở hoạt
        $categories = Category::query()
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->get();

        // Xây dựng câu truy vấn lọc danh sách sản phẩm
        $query = Product::query()
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.review_count, 0) as review_count'),
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            ->leftJoin(DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL GROUP BY oi.product_id) as o"), 'products.id', '=', 'o.product_id')
            ->where('categories.is_active', 1);

        // Lọc theo danh mục sản phẩm được tích chọn, nếu có
        if (!empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }

        // Lọc theo khoảng giá tối đa được chọn trên thanh kéo slider
        if ($maxPrice) {
            $query->where('products.base_price', '<=', $maxPrice);
        }

        // Lọc theo điểm số đánh giá trung bình tối thiểu ví dụ:
        if ($minRating !== null) {
            $query->whereRaw('COALESCE(r.avg_rating, 0) >= ?', [$minRating]);
        }

        // Lọc theo từ khóa tìm kiếm tìm kiếm không phân biệt
        if (!empty($searchQuery)) {
            $query->where(DB::raw('LOWER(products.name)'), 'like', '%' . $searchQuery . '%');
        }

        // Thực thi lấy kết quả sản phẩm
        $products = $query->orderByDesc('products.is_active')->orderByDesc('total_sold')
            ->paginate(15)->withQueryString();

        // Lấy danh sách ID các sản phẩm đã được người dùng
        $favoriteProductIds = [];
        if (Auth::check()) {
            $favoriteProductIds = Favorite::query()
                ->where('user_id', Auth::id())
                ->pluck('product_id')
                ->toArray();
        }

        // Lấy danh sách ID của top 6 sản phẩm bán chạy nhất
        $top6HotProductIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->where('orders.status', 'completed')
            ->where('orders.payment_status', 'paid')
            ->whereNull('orders.deleted_at')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id')->toArray();

        // Trả dữ liệu sang view danh sách sản phẩm
        return view('frontend.products.index', compact('categories', 'products', 'favoriteProductIds', 'categoryIds', 'maxPrice', 'top6HotProductIds'));
    }

    // Số đánh giá tải mỗi lần trang đầu + mỗi lần bấm "Xem
    private const REVIEWS_PER_PAGE = 5;
}
