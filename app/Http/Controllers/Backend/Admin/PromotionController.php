<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\PromotionComboItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PromotionController
{
    // HIỂN THỊ DANH SÁCH KHUYẾN MÃI
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
        // Lọc theo loại khuyến mãi, percent / fixed
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        // Lọc theo kênh áp dụng, tại quầy / giao hàng / mọi kênh
        if ($request->filled('applies_to') && $request->applies_to !== 'all') {
            $query->where('applies_to', $request->applies_to);
        }
        // Lọc theo yêu cầu xác nhận nhân viên, cần xác nhận / tự động
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
        return view('backend.admin.promotions.index', compact(
            'promotions', //ds
            'totalPromotions', //tong
            'activePromotions', //ma hoat dong
            'expiredPromotions' // ma het han
        ));
    }

    // FORM THÊM MỚI
    public function create()
    {
        return view('backend.admin.promotions.create', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    //tự gán mặc định scope = 'order'
    private function applyDefaultScope(Request $request): void
    {
        if (!$request->filled('scope')) {
            $request->merge(['scope' => 'order']);
        }
    }

    // trả về bộ quy tắc validate dùng chung cho cả tạo mới
    private function validationRules(?int $promotionId = null): array
    {
        $codeUnique = 'unique:promotions,code' . ($promotionId ? ",{$promotionId}" : '');

        return [
            'code' => "nullable|string|max:20|{$codeUnique}",
            'scope' => 'required|in:order,product,category,combo',
            'type' => 'required_unless:scope,combo|nullable|in:percent,fixed',
            'value' => 'required_unless:scope,combo|nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'description' => 'nullable|string|max:100',
            'apply_for' => 'required|in:all,new,silver,gold,diamond',
            'applies_to' => 'required|in:all,pickup,delivery',
            'is_recurring' => 'nullable|boolean',
            'recurring_days' => 'nullable|array',
            'recurring_days.*' => 'integer|min:1|max:7',
            'recurring_start_time' => 'nullable|date_format:H:i',
            'recurring_end_time' => 'nullable|date_format:H:i|after:recurring_start_time',
            'product_ids' => 'required_if:scope,product|nullable|array|min:1',
            'product_ids.*' => 'integer|exists:products,id',
            'category_ids' => 'required_if:scope,category|nullable|array|min:1',
            'category_ids.*' => 'integer|exists:categories,id',
            'combo_product_ids' => 'required_if:scope,combo|nullable|array|min:1',
            'combo_product_ids.*' => 'integer|exists:products,id|distinct',
            'combo_quantities' => 'required_if:scope,combo|nullable|array|min:1',
            'combo_quantities.*' => 'integer|min:1',
            'combo_has_discount' => 'nullable|boolean',
            'combo_has_gift' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'combo_max_discount_amount' => 'nullable|numeric|min:0',
            'gift_product_id' => 'nullable|integer|exists:products,id',
            'gift_quantity' => 'nullable|integer|min:1',
            'max_applications_per_order' => 'nullable|integer|min:1',
            'auto_add_gift' => 'nullable|boolean',
            'requires_staff_verification' => 'nullable|boolean',
        ];
    }

    // ghi đè thông báo lỗi tiếng Việt cho một số rule trong
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
            'combo_product_ids.required_if' => 'Vui lòng chọn ít nhất 1 sản phẩm cho combo.',
            'combo_product_ids.*.distinct' => 'Mỗi sản phẩm chỉ được chọn 1 lần trong combo.',
            'combo_quantities.required_if' => 'Vui lòng nhập số lượng cho từng sản phẩm trong combo.',
        ];
    }

    //kiểm tra thêm mấy luật riêng cho Combo mà rule string thường không làm được
    private function validateComboRules($validator, Request $request): void
    {
        // Chỉ áp dụng cho combo, các scope khác bỏ qua
        if ($request->input('scope') !== 'combo') {
            return;
        }

        // mảng sản phẩm/số lượng phải cùng độ dài
        $productIds = $request->input('combo_product_ids', []);
        $quantities = $request->input('combo_quantities', []);
        if (count($productIds) !== count($quantities)) {
            $validator->errors()->add('combo_product_ids', 'Danh sách sản phẩm và số lượng combo không khớp.');
        }

        $hasDiscount = $request->boolean('combo_has_discount');
        $hasGift = $request->boolean('combo_has_gift');

        // Phải bật ít nhất 1 trong 2 thành phần thưởng, không
        if (!$hasDiscount && !$hasGift) {
            $validator->errors()->add('combo_has_discount', 'Combo phải có ít nhất giảm giá hoặc tặng quà.');
        }

        // Bật giảm giá thì bắt buộc điền loại + giá trị, percent
        if ($hasDiscount) {
            if (!$request->filled('discount_type')) {
                $validator->errors()->add('discount_type', 'Vui lòng chọn loại giảm giá cho combo.');
            }
            if (!$request->filled('discount_value')) {
                $validator->errors()->add('discount_value', 'Vui lòng nhập giá trị giảm giá cho combo.');
            } elseif ($request->input('discount_type') === 'percent' && (float) $request->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'Phần trăm giảm giá không được vượt quá 100.');
            }
        }

        // Bật tặng quà thì bắt buộc chọn sản phẩm + số lượng
        if ($hasGift) {
            if (!$request->filled('gift_product_id')) {
                $validator->errors()->add('gift_product_id', 'Vui lòng chọn sản phẩm tặng.');
            }
            if (!$request->filled('gift_quantity')) {
                $validator->errors()->add('gift_quantity', 'Vui lòng nhập số lượng tặng.');
            }
        }
    }

    // hàm gộp, gọi tuần tự 3 bước validate
    private function validateRequest(Request $request, ?int $promotionId = null): void
    {
        $this->dropEmptyComboRows($request);////dọn sạch các dòng combo chưa chọn sản phẩm

        ////trả về bộ quy tắc validate dùng chung cho cả tạo mới lẫn sửa khuyến mãi
        $validator = Validator::make($request->all(), $this->validationRules($promotionId), $this->validationMessages());
        $validator->after(fn($v) => $this->validateComboRules($v, $request));  //kiểm tra thêm mấy luật riêng cho Combo
        $validator->validate();
    }

    // dọn sạch các dòng combo chưa chọn sản phẩm
    private function dropEmptyComboRows(Request $request): void
    {
        $productIds = $request->input('combo_product_ids');
        if (!is_array($productIds)) {
            return;
        }

        $quantities = $request->input('combo_quantities');
        $quantities = is_array($quantities) ? $quantities : [];

        // Chỉ bỏ đúng CẶP, sản phẩm + số lượng của dòng trống.
        $keptIds = [];
        foreach ($productIds as $index => $productId) {
            if ($productId === null || $productId === '') {
                unset($quantities[$index]);
                continue;
            }
            $keptIds[] = $productId;
        }
        $keptQuantities = array_values($quantities);

        // Không còn dòng nào -> trả null chứ KHÔNG phải mảng
        $request->merge([
            'combo_product_ids' => $keptIds === [] ? null : $keptIds,
            'combo_quantities' => $keptIds === [] ? null : $keptQuantities,
        ]);
    }

    // Các field không phải cột trực tiếp của bảng promotions
    private function comboOnlyFields(): array
    {
        return [
            'product_ids',
            'category_ids',
            'combo_product_ids',
            'combo_quantities',
            'combo_has_discount',
            'combo_has_gift',
            'discount_type',
            'discount_value',
            'combo_max_discount_amount',
            'gift_product_id',
            'gift_quantity',
            'max_applications_per_order',
            'auto_add_gift',
        ];
    }

    // dọn sạch mảng dữ liệu form trước khi ghi vào bảng promotions
    private function normalizePromotionData(array $data): array
    {
        unset($data['product_ids'], $data['category_ids']);
        unset(
            $data['combo_product_ids'],
            $data['combo_quantities'],
            $data['combo_has_discount'],
            $data['combo_has_gift'],
            $data['discount_type'],
            $data['discount_value'],
            $data['combo_max_discount_amount'],
            $data['gift_product_id'],
            $data['gift_quantity'],
            $data['max_applications_per_order'],
            $data['auto_add_gift']
        );

        if ($data['scope'] === 'combo') {
            $data['type'] = 'fixed';
            $data['value'] = 0;
        }

        return $data;
    }

    // Đồng bộ quan hệ sản phẩm danh mục cấu hình combo theo
    private function syncScopeRelations(Promotion $promotion, Request $request): void
    {
        $promotion->products()->sync($request->input('scope') === 'product' ? $request->input('product_ids', []) : []);
        $promotion->categories()->sync($request->input('scope') === 'category' ? $request->input('category_ids', []) : []);

        if ($request->input('scope') === 'combo') {
            $hasDiscount = $request->boolean('combo_has_discount');
            $hasGift = $request->boolean('combo_has_gift');
            PromotionCombo::updateOrCreate(
                ['promotion_id' => $promotion->id],
                [
                    'discount_type' => $hasDiscount ? $request->input('discount_type') : null,
                    'discount_value' => $hasDiscount ? $request->input('discount_value') : null,
                    'max_discount_amount' => ($hasDiscount && $request->input('discount_type') === 'percent')
                        ? ($request->input('combo_max_discount_amount') ?: null) : null,
                    'gift_product_id' => $hasGift ? $request->input('gift_product_id') : null,
                    'gift_quantity' => $hasGift ? $request->input('gift_quantity') : null,
                    'max_applications_per_order' => $request->input('max_applications_per_order') ?: null,
                    'auto_add_gift' => $request->boolean('auto_add_gift', true),
                ]
            );
            // giữ số lượng của lần chọn
            $productIds = $request->input('combo_product_ids', []);
            $quantities = $request->input('combo_quantities', []);
            $items = [];
            foreach ($productIds as $i => $productId) {
                if (!$productId) {
                    continue;
                }
                $items[(int) $productId] = (int) ($quantities[$i] ?? 1);
            }
            $promotion->comboItems()->delete();
            foreach ($items as $productId => $quantity) {
                PromotionComboItem::create([
                    'promotion_id' => $promotion->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }
        } else {
            $promotion->combo()->delete();
            $promotion->comboItems()->delete();
        }
    }

    // LƯU KHUYẾN MÃI MỚI
    public function store(Request $request)
    {
        $this->applyDefaultScope($request);////tự gán mặc định scope = order
        $this->validateRequest($request);////  hàm gộp gọi tuần tự 3 bước validate

        // //dọn sạch mảng dữ liệu form trước khi ghi vào bảng
        $data = $this->normalizePromotionData($request->except($this->comboOnlyFields()));

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

        // Với khuyến mãi giảm cố định thì không cần giới hạn số
        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount_amount'] = null;
        }

        DB::transaction(function () use ($request, $data) {
            $promotion = Promotion::create($data);
            $this->syncScopeRelations($promotion, $request);// // Đồng bộ quan hệ sản phẩm/danh mục/cấu hình combo theo ĐÚNG scope hiện tại
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã tạo khuyến mãi thành công!');
    }

    // FORM CHỈNH SỬA
    public function edit(Promotion $promotion)
    {
        $promotion->load(['products', 'categories', 'combo.giftProduct', 'comboItems.product']);

        return view('backend.admin.promotions.edit', [
            'promotion' => $promotion,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    // CẬP NHẬT KHUYẾN MÃI
    public function update(Request $request, Promotion $promotion)
    {
        $this->applyDefaultScope($request);////tự gán mặc định scope = 'order'
        $this->validateRequest($request, $promotion->id);// // hàm gộp, gọi tuần tự 3 bước validate

        $data = $this->normalizePromotionData($request->except($this->comboOnlyFields()));

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
            $this->syncScopeRelations($promotion, $request);// // Đồng bộ quan hệ sản phẩm/danh mục/cấu hình combo theo ĐÚNG scope hiện tại
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Đã cập nhật khuyến mãi thành công!');
    }

    // XÓA KHUYẾN MÃI
    public function destroy(Promotion $promotion)
    {
        DB::transaction(function () use ($promotion) {
            $promotion->delete();
        });

        return redirect()->route('admin.promotions.index')->with('success', 'Đã xóa khuyến mãi thành công!');
    }

    // XÓA NHIỀU KHUYẾN MÃI chỉ các dòng đang chọn trong
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('promotion_ids', []);
        if (empty($ids)) {
            return redirect()->route('admin.promotions.index')
                ->with('error', 'Vui lòng chọn ít nhất một khuyến mãi.');
        }

        $count = Promotion::whereIn('id', $ids)->count();
        // Đếm trước để hiển thị đúng số bản ghi đã xoá
        DB::transaction(function () use ($ids) {
            Promotion::whereIn('id', $ids)->delete();
        });

        return redirect()->route('admin.promotions.index')
            ->with('success', "Đã xóa {$count} khuyến mãi thành công!");
    }
}
