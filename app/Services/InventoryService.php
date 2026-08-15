<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    private const MAX_UNIT_PRICE = 999999999;

    // Tạo phiếu nhập kho và tính toán giá vốn bình quân
    public function createImportLot(Material $material, string $quantity, string $totalPrice, ?string $note, ?string $expirationDate): MaterialImport
    {
        return DB::transaction(function () use ($material, $quantity, $totalPrice, $note, $expirationDate) {
            // Khóa dòng nguyên vật liệu để tránh xung đột dữ liệu
            $locked = Material::query()->lockForUpdate()->findOrFail($material->id); // Khóa bản ghi để độc quyền cập nhật

            // Tạo lô hàng nhập kho mới
            $lot = MaterialImport::create([ // Lưu bản ghi phiếu nhập kho mới
                'material_id' => $locked->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'total_price' => $totalPrice,
                'note' => $note,
                'expiration_date' => $expirationDate,
            ]);

            // Ghi nhận biến động kho nếu bảng tồn tại
            if (Schema::hasTable('inventory_movements')) {
                if ($note) {
                    $noteText = $note;
                } else {
                    $noteText = 'Nhập kho';
                }

                DB::table('inventory_movements')->insert([ // Ghi nhận lịch sử biến động kho vật liệu
                    'material_id' => $locked->id,
                    'material_import_id' => $lot->id,
                    'order_id' => null,
                    'type' => 'import',
                    'quantity' => $quantity,
                    'unit_cost' => bcdiv($totalPrice, $quantity, 4),
                    'note' => $noteText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Giá trị kho cũ = Tồn kho cũ * Giá vốn cũ
            $totalOldValue = bcmul((string) $locked->current_stock, (string) $locked->unit_price, 4);
            // Tồn kho mới = Tồn kho cũ + Số lượng nhập mới
            $newStock = bcadd((string) $locked->current_stock, $quantity, 2);
            // Giá vốn bình quân mới =, Giá trị kho cũ + Tổng tiền lô mới / Tồn kho mới
            $newAvgPrice = bcdiv(bcadd($totalOldValue, $totalPrice, 4), $newStock, 4);

            // Kiểm tra giới hạn giá vốn tối đa
            if (bccomp($newAvgPrice, (string) self::MAX_UNIT_PRICE, 2) > 0) {
                throw ValidationException::withMessages([
                    'total_price' => 'Phiếu nhập làm giá vốn bình quân vượt quá 999.999.999 đồng/đơn vị.',
                ]);
            }

            // Cập nhật tồn kho và giá vốn vào bảng nguyên vật liệu
            $locked->update([
                'current_stock' => $newStock,
                'unit_price' => $newAvgPrice,
            ]);

            return $lot;
        });
    }

    // Tính toán lại tồn kho thực tế và giá vốn bình quân
    public function recalculateMaterialCost(int $materialId): void
    {
        $lots = MaterialImport::query()->where('material_id', $materialId)
            ->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)->get();

        $stock = (float) $lots->sum('remaining_quantity');
        // Tính tổng giá trị kho còn lại của các lô
        $value = (float) $lots->sum(function ($lot) {
            return (float) $lot->remaining_quantity * ((float) $lot->total_price / (float) $lot->quantity);
        });
        // Xác định giá vốn bình quân mới dựa vào lượng tồn kho
        if ($stock > 0) {
            $unitPrice = $value / $stock;
        } else {
            $unitPrice = 0;
        }
        // Cập nhật thông số mới nhất vào bảng nguyên vật liệu
        Material::query()->whereKey($materialId)->update([ // Cập nhật lại tồn kho và giá vốn bình quân mới
            'current_stock' => $stock,
            'unit_price' => $unitPrice,
            'updated_at' => now(),
        ]);
    }
}
