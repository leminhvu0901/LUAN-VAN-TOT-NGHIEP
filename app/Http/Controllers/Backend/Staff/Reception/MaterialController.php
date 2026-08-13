<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Material;
use App\Models\MaterialImport;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;


class MaterialController
{
    private const MAX_UNIT_PRICE = 999999999; // Hạn mức giá tối đa để tránh lỗi số quá lớn trong Database

    // Inject dịch vụ quản lý kho nguyên liệu và cập nhật tồn kho
    public function __construct(private readonly InventoryService $sv_inventory)
    {
    }

    // Lấy danh sách Vật tư có lọc và phân trang
    public function index(Request $request)
    {
        // Khởi tạo truy vấn kèm theo các lô hàng còn tồn và đếm
        $query = Material::with([
            'imports' => function ($q) {
                $q->where('remaining_quantity', '>', 0); // Chỉ nạp các lô còn hàng
            }
        ])->withCount([
                    'imports as active_lots_count' => function ($q) {
                        $q->where('quantity', '>', 0)
                            ->where('remaining_quantity', '>', 0); // Lô nhập đang còn nguyên liệu
                    },
                    'imports as disposed_count' => function ($q) {
                        $q->where('quantity', '<', 0); // Đếm các lô bị xuất hủy/bỏ
                    }
                ]);

        $this->applyMaterialFilters($query, $request); // Áp dụng các điều kiện tìm kiếm/lọc từ giao diện gửi lên

        // Xử lý sắp xếp danh sách vật tư theo yêu cầu
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc'); // Cũ nhất trước
                    break;
                case 'stock_asc':
                    $query->orderBy('current_stock', 'asc'); // Tồn kho ít nhất trước
                    break;
                case 'stock_desc':
                    $query->orderBy('current_stock', 'desc'); // Tồn kho nhiều nhất trước
                    break;
                default: // newest
                    $query->orderBy('created_at', 'desc'); // Mặc định là mới nhất trước
                    break;
            }
        } else {
            $query->latest();
        }

        $materials = $query->paginate(10)->withQueryString(); // Phân trang 10 vật tư mỗi trang

        // Thu thập số liệu thống kê chung cho các thẻ báo cáo ở
        $totalItems = Material::count(); // Tổng số loại vật tư trong hệ thống
        $lowStockItems = Material::where('current_stock', '<', 5)->where('current_stock', '>', 0)->count(); // Số vật tư sắp hết (dưới 5)
        $outOfStockItems = Material::where('current_stock', 0)->count(); // Số vật tư đã hết hàng hoàn toàn

        // Số lượng lô hàng sắp hết hạn trong 30 ngày tới
        $expiringItems = MaterialImport::whereBetween('expiration_date', [now(), now()->addDays(30)])
            ->where('remaining_quantity', '>', 0)->count();
        // Số lượng lô hàng đã quá hạn sử dụng mà chưa dùng hết
        $expiredItems = MaterialImport::where('expiration_date', '<', today())
            ->where('remaining_quantity', '>', 0)->count();

        $disposedBatchesCount = MaterialImport::where('quantity', '<', 0)->count(); // Số lần xuất hủy
        $disposedValue = abs(MaterialImport::where('quantity', '<', 0)->sum('total_price')); // Tổng giá trị tiền của lượng vật tư bị hủy

        $totalValue = (float) Material::query()->sum(DB::raw('current_stock * unit_price')); // Tính tổng trị giá tồn kho hiện tại

        return view('backend.staff.reception.materials.index', compact(
            'materials',
            'totalItems',
            'lowStockItems',
            'outOfStockItems',
            'expiringItems',
            'expiredItems',
            'disposedBatchesCount',
            'totalValue',
            'disposedValue'
        ));
    }

    // Hiển thị Lịch sử Nhập/Xuất của một Vật tư cụ thể
    public function imports(Material $material)
    {
        $imports = $material->imports()->latest()->get(); // Lấy lịch sử nhập/xuất của nguyên liệu này
        $activeLotsCount = $imports
            ->where('quantity', '>', 0)
            ->where('remaining_quantity', '>', 0) // Lọc các lô hàng thực nhập còn tồn
            ->count();

        return view('backend.staff.reception.materials.imports', compact('material', 'imports', 'activeLotsCount'));
    }

    // Tạo Phiếu Nhập Kho Mới cho một nguyên liệu cụ thể
    public function storeImport(Request $request, Material $material)
    {
        $request->merge(['_form_context' => 'import-create']);
        $validated = $this->validateImportData($request, today()->toDateString()); // Validate dữ liệu nhập kho

        // Ghi nhận lịch sử thao tác có định danh của tài khoản
        $operator = Auth::user();
        $auditPrefix = sprintf('[Nhân viên: %s (%s)] ', $operator->name, $operator->email);
        $note = $auditPrefix . ($validated['note'] ?? 'Nhập kho');

        $this->sv_inventory->createImportLot( // Gọi inventory service tạo mới lô hàng nhập kho
            $material,
            (string) $validated['quantity'],
            (string) $validated['total_price'],
            $note,
            $validated['expiration_date'] ?? null,
        );

        return redirect()->route('staff.reception.materials.imports', $material)->with('success', 'Đã nhập kho thành công!');
    }

    // Xuất kho trực tiếp từ một lô hàng cụ thể — dùng khi
    public function consumeBatch(Request $request, MaterialImport $import)
    {
        $request->merge([
            '_form_context' => 'consume-batch',
            '_form_action' => route('staff.reception.materials.imports.consume_batch', $import),
            '_lot_id' => $import->id,
            '_unit' => $import->material?->unit,
            '_max_quantity' => $import->remaining_quantity,
            'reason' => trim((string) $request->input('reason')),
        ]);

        $validated = $request->validate([ // Validate số lượng xuất dùng và lý do xuất dùng
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'quantity.numeric' => 'Số lượng xuất phải là số hợp lệ.',
            'quantity.decimal' => 'Số lượng xuất chỉ được có tối đa 2 chữ số thập phân.',
            'quantity.min' => 'Số lượng xuất phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng xuất vượt quá giới hạn cho phép.',
            'reason.required' => 'Vui lòng nhập lý do xuất kho.',
        ]);

        $operator = Auth::user(); // Lấy tài khoản nhân viên đang thao tác

        DB::transaction(function () use ($import, $validated, $operator) { // Sử dụng Database Transaction để đảm bảo tính toàn vẹn dữ liệu
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id); // Khóa bản ghi vật tư đề phòng xung đột đồng thời
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id); // Khóa bản ghi lô hàng nhập
            $consumeQty = (float) $validated['quantity'];
            $remainingQuantity = (float) $lockedImport->remaining_quantity;

            if ((float) $lockedImport->quantity <= 0) { // Kiểm tra tính hợp lệ của lô hàng (phải là lô nhập)
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ có thể xuất kho từ một phiếu nhập kho.',
                ]);
            }

            if ($consumeQty > $remainingQuantity || $consumeQty > (float) $material->current_stock) { // Tránh xuất vượt quá số dư tồn của lô
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng xuất không được vượt quá số lượng còn lại của lô ({$remainingQuantity}).",
                ]);
            }

            $unitPrice = abs((float) $lockedImport->total_price / (float) $lockedImport->quantity); // Tính giá vốn trung bình của lô
            $consumeValue = $consumeQty * $unitPrice; // Tổng giá trị hàng xuất ra
            $reason = sprintf('[Nhân viên: %s (%s)] %s', $operator->name, $operator->email, $validated['reason']);

            $lockedImport->update([ // Cập nhật trừ bớt số lượng còn lại của lô gốc
                'remaining_quantity' => $remainingQuantity - $consumeQty,
            ]);

            MaterialImport::create([ // Tạo mới bản ghi mang giá trị âm đại diện cho hành động xuất kho
                'material_id' => $material->id,
                'quantity' => -$consumeQty,
                'remaining_quantity' => 0,
                'total_price' => -$consumeValue,
                'note' => 'Xuất dùng từ lô LOT-' . $lockedImport->id . ': ' . $reason,
                'expiration_date' => $lockedImport->expiration_date,
            ]);

            $material->update([ // Cập nhật trừ bớt tổng tồn kho hiện tại của nguyên liệu đó
                'current_stock' => (float) $material->current_stock - $consumeQty,
            ]);

            // Ghi nhận biến động vào bảng chi tiết biến động kho nếu
            if (Schema::hasTable('inventory_movements'))
                DB::table('inventory_movements')->insert([
                    'material_id' => $material->id,
                    'material_import_id' => $lockedImport->id,
                    'order_id' => null,
                    'type' => 'adjustment',
                    'quantity' => -$consumeQty,
                    'unit_cost' => $unitPrice,
                    'note' => $reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $this->sv_inventory->recalculateMaterialCost($material->id); // Gọi hàm tính toán lại đơn giá trung bình cho nguyên liệu
        });

        return redirect()->route('staff.reception.materials.imports', $import->material_id)->with('success', 'Đã ghi nhận xuất kho từ lô hàng thành công!');
    }

    // Xác thực validate thông tin nhập kho vật tư gửi lên
    private function validateImportData(Request $request, string $importDate): array
    {
        $request->merge([
            'note' => $request->filled('note') ? trim((string) $request->input('note')) : null,
        ]);

        return $request->validate([
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999.99'],
            'total_price' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date_format:Y-m-d', 'after:' . $importDate],
        ], [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.numeric' => 'Số lượng nhập phải là số hợp lệ.',
            'quantity.decimal' => 'Số lượng nhập chỉ được có tối đa 2 chữ số thập phân.',
            'quantity.min' => 'Số lượng nhập phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng nhập phải bé hơn 1000.',
            'total_price.required' => 'Vui lòng nhập tổng tiền thanh toán.',
            'total_price.numeric' => 'Tổng tiền thanh toán phải là số.',
            'total_price.min' => 'Tổng tiền thanh toán phải lớn hơn 0.',
            'total_price.max' => 'Tổng tiền thanh toán vượt quá giới hạn cho phép.',
            'expiration_date.date_format' => 'Hạn sử dụng không đúng định dạng ngày.',
            'expiration_date.after' => 'Hạn sử dụng phải sau ngày nhập kho.',
            'note.max' => 'Ghi chú không được vượt quá 255 ký tự.',
        ]);
    }

    // Bộ lọc tìm kiếm nguyên liệu nâng cao (Lọc theo Tên/Mã
    private function applyMaterialFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $materialId = preg_replace('/^VT[-\s]*0*/iu', '', $search); // Tách lấy ID số từ chuỗi mã vật tư (VD: VT-0004 -> 4)

            $query->where(function ($subQuery) use ($search, $materialId) {
                $subQuery->where('name', 'like', '%' . $search . '%'); // Tìm theo tên nguyên liệu

                if ($materialId !== '' && ctype_digit($materialId)) {
                    $subQuery->orWhere('id', (int) $materialId); // Tìm theo mã ID số
                }
            });
        }

        $status = $request->input('status'); // Lọc theo trạng thái đặc biệt
        if (!$status || $status === 'all') {
            return;
        }

        if ($status === 'low_stock') {
            $query->where('current_stock', '<', 5)->where('current_stock', '>', 0); // Lọc nguyên liệu sắp hết
        } elseif ($status === 'out_of_stock') {
            $query->where('current_stock', 0); // Lọc nguyên liệu đã hết hàng
        } elseif ($status === 'expiring') {
            $query->whereHas('imports', function ($subQuery) { // Lọc nguyên liệu sắp hết hạn trong 30 ngày
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            });
        } elseif ($status === 'expired') {
            $query->whereHas('imports', function ($subQuery) { // Lọc nguyên liệu đã quá hạn sử dụng
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->where('expiration_date', '<', today());
            });
        } elseif ($status === 'disposed') {
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->where('quantity', '<', 0); // Lọc nguyên liệu có ít nhất 1 lô đã bị xuất hủy
            });
        }
    }
}
