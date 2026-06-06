<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController
{
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
                DB::raw('COALESCE(AVG(reviews.rating), 0) as avg_rating'),
                DB::raw('COUNT(reviews.id) as review_count')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('reviews', function ($join) {
                $join->on('products.id', '=', 'reviews.product_id')
                    ->where('reviews.is_visible', 1);
            })
            ->where('products.is_active', 1)
            ->groupBy(
                'products.id',
                'products.sku',
                'products.slug',
                'products.name',
                'products.base_price',
                'products.image',
                'products.description',
                'products.is_active',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
                'categories.name'
            );

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
            $query->having('avg_rating', '>=', $minRating);
        }

        // Filter by search query (chỉ tìm theo tên sản phẩm)
        if (!empty($searchQuery)) {
            $query->where(DB::raw('LOWER(products.name)'), 'like', '%' . $searchQuery . '%');
        }

        // Get all results without pagination
        $products = $query->get();

        // 3. Get User's Wishlist (if logged in)
        $favoriteProductIds = [];
        if (Auth::check()) {
            $favoriteProductIds = DB::table('favorites')
                ->where('user_id', Auth::id())
                ->pluck('product_id')
                ->toArray();
        }

        return view('pages.products', compact('categories', 'products', 'favoriteProductIds', 'categoryIds', 'maxPrice'));
    }
}
