<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
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

            $material->decrement('current_stock', $quantity);
            $this->recalculateMaterialCost($materialId);
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
