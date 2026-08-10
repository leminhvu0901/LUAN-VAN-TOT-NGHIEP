<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewController
{
    // Khách chỉ được sửa lại đánh giá trong vòng 7 ngày
    private const EDIT_WINDOW_DAYS = 7;

    // HIỂN THỊ TRANG ĐÁNH GIÁ SẢN PHẨM
    public function create($orderId, $productId, Request $request)
    {
        $userId = Auth::id();

        // 1. Xác minh đơn hàng có thuộc về user này và ở trạng thái hoàn thành (completed) hay không
        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$order) {
            return redirect()->route('orders')->with('error', 'Không tìm thấy đơn hàng hợp lệ để đánh giá.');
        }

        // 2. Xác minh sản phẩm có thực sự nằm trong đơn hàng này không
        $orderItem = OrderItem::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$orderItem) {
            return redirect()->route('orders')->with('error', 'Sản phẩm này không thuộc đơn hàng của bạn.');
        }

        // 3. Tìm kiếm đánh giá hiện có của sản phẩm trong đơn hàng này (nếu đã từng đánh giá trước đó)
        $existingReview = Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        // 4. Lấy thông tin sản phẩm và trung bình điểm số, số lượt đánh giá
        $product = Product::query()
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

        // 5. Lấy danh sách các đánh giá khác đang hiển thị công khai để vẽ bảng thông tin tổng quan sản
        // phẩm bên lề — áp dụng bộ lọc sao/có ảnh (nút lọc trên trang giờ là link GET thật).
        $reviewsQuery = Review::query()
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.product_id', $productId)
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
            ->paginate(5)
            ->withQueryString();

        // Phân phối tỷ lệ đánh giá (số lượng 5 sao, 4 sao...)
        $ratingDistribution = Review::query()
            ->where('product_id', $productId)
            ->where('is_visible', 1)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Số đánh giá có ảnh
        $hasImageCount = Review::query()
            ->where('product_id', $productId)
            ->where('is_visible', 1)
            ->whereNotNull('image')
            ->count();

        // Kiểm tra xem khách hàng có thể chỉnh sửa lại đánh giá cũ này không (chưa từng sửa và trong 7 ngày)
        $canEditReview = $existingReview && $this->canStillEdit($existingReview);
        $editWindowDays = self::EDIT_WINDOW_DAYS;

        return view('frontend.products.review', compact('order', 'product', 'reviews', 'ratingDistribution', 'hasImageCount', 'existingReview', 'canEditReview', 'editWindowDays', 'isFiltered'));
    }

    // LƯU ĐÁNH GIÁ SẢN PHẨM
    public function store(Request $request, $orderId, $productId)
    {
        $userId = Auth::id();

        // 1. Kiểm tra tính hợp lệ của dữ liệu gửi lên (số sao, nội dung, hình ảnh tải lên)
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:150',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.integer' => 'Số sao đánh giá không hợp lệ.',
            'rating.min' => 'Số sao tối thiểu là 1.',
            'rating.max' => 'Số sao tối đa là 5.',
            'comment.max' => 'Cảm nhận của bạn quá dài (tối đa 150 ký tự).',
            'images.max' => 'Bạn chỉ được tải lên tối đa 5 hình ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh chỉ hỗ trợ định dạng: jpeg, png, jpg, gif.',
            'images.*.max' => 'Dung lượng mỗi hình ảnh không được vượt quá 2MB.'
        ]);

        // 2. Xác minh đơn hàng và món nước một lần nữa
        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        $orderItem = OrderItem::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$order || !$orderItem) {
            return redirect()->route('orders')->with('error', 'Không thể đánh giá sản phẩm này.');
        }

        // 3. Kiểm tra chắc chắn xem sản phẩm này đã được người dùng đánh giá chưa
        $existingReview = Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->exists();

        if ($existingReview) {
            return redirect()->route('orders')->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
        }

        // 4. Xử lý lưu các hình ảnh tải lên của đánh giá
        $imageNames = [];
        $files = $request->file('images');

        if ($files) {
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $image) {
                if ($image && $image->isValid()) {
                    $ext = $image->getClientOriginalExtension() ?: 'jpg';
                    $imageName = time() . '_' . Str::random(10) . '.' . $ext;
                    // Di chuyển file ảnh vào thư mục public/images/reviews
                    $image->move(public_path('images/reviews'), $imageName);
                    $imageNames[] = 'reviews/' . $imageName;
                }
            }
        }
        // Lưu trữ danh sách ảnh dưới dạng mảng JSON trong DB
        $imageJson = empty($imageNames) ? null : json_encode($imageNames);

        // 5. Ghi thông tin đánh giá mới vào cơ sở dữ liệu.
        // Sử dụng khối try/catch bắt lỗi khóa ngoại/duplicate UNIQUE đề phòng trường hợp gửi request song song (race condition).
        try {
            Review::query()->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'order_id' => $orderId,
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
                'image' => $imageJson,
                'is_visible' => 1, // Mặc định hiển thị công khai luôn
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (QueryException $e) {
            // Mã lỗi 23000: Vi phạm ràng buộc duy nhất (Unique Constraint) - nghĩa là đã đánh giá rồi
            if ((int) $e->getCode() === 23000) {
                return redirect()->route('orders')->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
            }
            throw $e;
        }

        session()->flash('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
        session()->flash('open_order_id', $orderId);

        return redirect()->route('orders', ['status' => 'completed']);
    }

   // CẬP NHẬT ĐÁNH GIÁ
    public function update(Request $request, $orderId, $productId)
    {
        $userId = Auth::id();

        // 1. Kiểm tra nhanh (không lock) để chặn sớm các yêu cầu sửa đổi không hợp lệ
        $existingReview = Review::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if (!$existingReview) {
            return redirect()->route('orders')->with('error', 'Không tìm thấy đánh giá để chỉnh sửa.');
        }

        // Chặn nếu đánh giá này đã được chỉnh sửa rồi (mỗi đánh giá chỉ được sửa 1 lần duy nhất)
        if ($existingReview->edited_at) {
            return redirect()->route('orders')->with('error', 'Đánh giá này đã được chỉnh sửa 1 lần rồi, bạn không thể sửa thêm nữa.');
        }

        // Chặn nếu đã quá thời hạn chỉnh sửa 7 ngày
        if (!$this->withinEditWindow($existingReview)) {
            return redirect()->route('orders')->with('error', 'Đã quá ' . self::EDIT_WINDOW_DAYS . ' ngày kể từ lúc đánh giá, bạn không thể chỉnh sửa nữa.');
        }

        // 2. Thực hiện validate dữ liệu đầu vào cập nhật
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:150',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.integer' => 'Số sao đánh giá không hợp lệ.',
            'rating.min' => 'Số sao tối thiểu là 1.',
            'rating.max' => 'Số sao tối đa là 5.',
            'comment.max' => 'Cảm nhận của bạn quá dài (tối đa 150 ký tự).',
            'images.max' => 'Bạn chỉ được tải lên tối đa 5 hình ảnh.',
            'images.*.image' => 'File tải lên phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh chỉ hỗ trợ định dạng: jpeg, png, jpg, gif.',
            'images.*.max' => 'Dung lượng mỗi hình ảnh không được vượt quá 2MB.'
        ]);

        // 3. Xử lý tải hình ảnh mới lên trước khi bắt đầu Lock dữ liệu trong Database (tối ưu hóa tốc độ Lock)
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

        // 4. Mở Transaction và thực hiện khóa dòng dữ liệu (lockForUpdate) để tránh ghi đè/spam đồng thời
        $result = DB::transaction(function () use ($orderId, $productId, $userId, $request, $newImageNames) {
            $locked = Review::query()
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            // Kiểm tra bảo mật chặt chẽ lại một lần nữa khi đang trong trạng thái Lock
            if (!$locked || $locked->edited_at || !$this->withinEditWindow($locked)) {
                return ['success' => false, 'review' => $locked];
            }

            // Nếu người dùng tải lên hình ảnh mới, tiến hành xóa toàn bộ ảnh cũ trên ổ đĩa vật lý trước khi ghi đè ảnh mới
            if (!empty($newImageNames)) {
                $oldImages = $locked->image ? json_decode($locked->image, true) : [];
                if (is_array($oldImages)) {
                    foreach ($oldImages as $oldImage) {
                        $path = upload_path($oldImage);
                        if ($path && file_exists($path)) {
                            @unlink($path); // Xóa file
                        }
                    }
                }
                $locked->image = json_encode($newImageNames);
            }

            // Ghi nhận số sao, bình luận mới và thời gian chỉnh sửa
            $locked->rating = $request->input('rating');
            $locked->comment = $request->input('comment');
            $locked->edited_at = now();
            $locked->save();

            return ['success' => true, 'review' => $locked];
        });

        // 6. Xử lý khi cập nhật thất bại (Ví dụ: do người dùng bấm gửi 2 lần gần như cùng lúc và request thứ nhất đã ghi đè xong trước)
        if (!$result['success']) {
            // Xóa toàn bộ các ảnh mới vừa tải lên mà không dùng đến để tránh rác ổ đĩa
            foreach ($newImageNames as $img) {
                $path = upload_path($img);
                if ($path && file_exists($path)) {
                    @unlink($path);
                }
            }
            return redirect()->route('orders')->with('error', 'Đánh giá này đã được chỉnh sửa 1 lần rồi, bạn không thể sửa thêm nữa.');
        }

        session()->flash('success', 'Đã cập nhật đánh giá của bạn!');
        session()->flash('open_order_id', $orderId);

        return redirect()->route('orders', ['status' => 'completed']);
    }

    // KIỂM TRA 7 NGÀY
    private function withinEditWindow(Review $review): bool
    {
        return now()->lte($review->created_at->copy()->addDays(self::EDIT_WINDOW_DAYS));
    }

    //XÁC MINH NGƯỜI DUNG CÓ THỂ CHỈNH SỬA ĐÁNH GIÁ ĐƯỢC HAY KHÔNG
    private function canStillEdit(Review $review): bool
    {
        return !$review->edited_at && $this->withinEditWindow($review);
    }
}
