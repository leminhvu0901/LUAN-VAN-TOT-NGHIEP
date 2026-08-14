<?php

namespace App\Http\Controllers\Backend\Admin;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MaterialController
{
    private const MAX_UNIT_PRICE = 999999999;

    public function __construct(private readonly InventoryService $inventory)
    {
    }

    // Lấy danh sách vật tư và tính thống kê hiển thị
    public function index(Request $request)
    {
        $query = Material::with([
            // Chỉ nạp các lô còn hàng
            'imports' => function ($q) {
                $q->where('remaining_quantity', '>', 0);
            }
        ])->withCount([
                    // Số lô đang còn tồn thật, loại trừ bản ghi âm là lô đã hủy hoặc xuất
                    'imports as active_lots_count' => function ($q) {
                        $q->where('quantity', '>', 0)
                            ->where('remaining_quantity', '>', 0);
                    },
                    // Số lần vật tư này đã bị hủy bỏ, tính từ bản ghi quantity âm
                    'imports as disposed_count' => function ($q) {
                        $q->where('quantity', '<', 0);
                    }
                ]);

        // Áp bộ lọc tìm kiếm và trạng thái
        $this->applyMaterialFilters($query, $request);
        $deletableMaterialsQuery = Material::query();
        // Áp cùng bộ lọc để số đếm khớp với danh sách đang hiển thị trên màn hình
        $this->applyMaterialFilters($deletableMaterialsQuery, $request);
        $deletableMaterialsCount = $deletableMaterialsQuery
            ->whereDoesntHave('imports', function ($q) {
                $q->where('quantity', '>', 0)
                    ->where('remaining_quantity', '>', 0);
            })
            ->count();

        // Sắp xếp danh sách
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

        // Thống kê cho các thẻ tổng quan, không phụ thuộc bộ lọc
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

        return view('backend.admin.materials.index', compact(
            'materials',
            'deletableMaterialsCount',
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

    // Lưu vật tư mới vào database
    public function store(Request $request)
    {
        $request->merge(['_form_context' => 'material-add']);
        // Validate tên, đơn vị, giá vốn
        $validated = $this->validateMaterialData($request);

        Material::create($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được thêm thành công!');
    }

    // Cập nhật thông tin vật tư
    public function update(Request $request, Material $material)
    {
        $request->merge([
            '_form_context' => 'material-edit',
            '_form_action' => route('admin.materials.update', $material),
        ]);
        // Truyền kèm $material để loại trừ chính nó khi check trùng tên, và cho phép giữ nguyên đơn vị cũ
        $validated = $this->validateMaterialData($request, $material);
        $hasImports = $material->imports()->exists();
        $isUsedByProducts = $material->products()->exists();

        // Đổi đơn vị sẽ làm sai lệch số liệu tồn kho/công thức
        if (($hasImports || $isUsedByProducts) && $validated['unit'] !== $material->unit) {
            throw ValidationException::withMessages([
                'unit' => 'Không thể đổi đơn vị vì vật tư đã có phiếu nhập hoặc đang được dùng trong công thức sản phẩm.',
            ]);
        }

        // Giá vốn là số hệ thống tự tính từ các phiếu nhập theo bình quân gia quyền
        if ($hasImports && (float) $validated['unit_price'] !== (float) $material->unit_price) {
            throw ValidationException::withMessages([
                'unit_price' => 'Giá vốn của vật tư đã có phiếu nhập được hệ thống tính tự động, không thể sửa thủ công.',
            ]);
        }

        $material->update($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Thông tin vật tư đã được cập nhật!');
    }

    // Xóa hẳn vật tư khỏi hệ thống
    public function destroy(Material $material, Request $request = null)
    {
        // Kiểm tra vật tư có còn lô hàng hoặc đang dùng trong công thức sản phẩm không
        $blockReason = $this->getMaterialDeleteBlockReason($material);
        if ($blockReason !== null) {
            return redirect()->back()->withErrors(['delete' => $blockReason]);
        }

        $material->delete();
        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được xóa!');
    }

    // Xóa nhiều vật tư, chỉ áp dụng cho các dòng đang chọn
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'material_ids' => 'required|array',
            'material_ids.*' => 'exists:materials,id'
        ]);

        $materials = Material::whereIn('id', $request->material_ids)->get();
        // Xóa từng vật tư trong danh sách, bỏ qua dòng không xóa được
        return $this->deleteMaterialCollection($materials, $request);
    }

    // Imports

    // Hiển thị màn hình lịch sử nhập xuất của một vật tư
    public function imports(Material $material)
    {
        // Lấy tất cả bản ghi, kể cả bản ghi âm hủy hoặc xuất
        $imports = $material->imports()->latest()->get();
        // Số lô còn tồn thật, dùng cho ô thống kê ở đầu trang
        $activeLotsCount = $imports
            ->where('quantity', '>', 0)
            ->where('remaining_quantity', '>', 0)
            ->count();

        return view('backend.admin.materials.imports', compact('material', 'imports', 'activeLotsCount'));
    }

    // Tạo phiếu nhập kho mới
    public function storeImport(Request $request, Material $material)
    {
        $request->merge(['_form_context' => 'import-create']);
        // Phiếu nhập mới nên hạn sử dụng phải sau ngày hôm nay
        $validated = $this->validateImportData($request, today()->toDateString());

        $this->inventory->createImportLot(
            $material,
            (string) $validated['quantity'],
            (string) $validated['total_price'],
            $validated['note'] ?? null,
            $validated['expiration_date'] ?? null,
        );

        return redirect()->route('admin.materials.imports', $material)->with('success', 'Đã nhập kho thành công!');
    }

    // Sửa phiếu nhập kho, tính lại tồn kho và giá vốn
    public function updateImport(Request $request, MaterialImport $import)
    {
        // Các giá trị _min_* để Blade validate phía client trước khi gửi lên server
        $request->merge([
            '_form_context' => 'import-edit',
            '_form_action' => route('admin.materials.imports.update', $import),
            '_import_id' => $import->id,
            // Không cho sửa số lượng xuống thấp hơn phần đã tiêu thụ
            '_min_quantity' => max((float) $import->quantity - (float) $import->remaining_quantity, 0),
            '_min_expiration_date' => $import->created_at->copy()->addDay()->toDateString(),
        ]);

        // Bản ghi quantity nhỏ hơn hoặc bằng 0 là nhật ký hủy hoặc xuất, không phải phiếu nhập thật nên không cho sửa
        if ((float) $import->quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Chỉ phiếu nhập kho mới được phép chỉnh sửa.',
            ]);
        }

        // Phiếu sửa thì hạn sử dụng phải sau ngày tạo phiếu gốc
        $validated = $this->validateImportData($request, $import->created_at->toDateString());

        DB::transaction(function () use ($import, $validated) {
            // Khóa dòng để tránh 2 request sửa cùng lúc
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);

            // Kiểm tra lại sau khi khóa dòng vì dữ liệu có thể đã đổi
            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ phiếu nhập kho mới được phép chỉnh sửa.',
                ]);
            }

            $oldQuantity = (float) $lockedImport->quantity;
            $oldTotalPrice = (float) $lockedImport->total_price;
            $oldRemainingQuantity = (float) $lockedImport->remaining_quantity;
            // Phần đã tiêu thụ của lô này, số lượng sửa xong phải lớn hơn hoặc bằng phần đã dùng
            $consumed = max($oldQuantity - $oldRemainingQuantity, 0);
            $newQuantity = (float) $validated['quantity'];
            $newTotalPrice = (float) $validated['total_price'];

            if ($newQuantity < $consumed) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng mới không được nhỏ hơn số lượng đã tiêu thụ ({$consumed}).",
                ]);
            }

            // Số lượng còn lại của lô bằng số lượng mới trừ phần đã tiêu thụ
            $newRemainingQuantity = $newQuantity - $consumed;
            $newStock = (float) $material->current_stock - $oldRemainingQuantity + $newRemainingQuantity;
            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Số lượng mới làm tồn kho bị âm, vui lòng kiểm tra lại.',
                ]);
            }

            // Giá vốn bình quân gia quyền, bỏ giá trị lô cũ ra và cộng giá trị lô mới vào
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

    // Hủy bỏ một phần hoặc toàn bộ số lượng của một lô hàng cụ thể
    public function disposeBatch(Request $request, MaterialImport $import)
    {
        // _max_quantity để Blade giới hạn ô nhập số lượng hủy
        $request->merge([
            '_form_context' => 'dispose-batch',
            '_form_action' => route('admin.materials.imports.dispose_batch', $import),
            '_lot_id' => $import->id,
            '_unit' => $import->material?->unit,
            '_max_quantity' => $import->remaining_quantity,
            'note' => trim((string) $request->input('note')),
        ]);

        // Bắt buộc phải có lý do hủy, dùng cho việc tra soát sau này
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
            // Khóa dòng để tránh 2 request hủy hoặc xuất cùng lúc trên cùng một lô
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);
            $disposeQty = (float) $validated['quantity'];
            $remainingQuantity = (float) $lockedImport->remaining_quantity;

            // Chỉ hủy được từ bản ghi phiếu nhập thật
            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ có thể hủy hàng từ một phiếu nhập kho.',
                ]);
            }

            // Không hủy nhiều hơn số lượng còn lại của lô hoặc tồn kho hiện có
            if ($disposeQty > $remainingQuantity || $disposeQty > (float) $material->current_stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng hủy không được vượt quá số lượng còn lại của lô ({$remainingQuantity}).",
                ]);
            }

            // Đơn giá gốc của đúng lô này, không phải giá bình quân của material
            $unitPrice = abs((float) $lockedImport->total_price / (float) $lockedImport->quantity);
            $disposeValue = $disposeQty * $unitPrice;

            // Trừ số lượng còn lại của lô gốc
            $lockedImport->update([
                'remaining_quantity' => $remainingQuantity - $disposeQty,
            ]);

            // Ghi thêm 1 bản ghi mới với quantity và total_price âm làm nhật ký hủy
            MaterialImport::create([
                'material_id' => $material->id,
                'quantity' => -$disposeQty,
                'remaining_quantity' => 0,
                'total_price' => -$disposeValue,
                'note' => 'Hủy từ lô LOT-' . $lockedImport->id . ': ' . $validated['note'],
                'expiration_date' => $lockedImport->expiration_date,
            ]);

            // Trừ thẳng tồn kho tổng của Material vì hủy không phải là xuất bán
            $material->update([
                'current_stock' => (float) $material->current_stock - $disposeQty,
            ]);

            // Bảng inventory_movements là log audit riêng, không bắt buộc phải tồn tại
            if (Schema::hasTable('inventory_movements'))
                DB::table('inventory_movements')->insert([
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
            // Tính lại giá vốn bình quân của Material sau khi lô hàng thay đổi
            $this->inventory->recalculateMaterialCost($material->id);
        });

        return redirect()->back()->with('success', 'Đã xuất hủy từ lô hàng thành công!');
    }

    // Xuất kho sử dụng, luôn từ một lô cụ thể do người dùng chọn
    public function consumeBatch(Request $request, MaterialImport $import)
    {
        // _max_quantity để Blade giới hạn ô nhập số lượng xuất
        $request->merge([
            '_form_context' => 'consume-batch',
            '_form_action' => route('admin.materials.imports.consume_batch', $import),
            '_lot_id' => $import->id,
            '_unit' => $import->material?->unit,
            '_max_quantity' => $import->remaining_quantity,
            'reason' => trim((string) $request->input('reason')),
        ]);

        // Bắt buộc phải có lý do xuất, khác dispose là dùng cho hàng hủy hỏng hoặc hết hạn
        $validated = $request->validate([
            'quantity' => 'required|numeric|decimal:0,2|min:0.01|max:99999999',
            'reason' => 'required|string|max:255',
        ], [
            'quantity.numeric' => 'Số lượng xuất phải là số hợp lệ.',
            'quantity.decimal' => 'Số lượng xuất chỉ được có tối đa 2 chữ số thập phân.',
            'quantity.min' => 'Số lượng xuất phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng xuất vượt quá giới hạn cho phép.',
            'reason.required' => 'Vui lòng nhập lý do xuất kho.'
        ]);

        $operator = Auth::user();

        DB::transaction(function () use ($import, $validated, $operator) {
            // Khóa dòng để tránh 2 request xuất hoặc hủy cùng lúc trên cùng một lô
            $material = Material::query()->lockForUpdate()->findOrFail($import->material_id);
            $lockedImport = MaterialImport::query()->lockForUpdate()->findOrFail($import->id);
            $consumeQty = (float) $validated['quantity'];
            $remainingQuantity = (float) $lockedImport->remaining_quantity;

            // Chỉ xuất được từ bản ghi phiếu nhập thật
            if ((float) $lockedImport->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ có thể xuất kho từ một phiếu nhập kho.',
                ]);
            }

            // Không xuất nhiều hơn số lượng còn lại của lô hoặc tồn kho hiện có
            if ($consumeQty > $remainingQuantity || $consumeQty > (float) $material->current_stock) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng xuất không được vượt quá số lượng còn lại của lô ({$remainingQuantity}).",
                ]);
            }

            // Đơn giá gốc của đúng lô này, không phải giá bình quân của material
            $unitPrice = abs((float) $lockedImport->total_price / (float) $lockedImport->quantity);
            $consumeValue = $consumeQty * $unitPrice;
            // Gắn danh tính admin thao tác vào ghi chú xuất kho
            $reason = sprintf('[Admin: %s (%s)] %s', $operator->name, $operator->email, $validated['reason']);

            // Trừ số lượng còn lại của lô gốc
            $lockedImport->update([
                'remaining_quantity' => $remainingQuantity - $consumeQty,
            ]);

            // Ghi thêm 1 bản ghi mới với quantity và total_price âm làm nhật ký xuất
            MaterialImport::create([
                'material_id' => $material->id,
                'quantity' => -$consumeQty,
                'remaining_quantity' => 0,
                'total_price' => -$consumeValue,
                'note' => 'Xuất dùng từ lô LOT-' . $lockedImport->id . ': ' . $reason,
                'expiration_date' => $lockedImport->expiration_date,
            ]);

            // Trừ thẳng tồn kho tổng của Material vì đây là xuất thủ công
            $material->update([
                'current_stock' => (float) $material->current_stock - $consumeQty,
            ]);

            // Bảng inventory_movements là log audit riêng, không bắt buộc phải tồn tại
            // type adjustment khác dispose để phân biệt xuất thủ công với hủy hàng hỏng hoặc hết hạn
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
            // Tính lại giá vốn bình quân của Material sau khi lô hàng thay đổi
            $this->inventory->recalculateMaterialCost($material->id);
        });

        return redirect()->back()->with('success', 'Đã ghi nhận xuất kho từ lô hàng thành công!');
    }

    // Validate chung cho cả thêm mới và sửa
    private function validateMaterialData(Request $request, ?Material $material = null): array
    {
        // Gộp nhiều khoảng trắng liên tiếp thành 1
        $request->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $request->input('name'))),
            'unit' => preg_replace('/\s+/u', ' ', trim((string) $request->input('unit'))),
        ]);

        // Khi sửa thì phải loại trừ chính bản ghi đang sửa khỏi kiểm tra trùng tên
        $uniqueNameRule = 'unique:materials,name';
        if ($material !== null) {
            $uniqueNameRule .= ',' . $material->id;
        }

        // Chặn nhập số vào đơn vị tính, ví dụ kg2, trừ khi giữ nguyên đơn vị cũ
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

    // Validate dữ liệu 1 phiếu nhập kho
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

    // kiểm tra xem 1 vật tư có xóa được không
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

    // áp bộ lọc tìm kiếm và trạng thái lên query danh sách vật tư
    private function applyMaterialFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            // Cho phép gõ cả mã hiển thị VT-0007 lẫn số ID thô
            $materialId = preg_replace('/^VT[-\s]*0*/iu', '', $search);

            // Khớp theo tên chứa từ khóa hoặc đúng ID nếu từ khóa là số
            $query->where(function ($subQuery) use ($search, $materialId) {
                $subQuery->where('name', 'like', '%' . $search . '%');

                if ($materialId !== '' && ctype_digit($materialId)) {
                    $subQuery->orWhere('id', (int) $materialId);
                }
            });
        }

        // Không chọn trạng thái hoặc chọn Tất cả thì không lọc gì thêm
        $status = $request->input('status');
        if (!$status || $status === 'all') {
            return;
        }

        if ($status === 'low_stock') {
            // Còn hàng nhưng dưới ngưỡng 5, sắp hết nhưng chưa hết hẳn
            $query->where('current_stock', '<', 5)->where('current_stock', '>', 0);
        } elseif ($status === 'out_of_stock') {
            // Tồn kho về đúng 0
            $query->where('current_stock', 0);
        } elseif ($status === 'expiring') {
            // Có ít nhất 1 lô còn hàng, hạn sử dụng rơi trong 30 ngày tới
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            });
        } elseif ($status === 'expired') {
            // Có ít nhất 1 lô còn hàng nhưng hạn sử dụng đã trôi qua
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->where('expiration_date', '<', today());
            });
        } elseif ($status === 'disposed') {
            // quantity nhỏ hơn 0 là bản ghi nhật ký hủy hoặc xuất
            $query->whereHas('imports', function ($subQuery) {
                $subQuery->where('quantity', '<', 0);
            });
        }
    }

    // xóa nhiều vật tư cùng lúc
    private function deleteMaterialCollection($materials, Request $request = null)
    {
        $deletedCount = 0;
        $blockedCount = 0;

        foreach ($materials as $material) {
            // Dùng cùng hàm kiểm tra như destroy, chặn nếu vật tư còn lô hàng
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
            $response->withErrors([
                'delete' => $msg,
            ]);
        }

        return $response;
    }

}
