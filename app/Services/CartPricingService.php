<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductSize;
use App\Models\Topping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CartPricingService
{

    // TÍNH TOÁN GIÁ CHUẨN NHẤT TỪNG SẢN PHẨM 
    public function pricedItems(Cart $cart, bool $lock = false, ?array $selectedIds = null): Collection
    {
        $query = CartItem::query()->with(['product', 'toppings.topping'])->where('cart_id', $cart->id); // Lấy danh sách sản phẩm trong giỏ kèm chi tiết Topping
        if ($selectedIds !== null && count($selectedIds) > 0) {
            $query->whereIn('id', $selectedIds);
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        $items = $query->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }
        foreach ($items as $item) {
            if (!$item->product || !$item->product->is_active) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng có sản phẩm đã ngừng kinh doanh.']);
            }
            if (!is_numeric($item->quantity) || (int) $item->quantity < 1 || (int) $item->quantity > 99) {
                throw ValidationException::withMessages(['cart' => 'Số lượng sản phẩm trong giỏ không hợp lệ.']);
            }
            $price = (float) $item->product->base_price;
            $size = null;
            if ($item->size_name) {
                $size = ProductSize::query()->where('product_id', $item->product_id)
                    ->where('size_name', $item->size_name)->first();
                if (!$size) {
                    throw ValidationException::withMessages(['cart' => "Kích thước của {$item->product->name} không còn hợp lệ."]);
                }
                $price += (float) $size->price_adjustment;
            }
            $toppingIds = $item->toppings->pluck('topping_id')->unique()->values();
            $allowedToppings = collect();
            if ($toppingIds->isNotEmpty()) {
                $allowedToppings = Topping::query()
                    ->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $item->product_id)
                    ->where('toppings.is_available', true)
                    ->whereIn('toppings.id', $toppingIds)
                    ->select('toppings.*')->get();
                if ($allowedToppings->count() !== $toppingIds->count()) {
                    throw ValidationException::withMessages(['cart' => "Topping của {$item->product->name} không còn hợp lệ."]);
                }
                $price += (float) $allowedToppings->sum('price');
            }
            $item->setAttribute('calculated_unit_price', $price);
            $item->setAttribute('calculated_toppings', $allowedToppings);
            if ((float) $item->unit_price !== $price) {
                DB::table('cart_items')->where('id', $item->id)->update(['unit_price' => $price, 'updated_at' => now()]);
                $item->unit_price = $price;
            }
        }

        return $items;
    }

    // hàm tính tổng số tiền của giỏ hàng , chưa áp mã 
    public function subtotal(Collection $items): float
    {
        return (float) $items->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }
}

