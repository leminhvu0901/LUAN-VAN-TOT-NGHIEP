<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewController
{
    // Khách chỉ được sửa lại đánh giá trong vòng 7 ngày kể từ lúc gửi (created_at gốc, không tính
    // theo lần sửa gần nhất — tránh khách sửa sát hạn để "gia hạn" thêm 7 ngày nữa).
    private const EDIT_WINDOW_DAYS = 7;

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

        // 3. Đã đánh giá rồi thì KHÔNG chặn nữa — hiển thị lại đúng trang này ở chế độ CHỈ XEM (thay
        // form nhập bằng nội dung đã gửi), thay vì đá về /orders với lỗi như trước (khiến nút "Xem
        // đánh giá" trên trang đơn hàng không có nơi để trỏ đến).
        $existingReview = \App\Models\Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

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

        // 5. Get existing reviews for this product — paginate() (không phải limit()->get()) để dùng
        // chung được partial reviews-list-compact với endpoint AJAX lọc/xem thêm (ProductController::reviews()).
        $reviews = \App\Models\Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $productId)
            ->where('reviews.is_visible', 1)
            ->select('reviews.*', 'users.name as user_name', 'users.avatar as user_avatar')
            ->orderBy('reviews.created_at', 'desc')
            ->paginate(5);

        $ratingDistribution = \App\Models\Review::query()
            ->where('product_id', $productId)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $hasImageCount = \App\Models\Review::query()
            ->where('product_id', $productId)
            ->where('is_visible', 1)
            ->whereNotNull('image')
            ->count();

        $canEditReview = $existingReview && $this->canStillEdit($existingReview);
        $editWindowDays = self::EDIT_WINDOW_DAYS;

        return view('frontend.products.review', compact('order', 'product', 'reviews', 'ratingDistribution', 'hasImageCount', 'existingReview', 'canEditReview', 'editWindowDays'));
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
                    $imageName = time() . '_' . Str::random(10) . '.' . $ext;
                    $image->move(public_path('images/reviews'), $imageName);
                    $imageNames[] = 'reviews/' . $imageName;
                }
            }
        }
        $imageJson = empty($imageNames) ? null : json_encode($imageNames);

        // 5. Insert review — bọc try/catch vì bước 3 (kiểm tra đã đánh giá chưa) và bước insert này
        // không nguyên tử: 2 request gửi gần như đồng thời đều có thể qua được bước 3 trước khi request
        // nào kịp insert. Ràng buộc UNIQUE(user_id, order_id, product_id) ở DB vẫn là chốt chặn cuối
        // cùng đáng tin cậy nhất — bắt lỗi vi phạm ràng buộc này để trả thông báo thân thiện thay vì
        // để lộ ra lỗi 500 khi thua trong race hiếm gặp đó.
        try {
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
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return redirect()->route('orders')->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
            }
            throw $e;
        }

        session()->flash('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
        session()->flash('open_order_id', $orderId);

        // Form submit qua fetch khi lỗi validate (xem review.js) để không mất ảnh/sao/bình luận vừa
        // nhập khi phải sửa lại — nhưng lúc THÀNH CÔNG vẫn điều hướng thật sang /orders (đúng ý định
        // ban đầu là quay lại danh sách đơn), JS chỉ cần đọc redirect_url rồi tự chuyển trang.
        $redirectUrl = route('orders', ['status' => 'completed']);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
        }

        return redirect($redirectUrl);
    }

    /**
     * Chỉnh sửa đánh giá đã gửi (số sao/bình luận/ảnh) — ĐÚNG 1 LẦN DUY NHẤT cho mỗi đánh giá, và chỉ
     * trong vòng EDIT_WINDOW_DAYS ngày kể từ lúc gửi ban đầu. Chọn ảnh mới sẽ THAY THẾ toàn bộ ảnh cũ
     * (xóa file cũ trên đĩa); không chọn ảnh mới thì giữ nguyên ảnh đang có — không có UI xóa từng ảnh
     * lẻ như bên admin (đơn giản hơn, đủ dùng cho khách tự sửa đánh giá của chính mình).
     */
    public function update(Request $request, $orderId, $productId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // Kiểm tra nhanh, KHÔNG lock, chỉ để chặn sớm các trường hợp rõ ràng không hợp lệ (chưa từng
        // đánh giá / đã sửa rồi / hết hạn 7 ngày) trước khi tốn công validate + upload ảnh. Việc chống
        // race thật sự (2 request PUT gần như đồng thời) nằm ở transaction lockForUpdate() bên dưới,
        // KHÔNG phải ở đây.
        $existingReview = \App\Models\Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if (!$existingReview) {
            return redirect()->route('orders')->with('error', 'Không tìm thấy đánh giá để chỉnh sửa.');
        }

        if ($existingReview->edited_at) {
            $message = 'Đánh giá này đã được chỉnh sửa 1 lần rồi, bạn không thể sửa thêm nữa.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('orders')->with('error', $message);
        }

        if (!$this->withinEditWindow($existingReview)) {
            $message = 'Đã quá ' . self::EDIT_WINDOW_DAYS . ' ngày kể từ lúc đánh giá, bạn không thể chỉnh sửa nữa.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('orders')->with('error', $message);
        }

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

        // Upload ảnh mới (I/O chậm) TRƯỚC khi mở transaction/lock — không giữ lock DB trong lúc chờ
        // ghi file, cùng nguyên tắc đã áp dụng cho luồng hoàn tiền MoMo (MomoController::requestRefund).
        $newImageNames = [];
        $files = $request->file('images');
        if ($files) {
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $image) {
                if ($image && $image->isValid()) {
                    $ext = $image->getClientOriginalExtension() ?: 'jpg';
                    $imageName = time() . '_' . Str::random(10) . '.' . $ext;
                    $image->move(public_path('images/reviews'), $imageName);
                    $newImageNames[] = 'reviews/' . $imageName;
                }
            }
        }

        // Chốt chặn race thật sự: lock đúng dòng review trong 1 transaction, ĐỌC LẠI edited_at mới
        // nhất trước khi ghi — nếu giữa lúc kiểm tra nhanh ở trên và lúc này đã có request khác sửa
        // mất lượt (2 request PUT gần như đồng thời), request này sẽ thấy edited_at đã có giá trị và
        // dừng lại, không ghi đè lên bản đã sửa trước đó.
        $result = DB::transaction(function () use ($orderId, $productId, $userId, $request, $newImageNames) {
            $locked = \App\Models\Review::query()
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$locked || $locked->edited_at || !$this->withinEditWindow($locked)) {
                return ['success' => false, 'review' => $locked];
            }

            if (!empty($newImageNames)) {
                // Xóa ảnh cũ trên đĩa trước khi thay bằng bộ ảnh mới.
                $oldImages = $locked->image ? json_decode($locked->image, true) : [];
                if (is_array($oldImages)) {
                    foreach ($oldImages as $oldImage) {
                        $path = public_path('images/' . $oldImage);
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }
                $locked->image = json_encode($newImageNames);
            }

            $locked->rating = $request->input('rating');
            $locked->comment = $request->input('comment');
            $locked->edited_at = now();
            $locked->save();

            return ['success' => true, 'review' => $locked];
        });

        if (!$result['success']) {
            // Đã thua trong race hiếm gặp -> dọn ảnh vừa upload (không dùng tới) để không rác ổ đĩa.
            foreach ($newImageNames as $img) {
                $path = public_path('images/' . $img);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            $message = 'Đánh giá này đã được chỉnh sửa 1 lần rồi, bạn không thể sửa thêm nữa.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('orders')->with('error', $message);
        }

        session()->flash('success', 'Đã cập nhật đánh giá của bạn!');
        session()->flash('open_order_id', $orderId);

        $redirectUrl = route('orders', ['status' => 'completed']);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $redirectUrl]);
        }

        return redirect($redirectUrl);
    }

    private function withinEditWindow(\App\Models\Review $review): bool
    {
        return now()->lte($review->created_at->copy()->addDays(self::EDIT_WINDOW_DAYS));
    }

    private function canStillEdit(\App\Models\Review $review): bool
    {
        return !$review->edited_at && $this->withinEditWindow($review);
    }
}
