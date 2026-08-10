<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BannerController
{
    //HIỂN THỊ DANH SÁCH BANNER
    public function index(Request $request)
    {
        $query = Banner::query();
        $now = now();

        // 1. Tìm kiếm theo tiêu đề hoặc liên kết
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_tag', 'like', "%{$search}%")
                    ->orWhere('link_url', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo trạng thái
        if ($request->filled('status') && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('is_active', 1)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                    });
            } elseif ($status === 'inactive') {
                $query->where('is_active', 0);
            } elseif ($status === 'upcoming') {
                $query->where('is_active', 1)
                    ->whereNotNull('start_at')
                    ->where('start_at', '>', $now);
            } elseif ($status === 'expired') {
                $query->where('is_active', 1)
                    ->whereNotNull('end_at')
                    ->where('end_at', '<', $now);
            }
        }

        // 3. Lọc theo vị trí hiển thị
        if ($request->filled('position') && $request->position !== 'all') {
            $query->where('position', $request->position);
        }

        // 4. Sắp xếp
        switch ($request->sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'order_asc':
                $query->orderBy('display_order', 'asc');
                break;
            case 'order_desc':
                $query->orderBy('display_order', 'desc');
                break;
            default:
                $query->orderBy('display_order', 'asc');
                break;
        }

        $banners = $query->paginate(10)->withQueryString();

        // Thống kê tổng quan
        $totalBanners = Banner::count();
        $activeBanners = Banner::where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->count();
        $inactiveBanners = Banner::where('is_active', 0)->count();
        $upcomingBanners = Banner::where('is_active', 1)
            ->whereNotNull('start_at')
            ->where('start_at', '>', $now)
            ->count();
        $expiredBanners = Banner::where('is_active', 1)
            ->whereNotNull('end_at')
            ->where('end_at', '<', $now)
            ->count();

        // Lấy danh sách các vị trí hiện có trong DB để làm bộ lọc
        $positions = Banner::select('position')->distinct()->whereNotNull('position')->pluck('position')->toArray();

        return view('backend.admin.banners.index', compact(
            'banners',
            'totalBanners',
            'activeBanners',
            'inactiveBanners',
            'upcomingBanners',
            'expiredBanners',
            'positions'
        ));
    }

    //FORM THÊM MỚI
    public function create()
    {
        return view('backend.admin.banners.create');
    }

   // LƯU BANNER MỚI
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:100',
            'title_tag' => 'nullable|string|max:50',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'link_url' => 'nullable|url',
            'position' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ], [
            'image.required' => 'Vui lòng chọn ảnh banner.',
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.mimes' => 'Định dạng ảnh được chấp nhận: jpeg, png, jpg, webp, gif.',
            'image.max' => 'Kích thước ảnh không được vượt quá 10MB.',
            'link_url.url' => 'Đường dẫn liên kết không đúng định dạng URL.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ]);

        $data = $request->only(['title', 'title_tag', 'link_url', 'position', 'display_order', 'start_at', 'end_at']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['position'] = ($data['position'] ?? 'home_slider') ?: 'home_slider';

        if (empty($data['display_order'])) {
            $data['display_order'] = (Banner::max('display_order') ?? 0) + 1;
        }

        if ($request->hasFile('image')) {
            $uploaded = [];
            $data['image_url'] = $this->storeImage($request->file('image'), $uploaded);
        }
        // Cột "image" (khác "image_url") là cột CŨ từ migration gốc, vẫn còn ràng buộc NOT NULL nhưng
        // controller chưa từng ghi vào từ khi chuyển sang "image_url" — INSERT thiếu cột này sẽ báo
        // lỗi constraint. Không còn ai đọc cột "image" nữa nên chỉ cần điền cùng giá trị cho hợp lệ,
        // không cần thêm migration đổi schema.
        $data['image'] = $data['image_url'] ?? '';

        Banner::create($data);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Đã tạo banner thành công!');
    }

    //FORM CHỈNH SỬA
    public function edit(Banner $banner)
    {
        return view('backend.admin.banners.edit', compact('banner'));
    }

    //CẬP NHẬT BANNER
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => 'nullable|string|max:100',
            'title_tag' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'link_url' => 'nullable|url',
            'position' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ], [
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.mimes' => 'Định dạng ảnh được chấp nhận: jpeg, png, jpg, webp, gif.',
            'image.max' => 'Kích thước ảnh không được vượt quá 10MB.',
            'link_url.url' => 'Đường dẫn liên kết không đúng định dạng URL.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ]);

        $data = $request->only(['title', 'title_tag', 'link_url', 'position', 'display_order', 'start_at', 'end_at']);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['position'] = ($data['position'] ?? 'home_slider') ?: 'home_slider';

        if (empty($data['display_order'])) {
            $data['display_order'] = 0;
        }

        if ($request->hasFile('image')) {
            $uploaded = [];
            $data['image_url'] = $this->storeImage($request->file('image'), $uploaded);

            // Xóa ảnh cũ
            if ($banner->image_url) {
                $oldPath = upload_path($banner->image_url);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Đã cập nhật banner thành công!');
    }

    //XÓA BANNER
    public function destroy(Banner $banner)
    {
        if ($banner->image_url) {
            $oldPath = upload_path($banner->image_url);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Đã xóa banner thành công!');
    }

    // XÓA NHIỀU BANNER (chỉ các dòng đang chọn trong trang hiện tại)
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'banner_ids' => 'required|array',
            'banner_ids.*' => 'exists:banners,id'
        ]);

        $banners = Banner::whereIn('id', $request->banner_ids)->get();

        $deletedCount = 0;
        foreach ($banners as $banner) {
            if ($banner->image_url) {
                $oldPath = upload_path($banner->image_url);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $banner->delete();
            $deletedCount++;
        }

        return redirect()->route('admin.banners.index')->with('success', "Đã xóa {$deletedCount} banner thành công.");
    }

    // BẬT/TẮT TRẠNG THÁI NHANH
    public function toggleStatus(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = $banner->is_active ? 0 : 1;
        $banner->save();

        return redirect()->route('admin.banners.index')->with('success', 'Cập nhật trạng thái banner thành công!');
    }

   //Helper lưu ảnh banner
    private function storeImage($file, array &$uploaded): string
    {
        $filename = (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(public_path('images/banners'), $filename);
        $uploaded[] = 'banners/' . $filename;
        return 'banners/' . $filename;
    }
}
