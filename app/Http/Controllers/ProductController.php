<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController
{
    public function show($slug)
    {
        // 1. Find Product by slug
        $product = DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.review_count, 0) as review_count'),
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id) as o'), 'products.id', '=', 'o.product_id')
            ->where('products.slug', $slug)
            ->where('products.is_active', 1)
            ->first();

        if (!$product) {
            abort(404);
        }

        // 2. Get Sizes
        $sizes = DB::table('product_sizes')
            ->where('product_id', $product->id)
            ->orderBy('price_adjustment')
            ->get();

        // 3. Get Toppings
        $toppings = DB::table('toppings')
            ->join('product_toppings', 'toppings.id', '=', 'product_toppings.topping_id')
            ->where('product_toppings.product_id', $product->id)
            ->where('toppings.is_available', 1)
            ->select('toppings.*')
            ->get();

        // 4. Get Reviews with User Info
        $reviews = DB::table('reviews')
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $product->id)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name')
            ->orderBy('reviews.created_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Rating distribution
        $ratingDistribution = DB::table('reviews')
            ->where('product_id', $product->id)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // 6. Related Products (same category)
        $relatedProducts = DB::table('products')
            ->select(
                'products.*',
                DB::raw('COALESCE(r2.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(o2.total_sold, 0) as total_sold')
            )
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r2'), 'products.id', '=', 'r2.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id) as o2'), 'products.id', '=', 'o2.product_id')
            ->where('products.category_id', $product->category_id)
            ->where('products.id', '!=', $product->id)
            ->where('products.is_active', 1)
            ->orderByDesc('total_sold')
            ->limit(4)
            ->get();

        // 7. Wishlist status
        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = DB::table('favorites')
                ->where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
        }

        // 8. Check if bestseller
        $top6HotProductIds = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id')->toArray();

        $isHot = in_array($product->id, $top6HotProductIds);
        $isNew = (\Carbon\Carbon::parse($product->created_at)->diffInDays(now()) <= 15);

        return view('pages.product-detail', compact(
            'product', 'sizes', 'toppings', 'reviews',
            'ratingDistribution', 'relatedProducts', 'isFavorite', 'isHot', 'isNew'
        ));
    }

    public function index(Request $request)
    {
        // Get filter inputs
        $categoryIds = $request->input('category', []);
        $maxPrice = $request->input('max_price', 600000); // Default to max 600k
        $minRating = $request->input('rating');

        $rawSearch = $request->input('search');
        $searchQuery = '';
        if (!empty($rawSearch)) {
            $searchQuery = trim($rawSearch);
            if (class_exists('Normalizer')) {
                $searchQuery = \Normalizer::normalize($searchQuery, \Normalizer::FORM_C);
            }
            $searchQuery = mb_strtolower($searchQuery, 'UTF-8');
        }

        // 1. Fetch Active Categories
        $categories = DB::table('categories')
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->get();

        // 2. Query Products
        $query = DB::table('products')
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.review_count, 0) as review_count'),
                DB::raw('COALESCE(o.total_sold, 0) as total_sold')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id) as o'), 'products.id', '=', 'o.product_id')
            ->where('products.is_active', 1);

        // Filter by category if provided
        if (!empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }

        // Filter by max price
        if ($maxPrice) {
            $query->where('products.base_price', '<=', $maxPrice);
        }

        // Filter by rating
        if ($minRating !== null) {
            $query->whereRaw('COALESCE(r.avg_rating, 0) >= ?', [$minRating]);
        }

        // Filter by search query (chỉ tìm theo tên sản phẩm)
        if (!empty($searchQuery)) {
            $query->where(DB::raw('LOWER(products.name)'), 'like', '%' . $searchQuery . '%');
        }

        // Get all results without pagination, pre-sorted by popularity to avoid JS re-sort flash
        $products = $query->orderByDesc('total_sold')->get();

        // 3. Get User's Wishlist (if logged in)
        $favoriteProductIds = [];
        if (Auth::check()) {
            $favoriteProductIds = DB::table('favorites')
                ->where('user_id', Auth::id())
                ->pluck('product_id')
                ->toArray();
        }

        // 4. Get Top 6 Best Selling Product IDs
        $top6HotProductIds = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(6)
            ->pluck('product_id')->toArray();

        return view('pages.products', compact('categories', 'products', 'favoriteProductIds', 'categoryIds', 'maxPrice', 'top6HotProductIds'));
    }
}
