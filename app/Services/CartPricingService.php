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

    // TÍNH TOÁN GIÁ CHUẨN NHẤT TỪNG SẢN PHẨM , trả về giá
    public function pricedItems(Cart $cart, bool $lock = false, ?array $selectedIds = null): Collection
    {
        // Khởi tạo câu truy vấn lấy các sản phẩm trong giỏ hàng
        $query = CartItem::query()->with(['product', 'toppings.topping'])->where('cart_id', $cart->id); // Lấy danh sách sản phẩm trong giỏ kèm chi tiết Topping

        // Nếu người dùng chỉ tick chọn một vài sản phẩm để thanh
        if ($selectedIds !== null && count($selectedIds) > 0) {
            $query->whereIn('id', $selectedIds);
        }

        // Nếu tham số $lock = true, áp dụng cơ chế khóa dòng dữ liệu để tránh cập nhật đồng thời từ các luồng khác
        if ($lock) {
            $query->lockForUpdate();
        }

        // Thực thi câu lệnh SQL để lấy danh sách sản phẩm trong
        $items = $query->get();

        // Kiểm tra nếu danh sách trống, báo lỗi
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }

        // Vòng lặp duyệt qua từng sản phẩm trong giỏ hàng để bắt
        foreach ($items as $item) {
            if (!$item->product || !$item->product->is_active) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng có sản phẩm đã ngừng kinh doanh.']);
            }

            // Kiểm tra tính hợp lệ của số lượng mua phải từ 1
            if (!is_numeric($item->quantity) || (int) $item->quantity < 1 || (int) $item->quantity > 99) {
                throw ValidationException::withMessages(['cart' => 'Số lượng sản phẩm trong giỏ không hợp lệ.']);
            }

            // Lấy giá cơ bản của sản phẩm, base_price làm mốc giá ban đầu
            $price = (float) $item->product->base_price;
            $size = null;

            // Nếu khách chọn Size, Ví dụ: Size M, Size L, tìm
            if ($item->size_name) {
                $size = ProductSize::query()->where('product_id', $item->product_id)
                    ->where('size_name', $item->size_name)->first();
                if (!$size) {
                    throw ValidationException::withMessages(['cart' => "Kích thước của {$item->product->name} không còn hợp lệ."]);
                }
                $price += (float) $size->price_adjustment;
            }

            // Lấy danh sách ID của các loại Topping được chọn cho
            $toppingIds = $item->toppings->pluck('topping_id')->unique()->values();
            $allowedToppings = collect();

            // Nếu khách hàng có chọn Topping
            if ($toppingIds->isNotEmpty()) {
                $allowedToppings = Topping::query()
                    ->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $item->product_id)
                    ->where('toppings.is_available', true)
                    ->whereIn('toppings.id', $toppingIds)
                    ->select('toppings.*')->get();

                // Nếu số lượng Topping hợp lệ tìm thấy khác với số lượng
                if ($allowedToppings->count() !== $toppingIds->count()) {
                    throw ValidationException::withMessages(['cart' => "Topping của {$item->product->name} không còn hợp lệ."]);
                }

                // Cộng dồn tổng giá tiền của tất cả các Topping đã chọn
                $price += (float) $allowedToppings->sum('price');
            }

            // Gán 2 thuộc tính ảo calculated_unit_price và
            $item->setAttribute('calculated_unit_price', $price);
            $item->setAttribute('calculated_toppings', $allowedToppings);

            // Đồng bộ lại giá tiền vào bảng `cart_items` trong
            if ((float) $item->unit_price !== $price) {
                DB::table('cart_items')->where('id', $item->id)->update(['unit_price' => $price, 'updated_at' => now()]);
                $item->unit_price = $price;
            }
        }

        return $items;
    }

    // hàm tính tổng số tiền của giỏ hàng trước khi áp dụng
    public function subtotal(Collection $items): float
    {
        // Tổng tiền = tổng của các, giá mỗi sản phẩm * số lượng tương ứng
        return (float) $items->sum(fn ($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }
}

