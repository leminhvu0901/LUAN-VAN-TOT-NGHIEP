<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewController
{
    public function create($orderId, $productId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // 1. Verify order belongs to user and is completed
        $order = \App\Models\Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$order) {
            return redirect()->route('orders')->with('error', 'Không tìm thấy đơn hàng hợp lệ để đánh giá.');
        }

        // 2. Verify product is in the order
        $orderItem = \App\Models\OrderItem::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$orderItem) {
            return redirect()->route('orders')->with('error', 'Sản phẩm này không thuộc đơn hàng của bạn.');
        }

        // 3. Verify not already reviewed
        $existingReview = \App\Models\Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return redirect()->route('orders')->with('error', 'Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi.');
        }

        // 4. Get product details (similar to ProductController)
        $product = \App\Models\Product::query()
            ->select(
                'products.*',
                'categories.name as category_name',
                DB::raw('COALESCE(r.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(r.review_count, 0) as review_count')
            )
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE is_visible = 1 GROUP BY product_id) as r'), 'products.id', '=', 'r.product_id')
            ->where('products.id', $productId)
            ->first();

        if (!$product) {
            abort(404);
        }

        // 5. Get existing reviews for this product
        $reviews = \App\Models\Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $productId)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name', 'users.avatar as user_avatar')
            ->orderBy('reviews.created_at', 'desc')
            ->limit(10)
            ->get();

        $ratingDistribution = \App\Models\Review::query()
            ->where('product_id', $productId)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return view('frontend.products.review', compact('order', 'product', 'reviews', 'ratingDistribution'));
    }

    public function store(Request $request, $orderId, $productId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // 1. Validations
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.integer' => 'Số sao đánh giá không hợp lệ.',
            'rating.min' => 'Số sao tối thiểu là 1.',
            'rating.max' => 'Số sao tối đa là 5.',
            'comment.max' => 'Cảm nhận của bạn quá dài (tối đa 1000 ký tự).',
            'images.max' => 'Bạn chỉ được tải lên tối đa 5 hình ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh chỉ hỗ trợ định dạng: jpeg, png, jpg, gif.',
            'images.*.max' => 'Dung lượng mỗi hình ảnh không được vượt quá 2MB.'
        ]);

        // 2. Verify order and item again
        $order = \App\Models\Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        $orderItem = \App\Models\OrderItem::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$order || !$orderItem) {
            return redirect()->route('orders')->with('error', 'Không thể đánh giá sản phẩm này.');
        }

        // 3. Verify not already reviewed
        $existingReview = \App\Models\Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->exists();

        if ($existingReview) {
            return redirect()->route('orders')->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
        }

        // 4. Handle multiple image upload
        $imageNames = [];
        $files = $request->file('images');

        if ($files) {
            // Ensure it's an array even if a single file is uploaded
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $image) {
                if ($image && $image->isValid()) {
                    $ext = $image->getClientOriginalExtension() ?: 'jpg';
                    $imageName = 'reviews/' . time() . '_' . Str::random(10) . '.' . $ext;
                    $image->move(public_path('images'), $imageName);
                    $imageNames[] = $imageName;
                }
            }
        }
        $imageJson = empty($imageNames) ? null : json_encode($imageNames);

        // 5. Insert review
        \App\Models\Review::query()->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'image' => $imageJson,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('orders', ['status' => 'completed'])->with([
            'success' => 'Cảm ơn bạn đã đánh giá sản phẩm!',
            'open_order_id' => $orderId
        ]);
    }
}
