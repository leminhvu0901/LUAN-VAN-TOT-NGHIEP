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
    private const MAX_UNIT_PRICE = 999999999;

    public function __construct(private readonly InventoryService $inventory) {}

    // 1. Lấy danh sách Vật tư
    public function index(Request $request)
    {
        $query = Material::with([
            'imports' => function ($q) {
                $q->where('remaining_quantity', '>', 0);
            }
        ])->withCount([
            'imports as active_lots_count' => function ($q) {
                $q->where('quantity', '>', 0)
                    ->where('remaining_quantity', '>', 0);
            },
            'imports as disposed_count' => function ($q) {
                $q->where('quantity', '<', 0);
            }
        ]);

        $this->applyMaterialFilters($query, $request);

        // Sắp xếp
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'stock_asc':
                    $query->orderBy('current_stock', 'asc');
                    break;
                case 'stock_desc':
                    $query->orderBy('current_stock', 'desc');
                    break;
                default: // newest
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->latest();
        }

        $materials = $query->paginate(10)->withQueryString();

        // Thống kê cho các thẻ
        $totalItems = Material::count();
        $lowStockItems = Material::where('current_stock', '<', 5)->where('current_stock', '>', 0)->count();
        $outOfStockItems = Material::where('current_stock', 0)->count();

        $expiringItems = MaterialImport::whereBetween('expiration_date', [now(), now()->addDays(30)])
                                       ->where('remaining_quantity', '>', 0)->count();
        $expiredItems = MaterialImport::where('expiration_date', '<', today())
                                      ->where('remaining_quantity', '>', 0)->count();

        $disposedBatchesCount = MaterialImport::where('quantity', '<', 0)->count();
        $disposedValue = abs(MaterialImport::where('quantity', '<', 0)->sum('total_price'));

        $totalValue = (float) Material::query()->sum(DB::raw('current_stock * unit_price'));

        if ($request->ajax()) {
            $html = view('backend.staff.reception.materials.partials.table', compact('materials'))->render();
            return response()->json([
                'html' => $html,
            ]);
        }

        return view('backend.staff.reception.materials.index', compact(
            'materials', 'totalItems', 'lowStockItems', 'outOfStockItems', 'expiringItems', 'expiredItems', 'disposedBatchesCount', 'totalValue', 'disposedValue'
        ));
    }

    // 2. Hiển thị Lịch sử Nhập/Xuất của một Vật tư
    public function imports(Material $material)
    {
        $imports = $material->imports()->latest()->get();
        $activeLotsCount = $imports
            ->where('quantity', '>', 0)
            ->where('remaining_quantity', '>', 0)
            ->count();

        return view('backend.staff.reception.materials.imports', compact('material', 'imports', 'activeLotsCount'));
    }

    // 3. Tạo Phiếu Nhập Kho Mới
    public function storeImport(Request $request, Material $material)
    {
        $request->merge(['_form_context' => 'import-create']);
        $validated = $this->validateImportData($request, today()->toDateString());

        // Ghi nhận audit tài khoản nhân viên qua note
        $operator = Auth::user();
        $auditPrefix = sprintf('[Nhân viên: %s (%s)] ', $operator->name, $operator->email);
        $note = $auditPrefix . ($validated['note'] ?? 'Nhập kho');

        $this->inventory->createImportLot(
            $material,
            (string) $validated['quantity'],
            (string) $validated['total_price'],
            $note,
            $validated['expiration_date'] ?? null,
        );

        return redirect()->route('staff.reception.materials.imports', $material)->with('success', 'Đã nhập kho thành công!');
    }

    // Xuất kho sử dụng (lấy vật tư ra khỏi kho để dùng trực tiếp tại quầy, không qua đơn hàng) - LUÔN
    // từ một lô cụ thể do người dùng chọn (nút "Xuất" trên từng dòng lô ở bảng Lịch sử Nhập kho), y hệt
    // cách admin làm (MaterialController(Admin)::consumeBatch()) - không còn bản "tự động chọn lô theo
    // hạn dùng gần nhất" như trước, vì trang này giờ đã thấy rõ danh sách lô để chọn đúng, không cần
    // thêm 1 con đường mơ hồ không biết trừ từ lô nào nữa.
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

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999.99'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'quantity.numeric' => 'Số lượng xuất phải là số hợp lệ.',
            'quantity.decimal' => 'Số lượng xuất chỉ được có tối đa 2 chữ số thập phân.',
            'quantity.min' => 'Số lượng xuất phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng xuất vượt quá giới hạn cho phép.',
            'reason.required' => 'Vui lòng nhập lý do xuất kho.',
        ]);

        $operator = Auth::user();

        DB::transaction(function () use ($import, $validated, $operator) {
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);
            $consumeQty = (float) $validated['quantity'];
            $remainingQuantity = (float) $lockedImport->remaining_quantity;

            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ có thể xuất kho từ một phiếu nhập kho.',
                ]);
            }

            if ($consumeQty > $remainingQuantity || $consumeQty > (float) $material->current_stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng xuất không được vượt quá số lượng còn lại của lô ({$remainingQuantity}).",
                ]);
            }

            $unitPrice = abs((float) $lockedImport->total_price / (float) $lockedImport->quantity);
            $consumeValue = $consumeQty * $unitPrice;
            $reason = sprintf('[Nhân viên: %s (%s)] %s', $operator->name, $operator->email, $validated['reason']);

            $lockedImport->update([
                'remaining_quantity' => $remainingQuantity - $consumeQty,
            ]);

            MaterialImport::create([
                'material_id' => $material->id,
                'quantity' => -$consumeQty,
                'remaining_quantity' => 0,
                'total_price' => -$consumeValue,
                'note' => 'Xuất dùng từ lô LOT-' . $lockedImport->id . ': ' . $reason,
                'expiration_date' => $lockedImport->expiration_date,
            ]);

            $material->update([
                'current_stock' => (float) $material->current_stock - $consumeQty,
            ]);

            if (Schema::hasTable('inventory_movements')) DB::table('inventory_movements')->insert([
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
            $this->inventory->recalculateMaterialCost($material->id);
        });

        return redirect()->route('staff.reception.materials.imports', $import->material_id)->with('success', 'Đã ghi nhận xuất kho từ lô hàng thành công!');
    }

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

    private function applyMaterialFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $materialId = preg_replace('/^VT[-\s]*0*/iu', '', $search);

            $query->where(function ($subQuery) use ($search, $materialId) {
                $subQuery->where('name', 'like', '%' . $search . '%');

                if ($materialId !== '' && ctype_digit($materialId)) {
                    $subQuery->orWhere('id', (int) $materialId);
                }
            });
        }

        $status = $request->input('status');
        if (!$status || $status === 'all') {
            return;
        }

        if ($status === 'low_stock') {
            $query->where('current_stock', '<', 5)->where('current_stock', '>', 0);
        } elseif ($status === 'out_of_stock') {
            $query->where('current_stock', 0);
        } elseif ($status === 'expiring') {
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            });
        } elseif ($status === 'expired') {
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->where('expiration_date', '<', today());
            });
        } elseif ($status === 'disposed') {
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->where('quantity', '<', 0);
            });
        }
    }
}
