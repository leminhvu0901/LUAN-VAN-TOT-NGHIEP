<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

//"Dịch vụ quản lý kho và nguyên vật liệu".
class InventoryService
{
    // Giá vốn bình quân tối đa cho phép (đồng/đơn vị) để tránh nhập sai số tiền quá lớn
    private const MAX_UNIT_PRICE = 999999999;

    /**
     * public: Cho phép gọi từ bên ngoài.
     * createImportLot(...): Tạo phiếu nhập lô hàng nguyên vật liệu mới vào kho, đồng thời tính lại giá vốn bình quân gia quyền.
     * 
     * Các tham số truyền vào:
     * - Material $material: Đối tượng nguyên vật liệu cần nhập kho.
     * - string $quantity: Số lượng nhập (kiểu chuỗi để giữ độ chính xác thập phân).
     * - string $totalPrice: Tổng số tiền nhập của cả lô.
     * - ?string $note: Ghi chú phiếu nhập.
     * - ?string $expirationDate: Ngày hết hạn của lô hàng (nếu có).
     */
    public function createImportLot(
        Material $material,
        string $quantity,
        string $totalPrice,
        ?string $note,
        ?string $expirationDate
    ): MaterialImport {
        return DB::transaction(function () use ($material, $quantity, $totalPrice, $note, $expirationDate) {
            // Khóa dòng dữ liệu của nguyên vật liệu trong DB để tránh xung đột khi tính toán giá vốn bình quân
            $locked = Material::query()->lockForUpdate()->findOrFail($material->id);

            // 1. Tạo bản ghi lô hàng nhập mới (MaterialImport)
            $lot = MaterialImport::create([
                'material_id' => $locked->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity, // Số lượng còn lại ban đầu bằng số lượng nhập
                'total_price' => $totalPrice,
                'note' => $note,
                'expiration_date' => $expirationDate,
            ]);

            // 2. Ghi nhận lịch sử biến động kho (Nếu bảng inventory_movements tồn tại)
            if (Schema::hasTable('inventory_movements')) {
                DB::table('inventory_movements')->insert([
                    'material_id' => $locked->id,
                    'material_import_id' => $lot->id,
                    'order_id' => null,
                    'type' => 'import', // Kiểu biến động: Nhập kho
                    'quantity' => $quantity,
                    'unit_cost' => bcdiv($totalPrice, $quantity, 4), // Đơn giá 1 đơn vị = Tổng tiền / Số lượng (lấy 4 chữ số thập phân)
                    'note' => $note ?: 'Nhập kho',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3. Tính toán lại giá vốn bình quân gia quyền (Weighted Average Price):
            // Giá trị kho cũ = Tồn kho cũ * Giá vốn cũ
            $totalOldValue = bcmul((string) $locked->current_stock, (string) $locked->unit_price, 4);
            // Tồn kho mới = Tồn kho cũ + Số lượng nhập mới
            $newStock = bcadd((string) $locked->current_stock, $quantity, 2);
            // Giá vốn bình quân mới = (Giá trị kho cũ + Tổng tiền lô mới) / Tồn kho mới
            $newAvgPrice = bcdiv(bcadd($totalOldValue, $totalPrice, 4), $newStock, 4);

            // Kiểm soát nếu giá vốn bình quân mới vượt quá giới hạn tối đa
            if (bccomp($newAvgPrice, (string) self::MAX_UNIT_PRICE, 2) > 0) {
                throw ValidationException::withMessages([
                    'total_price' => 'Phiếu nhập làm giá vốn bình quân vượt quá 999.999.999 đồng/đơn vị.',
                ]);
            }

            // 4. Cập nhật số lượng tồn kho và đơn giá vốn mới vào bảng nguyên vật liệu
            $locked->update([
                'current_stock' => $newStock,
                'unit_price' => $newAvgPrice,
            ]);

            return $lot;
        });
    }

    /**
     * public: Cho phép gọi từ bên ngoài.
     * recalculateMaterialCost(int $materialId): Tính toán lại số lượng tồn kho thực tế và đơn giá vốn bình quân dựa trên các lô hàng hiện có.
     * - Tham số int $materialId: ID của nguyên vật liệu cần tính lại.
     */
    public function recalculateMaterialCost(int $materialId): void
    {
        // Lấy tất cả các lô hàng còn hạn sử dụng và còn tồn thực tế
        $lots = MaterialImport::query()->where('material_id', $materialId)
            ->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)->get();

        $stock = (float) $lots->sum('remaining_quantity');

        // Tính tổng giá trị kho còn lại = Tổng của các (Số lượng còn lại của lô * Đơn giá nhập của lô đó)
        $value = (float) $lots->sum(function ($lot) {
            return (float) $lot->remaining_quantity * ((float) $lot->total_price / (float) $lot->quantity);
        });

        // Cập nhật thông số mới nhất vào bảng nguyên vật liệu (materials)
        Material::query()->whereKey($materialId)->update([
            'current_stock' => $stock,
            'unit_price' => $stock > 0 ? $value / $stock : 0, // Giá vốn bình quân mới = Tổng giá trị / Tổng tồn kho
            'updated_at' => now(),
        ]);
    }
}
