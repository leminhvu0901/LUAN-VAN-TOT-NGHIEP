<?php

namespace App\Http\Controllers\Backend;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionController
{
    /**
     * HIỆN THỊ DANH SÁCH KHUYẾN MÃI
     */
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Tìm kiếm theo mã khuyến mãi hoặc mô tả
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(
                function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            );
        }

        // Lọc theo loại khuyến mãi (percent / fixed)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Lọc theo trạng thái hiển thị trên giao diện
        if ($request->filled('status') && $request->status !== 'all') {
            $now = now();
            if ($request->status === 'active') {
                $query->where('is_active', 1)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                    });
            } elseif ($request->status === 'upcoming') {
                $query->where('is_active', 1)->where('start_at', '>', $now);
            } elseif ($request->status === 'expired') {
                $query->where('end_at', '<', $now);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', 0);
            }
        }

        // Sắp xếp theo tiêu chí người dùng chọn
        switch ($request->sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'value_asc':
                $query->orderBy('value', 'asc');
                break;
            case 'value_desc':
                $query->orderBy('value', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $promotions = $query->paginate(10)->withQueryString();

        // Thống kê tổng quan cho 3 thẻ số liệu ở đầu trang
        $now = now();
        $totalPromotions = Promotion::count();
        $activePromotions = Promotion::where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })->count();
        $expiredPromotions = Promotion::where(function ($q) use ($now) {
            $q->where('end_at', '<', $now)->orWhere('is_active', 0);
        })->count();

        if ($request->ajax()) {
            $html = view('backend.promotions.partials.table', compact('promotions'))->render();
            return response()->json([
                'html' => $html,
                // JS đang đọc data.total để cập nhật input ẩn phục vụ xoá hàng loạt
                'total' => $promotions->total(),
                'count_text' => 'Hiển thị ' . $promotions->count() . ' / ' . $promotions->total() . ' khuyến mãi',
            ]);
        }

            // Trả dữ liệu danh sách + thống kê sang Blade để render giao diện
            return view('backend.promotions.index', compact(
            'promotions',//ds
            'totalPromotions',//tong
            'activePromotions',//ma hoat dong
            'expiredPromotions'// ma het han
        ));
    }

    /**
     * FORM THÊM MỚI
     */
    public function create()
    {
        return view('backend.promotions.create');
    }

    /**
     * LƯU KHUYẾN MÃI MỚI
     */
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào trước khi tạo mới
        $request->validate([
            'code' => 'nullable|string|max:10|unique:promotions,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'description' => 'nullable|string|max:100',
            'apply_for' => 'required|in:all,new,silver,gold,diamond',
            'is_recurring' => 'nullable|boolean',
            'recurring_days' => 'nullable|array',
            'recurring_days.*' => 'integer|min:1|max:7',
            'recurring_start_time' => 'nullable|date_format:H:i',
            'recurring_end_time' => 'nullable|date_format:H:i|after:recurring_start_time',
        ], [
            'code.unique' => 'Mã khuyến mãi này đã tồn tại trong hệ thống.',
            'value.required' => 'Giá trị khuyến mãi là bắt buộc.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'type.required' => 'Vui lòng chọn loại khuyến mãi.',
            'recurring_end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ]);

        $data = $request->all();

        // Tự sinh mã code nếu người dùng để trống
        if (empty($data['code'])) {
            do {
                $data['code'] = 'KM-' . strtoupper(Str::random(6));
            } while (Promotion::where('code', $data['code'])->exists());
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['used_count'] = 0;

        $data['is_recurring'] = $request->has('is_recurring') ? 1 : 0;
        if (!$data['is_recurring']) {
            // Nếu không phải khuyến mãi lặp, xoá toàn bộ cấu hình lặp
            $data['recurring_days'] = null;
            $data['recurring_start_time'] = null;
            $data['recurring_end_time'] = null;
        }

        // Với khuyến mãi giảm cố định thì không cần giới hạn số tiền giảm tối đa
        if ($data['type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã tạo khuyến mãi thành công!');
    }

    /**
     * FORM CHỈNH SỬA
     */
    public function edit(Promotion $promotion)
    {
        return view('backend.promotions.edit', compact('promotion'));
    }

    /**
     * CẬP NHẬT KHUYẾN MÃI
     */
    public function update(Request $request, Promotion $promotion)
    {
        // Validate trước khi cập nhật để tránh dữ liệu sai định dạng
        $request->validate([
            'code' => 'nullable|string|max:10|unique:promotions,code,' . $promotion->id,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'description' => 'nullable|string|max:100',
            'apply_for' => 'required|in:all,new,silver,gold,diamond',
            'is_recurring' => 'nullable|boolean',
            'recurring_days' => 'nullable|array',
            'recurring_days.*' => 'integer|min:1|max:7',
            'recurring_start_time' => 'nullable|date_format:H:i',
            'recurring_end_time' => 'nullable|date_format:H:i|after:recurring_start_time',
        ], [
            'code.unique' => 'Mã khuyến mãi này đã tồn tại trong hệ thống.',
            'value.required' => 'Giá trị khuyến mãi là bắt buộc.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'recurring_end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ]);

        $data = $request->all();

        // Giữ lại mã cũ nếu người dùng không nhập mã mới
        if (empty($data['code'])) {
            $data['code'] = $promotion->code;
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_recurring'] = $request->has('is_recurring') ? 1 : 0;

        if (!$data['is_recurring']) {
            // Xoá dữ liệu lặp nếu người dùng tắt chế độ recurring
            $data['recurring_days'] = null;
            $data['recurring_start_time'] = null;
            $data['recurring_end_time'] = null;
        }

        // Khuyến mãi giảm cố định không cần giới hạn tối đa
        if ($data['type'] === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        $promotion->update($data);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã cập nhật khuyến mãi thành công!');
    }

    /**
     * XÓA KHUYẾN MÃI
     */
    public function destroy(Promotion $promotion)
    {
        // Xoá bản ghi và trả JSON cho request AJAX từ giao diện
        $promotion->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa khuyến mãi thành công!']);
    }

    /**
     * XÓA NHIỀU KHUYẾN MÃI
     */
    public function bulkDelete(Request $request)
    {
        // Chế độ xoá toàn bộ kết quả đang lọc
        if ($request->input('delete_all_pages') == '1') {
            $query = Promotion::query();

            // Giữ đồng nhất với bộ lọc đang hiển thị trên bảng
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            $promotions = $query->get();
            $deletedCount = $promotions->count();
            // Xoá từng bản ghi để dễ mở rộng logic sau này nếu cần hook riêng
            foreach ($promotions as $promo) {
                $promo->delete();
            }

            return redirect()->route('admin.promotions.index')
                ->with('success', "Đã xóa {$deletedCount} khuyến mãi thành công!");
        }

        // Chế độ xoá các ID người dùng tick chọn
        $ids = $request->input('promotion_ids', []);
        if (empty($ids)) {
            return redirect()->route('admin.promotions.index')
                ->with('error', 'Vui lòng chọn ít nhất một khuyến mãi.');
        }

        $count = Promotion::whereIn('id', $ids)->count();
            // Đếm trước để hiển thị đúng số bản ghi đã xoá
        Promotion::whereIn('id', $ids)->delete();

        return redirect()->route('admin.promotions.index')
            ->with('success', "Đã xóa {$count} khuyến mãi thành công!");
    }
}
