<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Controller quản lý đánh giá (reviews) ở khu vực Backend (admin).
 * Chứa các hành động: liệt kê, thêm, sửa, xóa, thay đổi hiển thị, xóa ảnh.
 * Mọi chú thích trong file được viết bằng tiếng Việt để dễ hiểu.
 */
class ReviewController
{
    /**
     * Hiển thị danh sách đánh giá với chức năng lọc, sắp xếp và phân trang.
     */
    public function index(Request $request)
    {
        // Khởi tạo query và eager-load quan hệ `user` và `product` để tránh N+1
        $query = Review::with(['user', 'product']);

        // Lọc theo ô tìm kiếm: tìm trong tên user hoặc tên product
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qu) use ($search) {
                    $qu->where('name', 'like', "%{$search}%");
                })->orWhereHas('product', function ($qp) use ($search) {
                    $qp->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Lọc theo trạng thái hiển thị: visible, hidden hoặc 'all'
        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'visible') {
                $query->where('is_visible', 1);
            } elseif ($request->input('status') === 'hidden') {
                $query->where('is_visible', 0);
            }
        }

        // Lọc theo rating nếu có
        if ($request->filled('rating') && $request->input('rating') !== 'all') {
            $query->where('rating', $request->input('rating'));
        }

        // Sắp xếp theo yêu cầu: newest, oldest, highest/lowest rating
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Nếu yêu cầu chỉ lấy ID để phục vụ "Chọn tất cả" toàn bộ DB
        if ($request->input('fetch_all_ids') == 1) {
            return response()->json([
                'ids' => (clone $query)->pluck('id')->toArray()
            ]);
        }

        // Phân trang kết quả và giữ các param query string để điều hướng
        $reviews = $query->paginate(10)->appends($request->all());

        // Thống kê nhanh: tổng, đang hiển thị, đã ẩn
        $totalReviews = Review::count();
        $activeReviews = Review::where('is_visible', 1)->count();
        $hiddenReviews = Review::where('is_visible', 0)->count();

        // Nếu là AJAX (ví dụ: lọc không reload toàn trang) trả partial HTML
        if ($request->ajax()) {
            return response()->json([
                'html' =>  view('backend.admin.reviews.partials.table', compact('reviews'))->render(),
                'stats' => [
                    'total' => number_format($totalReviews),
                    'active' => number_format($activeReviews),
                    'hidden' => number_format($hiddenReviews)
                ]
            ]);
        }

        // Trả view chính (full page)
        return  view('backend.admin.reviews.index', compact('reviews', 'totalReviews', 'activeReviews', 'hiddenReviews'));
    }

    /**
     * Chuyển đổi trạng thái hiển thị của đánh giá (toggle visible/hidden).
     */
    public function toggleVisibility(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $review->is_visible = !$review->is_visible;
        $review->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trạng thái đánh giá đã được cập nhật!',
                'new_status' => $review->is_visible,
                'stats' => [
                    'total' => number_format(Review::count()),
                    'active' => number_format(Review::where('is_visible', 1)->count()),
                    'hidden' => number_format(Review::where('is_visible', 0)->count())
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Trạng thái đánh giá đã được cập nhật!');
    }

    /**
     * Hiển thị form tạo đánh giá mới (thường dùng cho admin nếu cần).
     */
    public function create()
    {
        // Thường admin không tự tạo đánh giá, nhưng để có form CRUD chuẩn
        $products = Product::all();
        $users = User::all();
        return  view('backend.admin.reviews.create', compact('products', 'users'));
    }

    /**
     * Xử lý lưu đánh giá mới vào cơ sở dữ liệu, kèm upload ảnh (nếu có).
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'is_visible' => 'boolean',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $images = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                if (count($images) >= 5) {
                    break;
                }
                if ($file->isValid()) {
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename = 'review_' . time() . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path('images/reviews'), $filename);
                    $images[] = 'reviews/' . $filename;
                }
            }
        }

        Review::create([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'image' => count($images) > 0 ? json_encode(array_values($images)) : null,
            'is_visible' => $request->is_visible ?? 1,
        ]);

        return redirect()->route('admin.reviews.index')->with('success', 'Thêm đánh giá thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa một đánh giá cụ thể.
     */
    public function edit($id)
    {
        $review = Review::findOrFail($id);
        return  view('backend.admin.reviews.edit', compact('review'));
    }

    /**
     * Cập nhật dữ liệu đánh giá, xử lý xóa/ thêm ảnh và lưu thay đổi.
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'is_visible' => 'boolean',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images' => 'nullable|array',
        ]);

        $images = $review->image ? json_decode($review->image, true) : [];
        if (!is_array($images)) $images = [];

        // Delete requested images
        if ($request->has('delete_images') && is_array($request->delete_images)) {
            foreach ($request->delete_images as $delImg) {
                if (($key = array_search($delImg, $images)) !== false) {
                    unset($images[$key]);
                    if (file_exists(upload_path($delImg))) {
                        unlink(upload_path($delImg));
                    }
                }
            }
        }

        // Add new images (enforce max 5 limit)
        if ($request->hasFile('new_images')) {
            $currentCount = count($images);
            foreach ($request->file('new_images') as $file) {
                if ($currentCount >= 5) {
                    break; // Stop accepting new images if we hit 5
                }

                if ($file->isValid()) {
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $filename = 'review_' . time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $ext;
                    $file->move(public_path('images/reviews'), $filename);
                    $images[] = 'reviews/' . $filename;
                    $currentCount++;
                }
            }
        }

        $images = array_values($images);
        $imageJson = empty($images) ? null : json_encode($images);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_visible' => $request->is_visible ?? 0,
            'image' => $imageJson,
        ]);

        return redirect()->route('admin.reviews.index')->with('success', 'Cập nhật đánh giá thành công!');
    }

    /**
     * Xóa một ảnh liên kết với đánh giá (xóa file trên disk và cập nhật DB).
     */
    public function deleteImage(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $imageToDelete = $request->input('image');

        if (!$imageToDelete || !$review->image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh.'], 404);
        }

        $images = json_decode($review->image, true);
        if (!is_array($images)) $images = [];

        if (($key = array_search($imageToDelete, $images)) !== false) {
            // Xóa khỏi array
            unset($images[$key]);

            // Xóa file thực tế
            if (file_exists(upload_path($imageToDelete))) {
                unlink(upload_path($imageToDelete));
            }

            // Cập nhật database
            $images = array_values($images);
            $review->image = empty($images) ? null : json_encode($images);
            $review->save();

            return response()->json(['success' => true, 'message' => 'Đã xóa ảnh.']);
        }

        return response()->json(['success' => false, 'message' => 'Ảnh không thuộc đánh giá này.'], 404);
    }

    /**
     * Xóa các file ảnh (JSON array đường dẫn) gắn với 1 đánh giá - gọi trước khi xóa DB
     * để tránh mồ côi file trong public/images/reviews.
     */
    private function deleteReviewImageFiles(Review $review): void
    {
        $images = $review->image ? json_decode($review->image, true) : [];
        if (!is_array($images)) return;

        foreach ($images as $image) {
            $path = upload_path($image);
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Xóa một đánh giá (hỗ trợ AJAX và redirect thông thường).
     */
    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $this->deleteReviewImageFiles($review);
        $review->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Xóa đánh giá thành công!'
            ]);
        }

        return redirect()->back()->with('success', 'Xóa đánh giá thành công!');
    }

    /**
     * Xóa hàng loạt đánh giá theo mảng id truyền lên từ giao diện.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('review_ids', []);

        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn ít nhất một đánh giá.'
                ]);
            }
            return redirect()->route('admin.reviews.index')
                ->with('error', 'Vui lòng chọn ít nhất một đánh giá.');
        }

        $reviewsToDelete = Review::whereIn('id', $ids)->get();
        foreach ($reviewsToDelete as $review) {
            $this->deleteReviewImageFiles($review);
        }
        $count = $reviewsToDelete->count();
        Review::whereIn('id', $ids)->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$count} đánh giá thành công!"
            ]);
        }

        return redirect()->route('admin.reviews.index')
            ->with('success', "Đã xóa {$count} đánh giá thành công!");
    }
}
