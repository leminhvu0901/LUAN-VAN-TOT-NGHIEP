<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MaterialController
{
    private const MAX_UNIT_PRICE = 999999999;

    public function __construct(private readonly InventoryService $inventory) {}

    // 1. Lấy danh sách Vật tư & Tính toán thống kê hiển thị ở trang chủ kho
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

        $deletableMaterialsQuery = Material::query();
        $this->applyMaterialFilters($deletableMaterialsQuery, $request);
        $deletableMaterialsCount = $deletableMaterialsQuery
            ->whereDoesntHave('imports', function ($q) {
                $q->where('quantity', '>', 0)
                    ->where('remaining_quantity', '>', 0);
            })
            ->count();

        // 3. Sắp xếp
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

        // Thống kê cho 6 thẻ (chỉ tính 1 lần không phụ thuộc bộ lọc tìm kiếm)
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
            $html = view('backend.materials.partials.table', compact('materials', 'deletableMaterialsCount'))->render();
            return response()->json([
                'html' => $html,
            ]);
        }

        return view('backend.materials.index', compact(
            'materials', 'deletableMaterialsCount', 'totalItems', 'lowStockItems', 'outOfStockItems', 'expiringItems', 'expiredItems', 'disposedBatchesCount', 'totalValue', 'disposedValue'
        ));
    }

    // 2. Lưu Vật tư mới vào Database (khi bấm nút Thêm vật tư)
    public function store(Request $request)
    {
        $request->merge(['_form_context' => 'material-add']);
        $validated = $this->validateMaterialData($request);

        Material::create($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được thêm thành công!');
    }

    // 3. Cập nhật thông tin Vật tư (Tên, Đơn vị, Giá vốn)
    public function update(Request $request, Material $material)
    {
        $request->merge([
            '_form_context' => 'material-edit',
            '_form_action' => route('admin.materials.update', $material),
        ]);
        $validated = $this->validateMaterialData($request, $material);
        $hasImports = $material->imports()->exists();
        $isUsedByProducts = $material->products()->exists();

        if (($hasImports || $isUsedByProducts) && $validated['unit'] !== $material->unit) {
            throw ValidationException::withMessages([
                'unit' => 'Không thể đổi đơn vị vì vật tư đã có phiếu nhập hoặc đang được dùng trong công thức sản phẩm.',
            ]);
        }

        if ($hasImports && (float) $validated['unit_price'] !== (float) $material->unit_price) {
            throw ValidationException::withMessages([
                'unit_price' => 'Giá vốn của vật tư đã có phiếu nhập được hệ thống tính tự động, không thể sửa thủ công.',
            ]);
        }

        $material->update($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Thông tin vật tư đã được cập nhật!');
    }

    // 4. Xóa hẳn Vật tư ra khỏi hệ thống
    public function destroy(Material $material, Request $request)
    {
        $blockReason = $this->getMaterialDeleteBlockReason($material);
        if ($blockReason !== null) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $blockReason]);
            }
            return redirect()->back()->withErrors(['delete' => $blockReason]);
        }

        $material->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Vật tư đã được xóa!']);
        }
        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được xóa!');
    }

    // Xóa nhiều vật tư
    public function bulkDelete(Request $request)
    {
        if ($request->input('delete_all_pages') == '1') {
            $validated = $request->validate([
                'excluded_material_ids' => ['sometimes', 'array'],
                'excluded_material_ids.*' => ['integer'],
            ]);

            // Xóa tất cả các trang theo bộ lọc hiện tại
            $query = Material::query();

            $this->applyMaterialFilters($query, $request);

            $excludedMaterialIds = $validated['excluded_material_ids'] ?? [];
            if ($excludedMaterialIds !== []) {
                $query->whereNotIn('id', $excludedMaterialIds);
            }

            return $this->deleteMaterialCollection($query->get(), $request);
        } else {
            // Chỉ xóa các vật tư được chọn
            $request->validate([
                'material_ids' => 'required|array',
                'material_ids.*' => 'exists:materials,id'
            ]);

            $materials = Material::whereIn('id', $request->material_ids)->get();
            return $this->deleteMaterialCollection($materials, $request);
        }
    }

    // --- Imports ---

    // 5. Hiển thị màn hình Lịch sử Nhập/Xuất của một Vật tư cụ thể (imports.blade.php)
    public function imports(Material $material)
    {
        $imports = $material->imports()->latest()->get();
        $activeLotsCount = $imports
            ->where('quantity', '>', 0)
            ->where('remaining_quantity', '>', 0)
            ->count();

        return view('backend.materials.imports', compact('material', 'imports', 'activeLotsCount'));
    }

    // 6. Tạo Phiếu Nhập Kho Mới (Cộng dồn số lượng và tính lại Giá vốn Trung bình)
    public function storeImport(Request $request, Material $material)
    {
        $request->merge(['_form_context' => 'import-create']);
        $validated = $this->validateImportData($request, today()->toDateString());

        DB::transaction(function () use ($material, $validated) {
            $lockedMaterial = Material::query()->lockForUpdate()->findOrFail($material->id);
            $quantity = (float) $validated['quantity'];
            $totalPrice = (float) $validated['total_price'];

            $lot = MaterialImport::create([
                'material_id' => $lockedMaterial->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'total_price' => $totalPrice,
                'note' => $validated['note'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null,
            ]);

            if (Schema::hasTable('inventory_movements')) DB::table('inventory_movements')->insert([
                'material_id' => $lockedMaterial->id,
                'material_import_id' => $lot->id,
                'order_id' => null,
                'type' => 'import',
                'quantity' => $quantity,
                'unit_cost' => $totalPrice / $quantity,
                'note' => $validated['note'] ?? 'Nhập kho',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalOldValue = (float) $lockedMaterial->current_stock * (float) $lockedMaterial->unit_price;
            $newStock = (float) $lockedMaterial->current_stock + $quantity;
            $newAvgPrice = ($totalOldValue + $totalPrice) / $newStock;

            if ($newAvgPrice > self::MAX_UNIT_PRICE) {
                throw ValidationException::withMessages([
                    'total_price' => 'Phiếu nhập làm giá vốn bình quân vượt quá 999.999.999 đồng/đơn vị.',
                ]);
            }

            $lockedMaterial->update([
                'current_stock' => $newStock,
                'unit_price' => $newAvgPrice,
            ]);
        });

        return redirect()->route('admin.materials.imports', $material)->with('success', 'Đã nhập kho thành công!');
    }

    // 6.1. Sửa Phiếu Nhập Kho (Tính toán lại Tồn kho và Giá vốn)
    public function updateImport(Request $request, MaterialImport $import)
    {
        $request->merge([
            '_form_context' => 'import-edit',
            '_form_action' => route('admin.materials.imports.update', $import),
            '_import_id' => $import->id,
            '_min_quantity' => max((float) $import->quantity - (float) $import->remaining_quantity, 0),
            '_min_expiration_date' => $import->created_at->copy()->addDay()->toDateString(),
        ]);

        if ((float) $import->quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Chỉ phiếu nhập kho mới được phép chỉnh sửa.',
            ]);
        }

        $validated = $this->validateImportData($request, $import->created_at->toDateString());

        DB::transaction(function () use ($import, $validated) {
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);

            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ phiếu nhập kho mới được phép chỉnh sửa.',
                ]);
            }

            $oldQuantity = (float) $lockedImport->quantity;
            $oldTotalPrice = (float) $lockedImport->total_price;
            $oldRemainingQuantity = (float) $lockedImport->remaining_quantity;
            $consumed = max($oldQuantity - $oldRemainingQuantity, 0);
            $newQuantity = (float) $validated['quantity'];
            $newTotalPrice = (float) $validated['total_price'];

            if ($newQuantity < $consumed) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng mới không được nhỏ hơn số lượng đã tiêu thụ ({$consumed}).",
                ]);
            }

            $newRemainingQuantity = $newQuantity - $consumed;
            $newStock = (float) $material->current_stock - $oldRemainingQuantity + $newRemainingQuantity;
            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Số lượng mới làm tồn kho bị âm, vui lòng kiểm tra lại.',
                ]);
            }

            $currentInventoryValue = (float) $material->current_stock * (float) $material->unit_price;
            $oldRemainingValue = $oldRemainingQuantity * ($oldTotalPrice / $oldQuantity);
            $newRemainingValue = $newRemainingQuantity * ($newTotalPrice / $newQuantity);
            $newInventoryValue = max($currentInventoryValue - $oldRemainingValue + $newRemainingValue, 0);
            $newAvgPrice = $newStock > 0 ? $newInventoryValue / $newStock : 0;

            if ($newAvgPrice > self::MAX_UNIT_PRICE) {
                throw ValidationException::withMessages([
                    'total_price' => 'Thay đổi này làm giá vốn bình quân vượt quá 999.999.999 đồng/đơn vị.',
                ]);
            }

            $material->update([
                'current_stock' => $newStock,
                'unit_price' => $newAvgPrice,
            ]);

            $lockedImport->update([
                'quantity' => $newQuantity,
                'remaining_quantity' => $newRemainingQuantity,
                'total_price' => $newTotalPrice,
                'note' => $validated['note'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null,
            ]);
        });

        return redirect()->back()->with('success', 'Đã cập nhật phiếu nhập kho thành công!');
    }

    // 8. Hủy bỏ một phần/toàn bộ số lượng của MỘT LÔ HÀNG cụ thể (VD: Lô bị hỏng, hết hạn)
    public function disposeBatch(Request $request, MaterialImport $import)
    {
        $request->merge([
            '_form_context' => 'dispose-batch',
            '_form_action' => route('admin.materials.imports.dispose_batch', $import),
            '_lot_id' => $import->id,
            '_unit' => $import->material?->unit,
            '_max_quantity' => $import->remaining_quantity,
            'note' => trim((string) $request->input('note')),
        ]);

        $validated = $request->validate([
            'quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999',
            'note' => 'required|string|max:255',
        ], [
            'quantity.numeric' => 'Số lượng hủy phải là số hợp lệ.',
            'quantity.decimal' => 'Số lượng hủy chỉ được có tối đa 2 chữ số thập phân.',
            'quantity.min' => 'Số lượng hủy phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng hủy vượt quá giới hạn cho phép.',
            'note.required' => 'Vui lòng nhập lý do hủy lô hàng.'
        ]);

        DB::transaction(function () use ($import, $validated) {
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);
            $disposeQty = (float) $validated['quantity'];
            $remainingQuantity = (float) $lockedImport->remaining_quantity;

            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ có thể hủy hàng từ một phiếu nhập kho.',
                ]);
            }

            if ($disposeQty > $remainingQuantity || $disposeQty > (float) $material->current_stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng hủy không được vượt quá số lượng còn lại của lô ({$remainingQuantity}).",
                ]);
            }

            $unitPrice = abs((float) $lockedImport->total_price / (float) $lockedImport->quantity);
            $disposeValue = $disposeQty * $unitPrice;

            $lockedImport->update([
                'remaining_quantity' => $remainingQuantity - $disposeQty,
            ]);

            MaterialImport::create([
                'material_id' => $material->id,
                'quantity' => -$disposeQty,
                'remaining_quantity' => 0,
                'total_price' => -$disposeValue,
                'note' => 'Hủy từ lô LOT-' . $lockedImport->id . ': ' . $validated['note'],
                'expiration_date' => $lockedImport->expiration_date,
            ]);

            $material->update([
                'current_stock' => (float) $material->current_stock - $disposeQty,
            ]);

            if (Schema::hasTable('inventory_movements')) DB::table('inventory_movements')->insert([
                'material_id' => $material->id,
                'material_import_id' => $lockedImport->id,
                'order_id' => null,
                'type' => 'dispose',
                'quantity' => -$disposeQty,
                'unit_cost' => $unitPrice,
                'note' => $validated['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->inventory->recalculateMaterialCost($material->id);
        });

        return redirect()->back()->with('success', 'Đã xuất hủy từ lô hàng thành công!');
    }

    private function validateMaterialData(Request $request, ?Material $material = null): array
    {
        $request->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('name'))),
            'unit' => preg_replace('/\s+/u', ' ', trim((string) $request->input('unit'))),
        ]);

        $uniqueNameRule = 'unique:materials,name';
        if ($material !== null) {
            $uniqueNameRule .= ',' . $material->id;
        }

        $unitCharacterRule = function (string $attribute, mixed $value, $fail) use ($material) {
            if ($material !== null && $value === $material->unit) {
                return;
            }

            if (!preg_match('/^[\p{L}\p{M}\s\.\/-]+$/u', (string) $value)) {
                $fail('Đơn vị chỉ được chứa chữ cái, khoảng trắng, dấu chấm, gạch ngang hoặc dấu /. Không được nhập số.');
            }
        };

        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', $uniqueNameRule],
            'unit' => ['required', 'string', 'max:20', $unitCharacterRule],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:' . self::MAX_UNIT_PRICE],
        ], [
            'name.required' => 'Vui lòng nhập tên vật tư.',
            'name.min' => 'Tên vật tư phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên vật tư không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên vật tư đã tồn tại trong hệ thống.',
            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'unit.max' => 'Đơn vị tính không được vượt quá 20 ký tự.',
            'unit_price.required' => 'Vui lòng nhập giá vốn dự kiến.',
            'unit_price.numeric' => 'Giá vốn dự kiến phải là số.',
            'unit_price.min' => 'Giá vốn dự kiến không được âm.',
            'unit_price.max' => 'Giá vốn dự kiến phải nhỏ hơn 1 tỷ đồng.',
        ]);
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

    private function getMaterialDeleteBlockReason(Material $material): ?string
    {
        $activeLotsCount = $material->imports()->count();

        if ($activeLotsCount > 0) {
            return "Không thể xóa vật tư vì vẫn còn {$activeLotsCount} lô/lịch sử kho.";
        }

        if (DB::table('product_materials')->where('material_id', $material->id)->exists()) {
            return 'Không thể xóa vật tư vì đang được dùng trong định lượng sản phẩm.';
        }

        return null;
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

    private function deleteMaterialCollection($materials, Request $request = null)
    {
        $deletedCount = 0;
        $blockedCount = 0;

        foreach ($materials as $material) {
            if ($this->getMaterialDeleteBlockReason($material) !== null) {
                $blockedCount++;
                continue;
            }

            $material->delete();
            $deletedCount++;
        }

        $response = redirect()->back();

        if ($deletedCount > 0) {
            $response->with('success', "Đã xóa {$deletedCount} vật tư.");
        }

        if ($blockedCount > 0) {
            $msg = "Có {$blockedCount} vật tư không thể xóa vì vẫn còn lô hàng trong kho.";
            if ($request && $request->ajax()) {
                if ($deletedCount > 0) {
                    return response()->json(['success' => true, 'message' => "Đã xóa {$deletedCount} vật tư. " . $msg]);
                }
                return response()->json(['success' => false, 'message' => $msg]);
            }
            $response->withErrors([
                'delete' => $msg,
            ]);
        } else {
            if ($request && $request->ajax()) {
                return response()->json(['success' => true, 'message' => "Đã xóa {$deletedCount} vật tư."]);
            }
        }

        return $response;
    }

}
