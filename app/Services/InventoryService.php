<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    // Giá vốn bình quân tối đa cho phép (đồng/đơn vị) — giống MaterialController::MAX_UNIT_PRICE,
    // định nghĩa riêng ở đây vì service không nên phụ thuộc ngược vào controller.
    private const MAX_UNIT_PRICE = 999999999;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Tạo phiếu nhập kho: cộng dồn tồn kho + tính lại giá vốn bình quân gia quyền.
     * Dùng bcmath (không dùng float) vì các cột liên quan là decimal trong DB — tránh sai số làm tròn.
     * $quantity/$totalPrice phải là chuỗi số thập phân hợp lệ (vd. từ $request->validate()).
     */
    public function createImportLot(
        Material $material,
        string $quantity,
        string $totalPrice,
        ?string $note,
        ?string $expirationDate
    ): MaterialImport {
        return DB::transaction(function () use ($material, $quantity, $totalPrice, $note, $expirationDate) {
            $locked = Material::query()->lockForUpdate()->findOrFail($material->id);

            $lot = MaterialImport::create([
                'material_id' => $locked->id,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'total_price' => $totalPrice,
                'note' => $note,
                'expiration_date' => $expirationDate,
            ]);

            if (Schema::hasTable('inventory_movements')) {
                DB::table('inventory_movements')->insert([
                    'material_id' => $locked->id,
                    'material_import_id' => $lot->id,
                    'order_id' => null,
                    'type' => 'import',
                    'quantity' => $quantity,
                    'unit_cost' => bcdiv($totalPrice, $quantity, 4),
                    'note' => $note ?: 'Nhập kho',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $totalOldValue = bcmul((string) $locked->current_stock, (string) $locked->unit_price, 4);
            $newStock = bcadd((string) $locked->current_stock, $quantity, 2);
            $newAvgPrice = bcdiv(bcadd($totalOldValue, $totalPrice, 4), $newStock, 4);

            if (bccomp($newAvgPrice, (string) self::MAX_UNIT_PRICE, 2) > 0) {
                throw ValidationException::withMessages([
                    'total_price' => 'Phiếu nhập làm giá vốn bình quân vượt quá 999.999.999 đồng/đơn vị.',
                ]);
            }

            $locked->update([
                'current_stock' => $newStock,
                'unit_price' => $newAvgPrice,
            ]);

            return $lot;
        });
    }

    /**
     * Nhân viên/quản lý lấy vật tư ra khỏi kho để dùng trực tiếp tại quầy (không qua đơn hàng/công thức).
     * Trừ theo FIFO — ưu tiên lô sắp hết hạn trước — giống cách reserveForOrder tiêu thụ lô, nhưng không
     * gắn với order nào. Tạo 1 bản ghi MaterialImport âm để hiện trong "Lịch sử Thu hồi kho" (giống disposeBatch).
     */
    public function consumeStockManually(Material $material, string $quantity, string $reason): void
    {
        DB::transaction(function () use ($material, $quantity, $reason) {
            $locked = Material::query()->lockForUpdate()->findOrFail($material->id);

            $lots = MaterialImport::query()->where('material_id', $locked->id)
                ->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)
                ->orderByRaw('expiration_date IS NULL, expiration_date ASC')->orderBy('created_at')->orderBy('id')
                ->lockForUpdate()->get();

            $available = (string) $lots->sum('remaining_quantity');
            if (bccomp($available, $quantity, 2) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Không đủ tồn kho để xuất (khả dụng: {$available} {$locked->unit}).",
                ]);
            }

            $remaining = $quantity;
            $totalValue = '0';
            foreach ($lots as $lot) {
                if (bccomp($remaining, '0', 2) <= 0) break;

                $lotRemaining = (string) $lot->remaining_quantity;
                $taken = bccomp($lotRemaining, $remaining, 2) < 0 ? $lotRemaining : $remaining;
                $unitCost = bccomp((string) $lot->quantity, '0', 2) > 0
                    ? bcdiv((string) $lot->total_price, (string) $lot->quantity, 4)
                    : '0';
                $totalValue = bcadd($totalValue, bcmul($taken, $unitCost, 4), 4);

                $lot->decrement('remaining_quantity', (float) $taken);

                if (Schema::hasTable('inventory_movements')) {
                    DB::table('inventory_movements')->insert([
                        'material_id' => $locked->id,
                        'material_import_id' => $lot->id,
                        'order_id' => null,
                        'type' => 'adjustment',
                        'quantity' => -(float) $taken,
                        'unit_cost' => $unitCost,
                        'note' => $reason,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $remaining = bcsub($remaining, $taken, 2);
            }

            MaterialImport::create([
                'material_id' => $locked->id,
                'quantity' => -(float) $quantity,
                'remaining_quantity' => 0,
                'total_price' => -(float) $totalValue,
                'note' => $reason,
                'expiration_date' => null,
            ]);

            $locked->decrement('current_stock', (float) $quantity);
            $this->recalculateMaterialCost($locked->id);
        });
    }

    public function reserveForOrder(Order $order, Collection $items): void
    {
        if ($order->inventory_reserved_at) return;

        $required = collect();
        foreach ($items as $item) {
            $recipes = DB::table('product_materials')->where('product_id', $item->product_id)->get();
            foreach ($recipes as $recipe) {
                $required[$recipe->material_id] = ($required[$recipe->material_id] ?? 0)
                    + ((float) $recipe->quantity_used * (int) $item->quantity);
            }
        }

        foreach ($required as $materialId => $quantity) {
            $material = Material::query()->lockForUpdate()->find($materialId);
            if (!$material || !$material->is_active) {
                throw ValidationException::withMessages(['inventory' => 'Một nguyên liệu của đơn hàng đã ngừng sử dụng.']);
            }

            $lots = MaterialImport::query()->where('material_id', $materialId)
                ->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today());
                })
                ->orderByRaw('expiration_date IS NULL, expiration_date ASC')->orderBy('created_at')->orderBy('id')
                ->lockForUpdate()->get();

            if ((float) $lots->sum('remaining_quantity') + 0.0001 < $quantity) {
                throw ValidationException::withMessages([
                    'inventory' => "Không đủ {$material->name} còn hạn sử dụng để xử lý đơn hàng.",
                ]);
            }

            $remaining = $quantity;
            foreach ($lots as $lot) {
                if ($remaining <= 0.0001) break;
                $taken = min((float) $lot->remaining_quantity, $remaining);
                $unitCost = (float) $lot->quantity > 0 ? (float) $lot->total_price / (float) $lot->quantity : 0;
                $lot->decrement('remaining_quantity', $taken);
                $consumptionId = DB::table('order_material_consumptions')->insertGetId([
                    'order_id' => $order->id,
                    'material_id' => $materialId,
                    'material_import_id' => $lot->id,
                    'quantity' => $taken,
                    'unit_cost' => $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('inventory_movements')->insert([
                    'material_id' => $materialId,
                    'material_import_id' => $lot->id,
                    'order_id' => $order->id,
                    'type' => 'order_reserve',
                    'quantity' => -$taken,
                    'unit_cost' => $unitCost,
                    'note' => 'Reserve #' . $order->order_code . ' / ' . $consumptionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $remaining -= $taken;
            }

            $stockBefore = (float) $material->current_stock;
            $material->decrement('current_stock', $quantity);
            $this->recalculateMaterialCost($materialId);

            $stockAfter = (float) Material::query()->whereKey($materialId)->value('current_stock');
            $this->notifications->lowStockAlertIfCrossed($material, $stockBefore, $stockAfter);
        }

        $order->forceFill(['inventory_reserved_at' => now(), 'inventory_released_at' => null])->save();
    }

    public function releaseForOrder(Order $order): void
    {
        if (!$order->inventory_reserved_at || $order->inventory_released_at) return;

        $consumptions = DB::table('order_material_consumptions')->where('order_id', $order->id)
            ->whereNull('restored_at')->lockForUpdate()->get();

        foreach ($consumptions->groupBy('material_id') as $materialId => $rows) {
            $material = Material::query()->lockForUpdate()->find($materialId);
            if (!$material) continue;
            foreach ($rows as $row) {
                if ($row->material_import_id && MaterialImport::query()->whereKey($row->material_import_id)->exists()) {
                    MaterialImport::query()->whereKey($row->material_import_id)->increment('remaining_quantity', $row->quantity);
                }
                DB::table('order_material_consumptions')->where('id', $row->id)->update(['restored_at' => now(), 'updated_at' => now()]);
                DB::table('inventory_movements')->insert([
                    'material_id' => $materialId,
                    'material_import_id' => $row->material_import_id,
                    'order_id' => $order->id,
                    'type' => 'order_release',
                    'quantity' => $row->quantity,
                    'unit_cost' => $row->unit_cost,
                    'note' => 'Release #' . $order->order_code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $material->increment('current_stock', (float) $rows->sum('quantity'));
            $this->recalculateMaterialCost($materialId);
        }

        $order->forceFill(['inventory_released_at' => now()])->save();
    }

    public function recalculateMaterialCost(int $materialId): void
    {
        $lots = MaterialImport::query()->where('material_id', $materialId)
            ->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)->get();
        $stock = (float) $lots->sum('remaining_quantity');
        $value = (float) $lots->sum(function ($lot) {
            return (float) $lot->remaining_quantity * ((float) $lot->total_price / (float) $lot->quantity);
        });
        Material::query()->whereKey($materialId)->update([
            'current_stock' => $stock,
            'unit_price' => $stock > 0 ? $value / $stock : 0,
            'updated_at' => now(),
        ]);
    }
}
