<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController
{
    /**
     * Hiển thị trang thông tin chi tiết của một sản phẩm.
     * 
     * @param string $slug - Đường dẫn thân thiện của sản phẩm (ví dụ: 'tra-sua-tran-chau')
     */
    public function show($slug)
    {
        // lay het thong tin tu san pham ban chọn
        $product = \App\Models\Product::query()
            ->select(
                'products.*',
                'categories.name as category_name',
                // Lấy điểm đánh giá trung bình, mặc định là 0 nếu chưa có đánh giá nào
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                // Lấy tổng số lượt đánh giá, mặc định là 0
                DB::raw('COALESCE(r.review_count, 0) as review_count'),
                // Lấy tổng số lượng sản phẩm này đã bán ra, mặc định là 0
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            // Join với bảng phụ gom nhóm điểm đánh giá theo sản phẩm (chỉ lấy đánh giá được phép hiển thị)
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            // Join với bảng phụ gom nhóm tổng số lượng đã bán từ các chi tiết đơn hàng
            ->leftJoin(DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL GROUP BY oi.product_id) as o"), 'products.id', '=', 'o.product_id')
            ->where('categories.is_active', 1)
            ->where('products.slug', $slug)
            ->first();

        // Nếu không tìm thấy sản phẩm, quăng lỗi 404 (Không tìm thấy trang)
        if (!$product) {
            abort(404);
        }

        // 2. Lấy danh sách các kích cỡ (Sizes) của sản phẩm này và sắp xếp theo mức giá chênh lệch tăng dần
        $sizes = \App\Models\ProductSize::query()
            ->where('product_id', $product->id)
            ->orderBy('price_adjustment')
            ->get();

        // 3. Lấy danh sách các loại Topping được liên kết với sản phẩm này (chỉ lấy topping còn khả dụng)
        $toppings = \App\Models\Topping::query()
            ->join('product_toppings', 'toppings.id', '=', 'product_toppings.topping_id')
            ->where('product_toppings.product_id', $product->id)
            ->where('toppings.is_available', 1)
            ->select('toppings.*')
            ->get();

        // 4. Lấy trang đầu tiên các lượt đánh giá mới nhất kèm thông tin người dùng (chỉ lấy đánh giá
        // công khai) — dùng paginate() (không phải limit()->get()) để cùng 1 partial reviews-list-full
        // vừa render được lần tải trang đầu tiên vừa render được kết quả fetch từ reviews-filter.js
        // (cả 2 đều gọi $reviews->hasMorePages()/currentPage()).
        $reviews = \App\Models\Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $product->id)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name', 'users.avatar as user_avatar')
            ->orderBy('reviews.created_at', 'desc')
            ->paginate(self::REVIEWS_PER_PAGE);

        // 5. Phân phối điểm số đánh giá (đếm xem có bao nhiêu lượt đánh giá 1 sao, 2 sao, ..., 5 sao)
        $ratingDistribution = \App\Models\Review::query()
            ->where('product_id', $product->id)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Số đánh giá có kèm hình ảnh — dùng cho nút lọc "Có hình ảnh".
        $hasImageCount = \App\Models\Review::query()
            ->where('product_id', $product->id)
            ->where('is_visible', 1)
            ->whereNotNull('image')
            ->count();

        // 6. Tìm các sản phẩm liên quan (cùng danh mục, loại trừ sản phẩm hiện tại)
        // Sắp xếp theo mức độ phổ biến (bán chạy nhất) để gợi ý và giới hạn lấy tối đa 4 sản phẩm
        $relatedProducts = \App\Models\Product::query()
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

        // 7. Kiểm tra xem người dùng hiện tại đã lưu sản phẩm này vào danh sách yêu thích (Wishlist) chưa
        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = \App\Models\Favorite::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
        }

        // 8. Xác định xem sản phẩm này có phải là Bán chạy (Bestseller) hay không
        // Lấy danh sách ID của top 6 sản phẩm bán ra với số lượng nhiều nhất
        $top6HotProductIds = \App\Models\OrderItem::query()
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
        // Nếu sản phẩm được tạo ra cách đây dưới 15 ngày -> đánh dấu là Mới (New)
        $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15);

        // Trả về view và truyền toàn bộ dữ liệu đã tính toán sang giao diện
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
            'isNew'
        ));
    }

    /**
     * Hiển thị danh sách tất cả sản phẩm kèm tính năng tìm kiếm, lọc theo danh mục, giá cả và xếp hạng.
     */
    public function index(Request $request)
    {
        // Nhận dữ liệu đầu vào phục vụ cho việc lọc sản phẩm
        $categoryIds = $request->input('category', []);
        if (!is_array($categoryIds)) {
            $categoryIds = empty($categoryIds) ? [] : [$categoryIds];
        }
        $maxPrice = $request->input('max_price', 600000); // Giá tối đa mặc định là 600,000đ
        $minRating = $request->input('rating');

        // Xử lý chuẩn hóa từ khóa tìm kiếm (chuyển chữ thường, bỏ khoảng trắng thừa)
        $rawSearch = $request->input('search');
        $searchQuery = '';
        if (!empty($rawSearch)) {
            $searchQuery = trim($rawSearch);
            if (class_exists('Normalizer')) {
                $searchQuery = \Normalizer::normalize($searchQuery, \Normalizer::FORM_C);
            }
            $searchQuery = mb_strtolower($searchQuery, 'UTF-8');
        }

        // 1. Lấy danh sách các danh mục sản phẩm đang mở hoạt động và sắp xếp theo thứ tự hiển thị
        $categories = \App\Models\Category::query()
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->get();

        // 2. Xây dựng câu truy vấn lọc danh sách sản phẩm
        $query = \App\Models\Product::query()
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

        // Lọc theo danh mục sản phẩm được tích chọn (nếu có)
        if (!empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }

        // Lọc theo khoảng giá tối đa được chọn trên thanh kéo slider
        if ($maxPrice) {
            $query->where('products.base_price', '<=', $maxPrice);
        }

        // Lọc theo điểm số đánh giá trung bình tối thiểu (ví dụ: từ 4 sao trở lên)
        if ($minRating !== null) {
            $query->whereRaw('COALESCE(r.avg_rating, 0) >= ?', [$minRating]);
        }

        // Lọc theo từ khóa tìm kiếm (tìm kiếm không phân biệt hoa thường theo tên sản phẩm)
        if (!empty($searchQuery)) {
            $query->where(DB::raw('LOWER(products.name)'), 'like', '%' . $searchQuery . '%');
        }

        // Thực thi lấy kết quả sản phẩm, phân trang 15 sản phẩm/trang (khớp lưới 5 cột x 3 hàng ở
        // desktop, xem .p-product-grid trong users.css) và mặc định sắp xếp theo trạng thái (còn hàng
        // trước, hết hàng sau) và độ bán chạy (total_sold). withQueryString() giữ nguyên các tham số
        // lọc (category/max_price/rating/search) khi chuyển trang.
        $products = $query->orderByDesc('products.is_active')->orderByDesc('total_sold')
            ->paginate(15)->withQueryString();

        // 3. Lấy danh sách ID các sản phẩm đã được người dùng hiện tại yêu thích (để hiển thị nút thả tim)
        $favoriteProductIds = [];
        if (Auth::check()) {
            $favoriteProductIds = \App\Models\Favorite::query()
                ->where('user_id', Auth::id())
                ->pluck('product_id')
                ->toArray();
        }

        // 4. Lấy danh sách ID của top 6 sản phẩm bán chạy nhất hệ thống
        $top6HotProductIds = \App\Models\OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->where('orders.status', 'completed')
            ->where('orders.payment_status', 'paid')
            ->whereNull('orders.deleted_at')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id')->toArray();

        // Bấm chuyển trang gửi lên qua fetch (X-Requested-With: XMLHttpRequest) -> chỉ trả về đúng
        // phần lưới sản phẩm + phân trang, không render lại toàn bộ trang (header/sidebar/footer...),
        // để JS thay nội dung tại chỗ thay vì tải lại cả trang (đỡ giật/nhấp nháy).
        if ($request->expectsJson()) {
            return view('frontend.products.partials.grid', compact('products', 'favoriteProductIds', 'top6HotProductIds'))
                ->render();
        }

        // Trả dữ liệu sang view danh sách sản phẩm
        return view('frontend.products.index', compact('categories', 'products', 'favoriteProductIds', 'categoryIds', 'maxPrice', 'top6HotProductIds'));
    }

    // Số đánh giá tải mỗi lần (trang đầu + mỗi lần bấm "Xem thêm đánh giá").
    private const REVIEWS_PER_PAGE = 5;

    /**
     * Lọc + phân trang đánh giá của 1 sản phẩm qua AJAX — dùng chung cho cả trang chi tiết sản phẩm
     * (view=full, class pd-review-*) và trang "Xem đánh giá" (view=compact, class Tailwind riêng).
     * Luôn trả về HTML đã render sẵn (không bọc JSON) vì endpoint này CHỈ được gọi qua fetch từ JS,
     * không có chế độ "trang đầy đủ" — JS tự thay/nối vào danh sách hiện có.
     */
    public function reviews($productId, Request $request)
    {
        $product = \App\Models\Product::query()->find($productId);
        if (!$product) {
            abort(404);
        }

        $query = \App\Models\Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $productId)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name', 'users.avatar as user_avatar');

        $rating = $request->query('rating');
        if (in_array($rating, ['1', '2', '3', '4', '5'], true)) {
            $query->where('reviews.rating', (int) $rating);
        }

        if ($request->boolean('has_image')) {
            $query->whereNotNull('reviews.image');
        }

        $reviews = $query->orderBy('reviews.created_at', 'desc')
            ->paginate(self::REVIEWS_PER_PAGE)
            ->withQueryString();

        // Đang có bộ lọc đang áp dụng hay không -> quyết định thông báo "trống" đúng ngữ cảnh (không
        // có đánh giá nào phù hợp bộ lọc, khác với chưa có đánh giá nào cho sản phẩm này).
        $isFiltered = $request->filled('rating') || $request->boolean('has_image');

        $view = $request->query('view') === 'full'
            ? 'frontend.products.partials.reviews-list-full'
            : 'frontend.products.partials.reviews-list-compact';

        return view($view, compact('reviews', 'isFiltered'));
    }
}
