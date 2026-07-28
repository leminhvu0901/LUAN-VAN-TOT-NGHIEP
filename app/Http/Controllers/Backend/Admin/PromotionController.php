<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionBuyXGetY;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromotionController
{
    /**
     * HIỂN THỊ DANH SÁCH KHUYẾN MÃI
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

        // Lọc theo kênh áp dụng (tại quầy / giao hàng / mọi kênh)
        if ($request->filled('applies_to') && $request->applies_to !== 'all') {
            $query->where('applies_to', $request->applies_to);
        }

        // Lọc theo yêu cầu xác nhận nhân viên (cần xác nhận / tự động)
        if ($request->filled('verification') && $request->verification !== 'all') {
            $query->where('requires_staff_verification', $request->verification === 'yes' ? true : false);
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

        // Lấy tất cả IDs trước khi thực hiện paginate nếu được yêu cầu
        if ($request->ajax() && $request->input('fetch_all_ids') == '1') {
            return response()->json([
                'ids' => $query->pluck('id')->toArray()
            ]);
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
            $html =  view('backend.admin.promotions.partials.table', compact('promotions'))->render();
            return response()->json([
                'html' => $html,
                // JS đang đọc data.total để cập nhật input ẩn phục vụ xoá hàng loạt
                'total' => $promotions->total(),
                'count_text' => 'Hiển thị ' . $promotions->count() . ' / ' . $promotions->total() . ' khuyến mãi',
                'stats' => [
                    'total' => number_format($totalPromotions),
                    'active' => number_format($activePromotions),
                    'expired' => number_format($expiredPromotions),
                ]
            ]);
        }

            // Trả dữ liệu danh sách + thống kê sang Blade để render giao diện
            return  view('backend.admin.promotions.index', compact(
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
        return view('backend.admin.promotions.create', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    // Không gửi 'scope' -> mặc định 'order' (giảm toàn đơn), khớp với giá trị mặc định của cột trong
    // DB. Giữ tương thích ngược cho mọi nơi gọi cũ chưa biết tới khái niệm phạm vi (form/API/test
    // trước khi có tính năng này), thay vì bắt buộc và làm hỏng chúng.
    private function applyDefaultScope(Request $request): void
    {
        if (!$request->filled('scope')) {
            $request->merge(['scope' => 'order']);
        }
    }

    // Validate chung cho store + update. $promotionId dùng để loại trừ chính bản ghi khi kiểm tra
    // unique code lúc sửa.
    private function validationRules(?int $promotionId = null): array
    {
        $codeUnique = 'unique:promotions,code' . ($promotionId ? ",{$promotionId}" : '');

        return [
            'code' => "nullable|string|max:20|{$codeUnique}",
            // Phạm vi áp dụng — quyết định field nào bên dưới là bắt buộc.
            'scope' => 'required|in:order,product,category,buy_x_get_y',
            // type/value KHÔNG bắt buộc khi Mua X tặng Y (không phải giảm giá tiền).
            'type' => 'required_unless:scope,buy_x_get_y|nullable|in:percent,fixed',
            'value' => 'required_unless:scope,buy_x_get_y|nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'description' => 'nullable|string|max:100',
            'apply_for' => 'required|in:all,new,silver,gold,diamond',
            // Kênh áp dụng: tất cả / chỉ đơn tại quầy / chỉ đơn giao hàng.
            'applies_to' => 'required|in:all,pickup,delivery',
            'is_recurring' => 'nullable|boolean',
            'recurring_days' => 'nullable|array',
            'recurring_days.*' => 'integer|min:1|max:7',
            'recurring_start_time' => 'nullable|date_format:H:i',
            'recurring_end_time' => 'nullable|date_format:H:i|after:recurring_start_time',
            // Giảm giá theo sản phẩm (scope=product).
            'product_ids' => 'required_if:scope,product|nullable|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            // Giảm giá theo danh mục (scope=category).
            'category_ids' => 'required_if:scope,category|nullable|array|min:1',
            'category_ids.*' => 'integer|exists:categories,id',
            // Mua X tặng Y (scope=buy_x_get_y).
            'buy_product_id' => 'required_if:scope,buy_x_get_y|nullable|integer|exists:products,id',
            'buy_quantity' => 'required_if:scope,buy_x_get_y|nullable|integer|min:1',
            'gift_product_id' => 'required_if:scope,buy_x_get_y|nullable|integer|exists:products,id',
            'gift_quantity' => 'required_if:scope,buy_x_get_y|nullable|integer|min:1',
            'max_applications_per_order' => 'nullable|integer|min:1',
            'auto_add_gift' => 'nullable|boolean',
            // Mã cần nhân viên xác nhận thủ công — không được tự động áp vào đơn.
            'requires_staff_verification' => 'nullable|boolean',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'code.unique' => 'Mã khuyến mãi này đã tồn tại trong hệ thống.',
            'value.required_unless' => 'Giá trị khuyến mãi là bắt buộc.',
            'type.required_unless' => 'Vui lòng chọn loại giảm giá.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'scope.required' => 'Vui lòng chọn phạm vi áp dụng.',
            'recurring_end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'applies_to.required' => 'Vui lòng chọn kênh áp dụng.',
            'applies_to.in' => 'Kênh áp dụng không hợp lệ.',
            'product_ids.required_if' => 'Vui lòng chọn ít nhất 1 sản phẩm áp dụng.',
            'category_ids.required_if' => 'Vui lòng chọn ít nhất 1 danh mục áp dụng.',
            'buy_product_id.required_if' => 'Vui lòng chọn sản phẩm cần mua.',
            'buy_quantity.required_if' => 'Vui lòng nhập số lượng cần mua (X).',
            'gift_product_id.required_if' => 'Vui lòng chọn sản phẩm tặng.',
            'gift_quantity.required_if' => 'Vui lòng nhập số lượng tặng (Y).',
        ];
    }

    // Cắt các field không thuộc cột trực tiếp của bảng promotions (mảng chọn sản phẩm/danh mục,
    // cấu hình mua-tặng) ra khỏi $data trước khi mass-assign — Eloquent sẽ lỗi nếu cố insert thẳng
    // 1 mảng vào cột không tồn tại. Đồng thời chuẩn hóa các cột còn lại theo scope đã chọn.
    private function normalizePromotionData(array $data): array
    {
        unset($data['product_ids'], $data['category_ids']);
        unset($data['buy_product_id'], $data['buy_quantity'], $data['gift_product_id'], $data['gift_quantity'], $data['max_applications_per_order'], $data['auto_add_gift']);

        if ($data['scope'] === 'buy_x_get_y') {
            // type/value không có ý nghĩa với Mua X tặng Y (không phải giảm giá tiền) — cột `type`
            // trong DB là NOT NULL nên đặt giá trị trung tính, không nơi nào trong hệ thống đọc lại
            // 2 giá trị này khi scope=buy_x_get_y (PromotionService loại scope này khỏi mọi phép tính
            // giảm giá tiền).
            $data['type'] = 'fixed';
            $data['value'] = 0;
        }

        return $data;
    }

    // Đồng bộ quan hệ sản phẩm/danh mục/cấu hình mua-tặng theo ĐÚNG scope hiện tại — dọn sạch dữ
    // liệu quan hệ cũ không còn phù hợp khi admin đổi loại khuyến mãi (vd từ "theo sản phẩm" sang
    // "theo danh mục" thì phải xóa hết sản phẩm đã chọn trước đó).
    private function syncScopeRelations(Promotion $promotion, Request $request): void
    {
        $promotion->products()->sync($request->input('scope') === 'product' ? $request->input('product_ids', []) : []);
        $promotion->categories()->sync($request->input('scope') === 'category' ? $request->input('category_ids', []) : []);

        if ($request->input('scope') === 'buy_x_get_y') {
            PromotionBuyXGetY::updateOrCreate(
                ['promotion_id' => $promotion->id],
                [
                    'buy_product_id' => $request->input('buy_product_id'),
                    'buy_quantity' => $request->input('buy_quantity'),
                    'gift_product_id' => $request->input('gift_product_id'),
                    'gift_quantity' => $request->input('gift_quantity'),
                    'max_applications_per_order' => $request->input('max_applications_per_order') ?: null,
                    'auto_add_gift' => $request->boolean('auto_add_gift', true),
                ]
            );
        } else {
            $promotion->buyXGetYRule()->delete();
        }
    }

    /**
     * LƯU KHUYẾN MÃI MỚI
     */
    public function store(Request $request)
    {
        $this->applyDefaultScope($request);
        $request->validate($this->validationRules(), $this->validationMessages());

        $data = $this->normalizePromotionData($request->except(['product_ids', 'category_ids', 'buy_product_id', 'buy_quantity', 'gift_product_id', 'gift_quantity', 'max_applications_per_order', 'auto_add_gift']));

        // Tự sinh mã code nếu người dùng để trống
        if (empty($data['code'])) {
            do {
                $data['code'] = 'KM-' . strtoupper(Str::random(6));
            } while (Promotion::where('code', $data['code'])->exists());
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['requires_staff_verification'] = $request->has('requires_staff_verification') ? 1 : 0;
        $data['used_count'] = 0;

        $data['is_recurring'] = $request->has('is_recurring') ? 1 : 0;
        if (!$data['is_recurring']) {
            // Nếu không phải khuyến mãi lặp, xoá toàn bộ cấu hình lặp
            $data['recurring_days'] = null;
            $data['recurring_start_time'] = null;
            $data['recurring_end_time'] = null;
        }

        // Với khuyến mãi giảm cố định thì không cần giới hạn số tiền giảm tối đa
        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        DB::transaction(function () use ($request, $data) {
            $promotion = Promotion::create($data);
            $this->syncScopeRelations($promotion, $request);
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã tạo khuyến mãi thành công!');
    }

    /**
     * FORM CHỈNH SỬA
     */
    public function edit(Promotion $promotion)
    {
        $promotion->load(['products', 'categories', 'buyXGetYRule']);

        return view('backend.admin.promotions.edit', [
            'promotion' => $promotion,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    /**
     * CẬP NHẬT KHUYẾN MÃI
     */
    public function update(Request $request, Promotion $promotion)
    {
        $this->applyDefaultScope($request);
        $request->validate($this->validationRules($promotion->id), $this->validationMessages());

        $data = $this->normalizePromotionData($request->except(['product_ids', 'category_ids', 'buy_product_id', 'buy_quantity', 'gift_product_id', 'gift_quantity', 'max_applications_per_order', 'auto_add_gift']));

        // Giữ lại mã cũ nếu người dùng không nhập mã mới
        if (empty($data['code'])) {
            $data['code'] = $promotion->code;
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['requires_staff_verification'] = $request->has('requires_staff_verification') ? 1 : 0;
        $data['is_recurring'] = $request->has('is_recurring') ? 1 : 0;

        if (!$data['is_recurring']) {
            // Xoá dữ liệu lặp nếu người dùng tắt chế độ recurring
            $data['recurring_days'] = null;
            $data['recurring_start_time'] = null;
            $data['recurring_end_time'] = null;
        }

        // Khuyến mãi giảm cố định không cần giới hạn tối đa
        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        DB::transaction(function () use ($request, $promotion, $data) {
            $promotion->update($data);
            $this->syncScopeRelations($promotion, $request);
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã cập nhật khuyến mãi thành công!');
    }

    /**
     * XÓA KHUYẾN MÃI
     */
    public function destroy(Promotion $promotion)
    {
        // promotion_products/promotion_categories/promotion_buy_x_get_y đều cascadeOnDelete theo
        // promotion_id nên tự dọn sạch quan hệ khi xóa, không cần code riêng.
        DB::transaction(function () use ($promotion) {
            $promotion->delete();
        });

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

            if ($request->filled('applies_to') && $request->applies_to !== 'all') {
                $query->where('applies_to', $request->applies_to);
            }

            $promotions = $query->get();
            $deletedCount = $promotions->count();
            DB::transaction(function () use ($promotions) {
                // Xoá từng bản ghi để dễ mở rộng logic sau này nếu cần hook riêng
                foreach ($promotions as $promo) {
                    $promo->delete();
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã xóa {$deletedCount} khuyến mãi thành công!"
                ]);
            }

            return redirect()->route('admin.promotions.index')
                ->with('success', "Đã xóa {$deletedCount} khuyến mãi thành công!");
        }

        // Chế độ xoá các ID người dùng tick chọn
        $ids = $request->input('promotion_ids', []);
        if (empty($ids)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn ít nhất một khuyến mãi.'
                ], 400);
            }
            return redirect()->route('admin.promotions.index')
                ->with('error', 'Vui lòng chọn ít nhất một khuyến mãi.');
        }

        $count = Promotion::whereIn('id', $ids)->count();
        // Đếm trước để hiển thị đúng số bản ghi đã xoá
        DB::transaction(function () use ($ids) {
            Promotion::whereIn('id', $ids)->delete();
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$count} khuyến mãi thành công!"
            ]);
        }

        return redirect()->route('admin.promotions.index')
            ->with('success', "Đã xóa {$count} khuyến mãi thành công!");
    }
}
