<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialImport;

class MaterialController
{
    // 1. Lấy danh sách Vật tư & Tính toán thống kê hiển thị ở trang chủ kho
    public function index(Request $request)
    {
        $query = Material::with([
            'imports' => function ($q) {
                $q->whereNotNull('expiration_date');
            }
        ])->withCount('imports')->latest();

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $materials = $query->get();

        $totalItems = Material::count();
        $lowStockMaterials = Material::where('current_stock', '<', 5)->get(); // arbitrary threshold
        $lowStockItems = $lowStockMaterials->count();
        $totalValue = Material::all()->sum(function ($item) {
            return $item->current_stock * $item->unit_price;
        });

        $expiringBatches = MaterialImport::with('material')
            ->whereBetween('expiration_date', [now(), now()->addDays(30)])
            ->where('remaining_quantity', '>', 0)
            ->get();
        $expiringItems = $expiringBatches->count();

        $expiredBatches = MaterialImport::with('material')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', today())
            ->where('remaining_quantity', '>', 0)
            ->get();

        $expiredValue = $expiredBatches->sum(function ($batch) {
            $unitPrice = $batch->quantity > 0 ? ($batch->total_price / $batch->quantity) : 0;
            return $batch->remaining_quantity * $unitPrice;
        });

        $disposedBatches = MaterialImport::with('material')
            ->where('quantity', '<', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $disposedValue = abs($disposedBatches->sum('total_price'));

        return view('backend.materials.index', compact('materials', 'totalItems', 'lowStockItems', 'lowStockMaterials', 'totalValue', 'expiringItems', 'expiringBatches', 'expiredBatches', 'expiredValue', 'disposedBatches', 'disposedValue'));
    }

    // 2. Lưu Vật tư mới vào Database (khi bấm nút Thêm vật tư)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:materials,name',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
        ]);

        Material::create($request->all());

        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được thêm thành công!');
    }

    // 3. Cập nhật thông tin Vật tư (Tên, Đơn vị, Giá vốn)
    public function update(Request $request, Material $material)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:materials,name,' . $material->id,
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $material->update($request->all());

        return redirect()->route('admin.materials.index')->with('success', 'Thông tin vật tư đã được cập nhật!');
    }

    // 4. Xóa hẳn Vật tư ra khỏi hệ thống
    public function destroy(Material $material)
    {
        $material->delete();
        return redirect()->route('admin.materials.index')->with('success', 'Vật tư đã được xóa!');
    }

    // --- Imports ---

    // 5. Hiển thị màn hình Lịch sử Nhập/Xuất của một Vật tư cụ thể (imports.blade.php)
    public function imports(Material $material)
    {
        $imports = $material->imports()->latest()->get();
        return view('backend.materials.imports', compact('material', 'imports'));
    }

    // 6. Tạo Phiếu Nhập Kho Mới (Cộng dồn số lượng và tính lại Giá vốn Trung bình)
    public function storeImport(Request $request, Material $material)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date|after_or_equal:today'
        ], [
            'expiration_date.after_or_equal' => 'Hạn sử dụng không được nhỏ hơn hoặc bằng ngày nhập (hôm nay).',
            'quantity.integer' => 'Số lượng nhập phải là số nguyên (không chứa phần thập phân).',
            'quantity.min' => 'Số lượng nhập phải từ 1 trở lên.',
            'total_price.min' => 'Tổng tiền thanh toán không được âm.'
        ]);

        MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => $request->quantity,
            'remaining_quantity' => $request->quantity,
            'total_price' => $request->total_price,
            'note' => $request->note,
            'expiration_date' => $request->expiration_date
        ]);

        // Update average unit price and current stock
        $totalOldValue = $material->current_stock * $material->unit_price;
        $totalNewValue = $totalOldValue + $request->total_price;
        $newStock = $material->current_stock + $request->quantity;

        $newAvgPrice = $newStock > 0 ? ($totalNewValue / $newStock) : $material->unit_price;

        $material->update([
            'current_stock' => $newStock,
            'unit_price' => $newAvgPrice,
        ]);

        return redirect()->route('admin.materials.imports', $material)->with('success', 'Đã nhập kho thành công!');
    }

    // 6.1. Sửa Phiếu Nhập Kho (Tính toán lại Tồn kho và Giá vốn)
    public function updateImport(Request $request, MaterialImport $import)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date'
        ], [
            'quantity.integer' => 'Số lượng nhập phải là số nguyên (không chứa phần thập phân).',
            'quantity.min' => 'Số lượng nhập phải từ 1 trở lên.',
            'total_price.min' => 'Tổng tiền thanh toán không được âm.'
        ]);

        $material = Material::findOrFail($import->material_id);

        $oldQuantity = $import->quantity;
        $oldTotalPrice = $import->total_price;
        $consumed = $oldQuantity - $import->remaining_quantity;

        if ($request->quantity < $consumed) {
            return redirect()->back()->with('error', "Số lượng mới không được nhỏ hơn số lượng đã tiêu thụ ($consumed).");
        }

        // Revert old import
        $revertedStock = $material->current_stock - $oldQuantity;
        $revertedValue = ($material->current_stock * $material->unit_price) - $oldTotalPrice;

        // Apply new import
        $newStock = $revertedStock + $request->quantity;
        $newTotalValue = $revertedValue + $request->total_price;
        $newAvgPrice = $newStock > 0 ? ($newTotalValue / $newStock) : $material->unit_price;

        $material->update([
            'current_stock' => $newStock,
            'unit_price' => $newAvgPrice,
        ]);

        $import->update([
            'quantity' => $request->quantity,
            'remaining_quantity' => $request->quantity - $consumed,
            'total_price' => $request->total_price,
            'note' => $request->note,
            'expiration_date' => $request->expiration_date
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật phiếu nhập kho thành công!');
    }

    // 7. Tạo Phiếu Xuất Kho (Tính năng này đang tạm ẩn, dự kiến dành cho Giao diện Nhân viên pha chế)
    // Nguyên tắc: Xuất kho sẽ tự động trừ lùi số lượng vào các lô hàng cũ nhất (FIFO - Nhập trước Xuất trước)
    public function storeExport(Request $request, Material $material)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $material->current_stock,
            'note' => 'required|string|max:255',
        ], [
            'quantity.integer' => 'Số lượng xuất phải là số nguyên (không chứa phần thập phân).',
            'quantity.min' => 'Số lượng xuất phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng xuất không được vượt quá tồn kho hiện tại (' . $material->current_stock . ').',
            'note.required' => 'Vui lòng nhập lý do/ghi chú xuất kho.'
        ]);

        $exportQty = $request->quantity;
        $exportValue = $exportQty * $material->unit_price;

        MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => -$exportQty,
            'total_price' => -$exportValue,
            'note' => 'Xuất/Hủy: ' . $request->note,
            'expiration_date' => null
        ]);

        $material->update([
            'current_stock' => $material->current_stock - $exportQty,
            // unit_price remains unchanged when exporting
        ]);

        // Trừ FIFO trong các lô nhập kho
        $imports = MaterialImport::where('material_id', $material->id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingToDeduct = $exportQty;
        foreach ($imports as $import) {
            if ($remainingToDeduct <= 0)
                break;

            if ($import->remaining_quantity <= $remainingToDeduct) {
                $remainingToDeduct -= $import->remaining_quantity;
                $import->remaining_quantity = 0;
                $import->save();
            } else {
                $import->remaining_quantity -= $remainingToDeduct;
                $import->save();
                $remainingToDeduct = 0;
            }
        }

        return redirect()->route('admin.materials.index')->with('success', 'Đã xuất hủy kho thành công!');
    }

    // 8. Hủy bỏ một phần/toàn bộ số lượng của MỘT LÔ HÀNG cụ thể (VD: Lô bị hỏng, hết hạn)
    public function disposeBatch(Request $request, MaterialImport $import)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $import->remaining_quantity,
            'note' => 'required|string|max:255',
        ], [
            'quantity.integer' => 'Số lượng hủy phải là số nguyên (không chứa phần thập phân).',
            'quantity.min' => 'Số lượng hủy phải từ 1 trở lên.',
            'quantity.max' => 'Số lượng hủy không được vượt quá số lượng tồn của lô này (' . $import->remaining_quantity . ').',
            'note.required' => 'Vui lòng nhập lý do hủy lô hàng.'
        ]);

        $disposeQty = $request->quantity;
        $unitPrice = $import->quantity != 0 ? abs($import->total_price / $import->quantity) : 0;
        $disposeValue = $disposeQty * $unitPrice;
        $material = Material::findOrFail($import->material_id);

        // Deduct from batch
        $import->remaining_quantity -= $disposeQty;
        $import->save();

        // Log the disposal
        MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => -$disposeQty,
            'remaining_quantity' => 0,
            'total_price' => -$disposeValue,
            'note' => 'Hủy từ lô LOT-' . $import->id . ': ' . $request->note,
            'expiration_date' => $import->expiration_date
        ]);

        // Deduct from total stock
        $material->update([
            'current_stock' => $material->current_stock - $disposeQty,
        ]);

        return redirect()->back()->with('success', 'Đã xuất hủy từ lô hàng thành công!');
    }

    // 9. Trang danh sách lô hàng sắp hết hạn
    public function expiring()
    {
        $expiringBatches = MaterialImport::whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays(30))
            ->where('expiration_date', '>=', now())
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->get();

        return view('backend.materials.expiring', compact('expiringBatches'));
    }

    // 10. Trang lịch sử xuất hủy 
    public function disposed()
    {
        $disposedBatches = MaterialImport::with('material')
            ->where('quantity', '<', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $disposedValue = abs($disposedBatches->sum('total_price'));

        return view('backend.materials.disposed', compact('disposedBatches', 'disposedValue'));
    }

    // 11. Trang vật tư dưới mức tồn tối thiểu 
    public function lowStock()
    {
        $lowStockMaterials = Material::where('current_stock', '<', 5)->orderBy('current_stock', 'asc')->get();

        return view('backend.materials.low_stock', compact('lowStockMaterials'));
    }

    // 12. Trang lô hàng đã hết hạn 
    public function expired()
    {
        $expiredBatches = MaterialImport::with('material')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', today())
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $expiredValue = $expiredBatches->sum(function ($batch) {
            $unitPrice = $batch->quantity > 0 ? ($batch->total_price / $batch->quantity) : 0;
            return $batch->remaining_quantity * $unitPrice;
        });

        return view('backend.materials.expired', compact('expiredBatches', 'expiredValue'));
    }

    // 13. Trang Giá trị kho
    public function inventoryValue()
    {
        $materials = Material::where('current_stock', '>', 0)
            ->orderBy('name', 'asc')
            ->get();

        $totalValue = $materials->sum(function ($item) {
            return $item->current_stock * $item->unit_price;
        });

        return view('backend.materials.inventory_value', compact('materials', 'totalValue'));
    }
}
