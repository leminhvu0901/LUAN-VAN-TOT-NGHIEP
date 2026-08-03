<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductSize;
use App\Models\Topping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

//"Dịch vụ tính giá giỏ hàng".
class CartPricingService
{
    /**
     * public: Cho phép gọi hàm từ bất kỳ nơi nào trong hệ thống.
     * pricedItems(...): Hàm tính toán và cập nhật giá bán thực tế cho từng sản phẩm trong giỏ hàng (áp dụng giá theo Size và các Topping đi kèm).
     * 
     * Các tham số truyền vào:
     * - Cart $cart: Đối tượng giỏ hàng cần tính toán giá.
     * - bool $lock: Có khóa dòng dữ liệu trong DB hay không (thường bằng true khi bắt đầu tạo đơn hàng để tránh xung đột dữ liệu). Mặc định là false.
     * - ?array $selectedIds: Danh sách ID của các cart_items cụ thể được chọn thanh toán (nếu null, hệ thống sẽ tính giá toàn bộ giỏ hàng).
     * 
     * Trả về kiểu dữ liệu: Collection (Danh sách các sản phẩm trong giỏ hàng đã được nạp giá tiền chính xác).
     */
    public function pricedItems(Cart $cart, bool $lock = false, ?array $selectedIds = null): Collection
    {
        // Khởi tạo câu truy vấn lấy các sản phẩm trong giỏ hàng (Eager Loading mối quan hệ 'product' và các 'toppings')
        $query = CartItem::query()->with(['product', 'toppings.topping'])->where('cart_id', $cart->id); // Lấy danh sách sản phẩm trong giỏ kèm chi tiết Topping

        // Nếu người dùng chỉ tick chọn một vài sản phẩm để thanh toán, chỉ lọc lấy các sản phẩm đó
        if ($selectedIds !== null && count($selectedIds) > 0) {
            $query->whereIn('id', $selectedIds);
        }

        // Nếu tham số $lock = true, áp dụng cơ chế khóa dòng dữ liệu để tránh cập nhật đồng thời từ các luồng khác
        if ($lock) {
            $query->lockForUpdate();
        }
        
        // Thực thi câu lệnh SQL để lấy danh sách sản phẩm trong giỏ hàng
        $items = $query->get();

        // Kiểm tra nếu danh sách trống, báo lỗi
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }

        // Vòng lặp duyệt qua từng sản phẩm trong giỏ hàng để bắt đầu tính toán giá
        foreach ($items as $item) {
            // 1. Kiểm tra trạng thái kinh doanh của sản phẩm
            if (!$item->product || !$item->product->is_active) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng có sản phẩm đã ngừng kinh doanh.']);
            }
            
            // 2. Kiểm tra tính hợp lệ của số lượng mua (phải từ 1 đến 99 ly)
            if (!is_numeric($item->quantity) || (int) $item->quantity < 1 || (int) $item->quantity > 99) {
                throw ValidationException::withMessages(['cart' => 'Số lượng sản phẩm trong giỏ không hợp lệ.']);
            }

            // Lấy giá cơ bản của sản phẩm (base_price) làm mốc giá ban đầu
            $price = (float) $item->product->base_price;
            $size = null;
            
            // 3. Nếu khách chọn Size (Ví dụ: Size M, Size L), tìm phần giá chênh lệch của Size đó và cộng dồn vào giá gốc
            if ($item->size_name) {
                $size = ProductSize::query()->where('product_id', $item->product_id)
                    ->where('size_name', $item->size_name)->first(); // Tìm giá chênh lệch của size được chọn
                
                // Nếu không tìm thấy Size này trong DB của sản phẩm, báo lỗi
                if (!$size) {
                    throw ValidationException::withMessages(['cart' => "Kích thước của {$item->product->name} không còn hợp lệ."]);
                }
                // Cộng thêm tiền chênh lệch của Size (ví dụ: size L cộng thêm 5,000đ)
                $price += (float) $size->price_adjustment;
            }

            // Lấy danh sách ID của các loại Topping được chọn cho sản phẩm này
            $toppingIds = $item->toppings->pluck('topping_id')->unique()->values();
            $allowedToppings = collect();
            
            // 4. Nếu khách hàng có chọn Topping (ví dụ: Trân châu, Thạch dừa)
            if ($toppingIds->isNotEmpty()) {
                // Truy vấn tìm các Topping hợp lệ: Phải được bật bán (is_available = true) và phải thuộc danh mục topping được phép của sản phẩm đó
                $allowedToppings = Topping::query()
                    ->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $item->product_id)
                    ->where('toppings.is_available', true)
                    ->whereIn('toppings.id', $toppingIds)
                    ->select('toppings.*')->get(); // Lấy danh sách topping hợp lệ cùng giá tiền của từng loại
                
                // Nếu số lượng Topping hợp lệ tìm thấy khác với số lượng Topping khách chọn -> Có topping không hợp lệ, báo lỗi
                if ($allowedToppings->count() !== $toppingIds->count()) {
                    throw ValidationException::withMessages(['cart' => "Topping của {$item->product->name} không còn hợp lệ."]);
                }
                
                // Cộng dồn tổng giá tiền của tất cả các Topping đã chọn vào giá của sản phẩm
                $price += (float) $allowedToppings->sum('price');
            }

            // Gán 2 thuộc tính ảo (calculated_unit_price và calculated_toppings) vào đối tượng CartItem 
            // để các Service khác có thể lấy ra sử dụng mà không cần tính lại từ đầu
            $item->setAttribute('calculated_unit_price', $price);
            $item->setAttribute('calculated_toppings', $allowedToppings);
            
            // 5. Đồng bộ lại giá tiền vào bảng `cart_items` trong Database nếu giá tính toán mới khác với giá đã lưu trước đó
            if ((float) $item->unit_price !== $price) {
                DB::table('cart_items')->where('id', $item->id)->update(['unit_price' => $price, 'updated_at' => now()]);
                $item->unit_price = $price;
            }
        }

        return $items;
    }

    /**
     * public: Cho phép gọi hàm từ bên ngoài.
     * subtotal(Collection $items): Hàm tính tổng số tiền của giỏ hàng trước khi áp dụng mã giảm giá.
     * - Tham số Collection $items: Danh sách các CartItem đã được nạp thuộc tính calculated_unit_price.
     * Trả về kiểu dữ liệu: float (Tổng tiền của toàn bộ sản phẩm trong giỏ hàng).
     */
    public function subtotal(Collection $items): float
    {
        // Tổng tiền = tổng của các (giá mỗi sản phẩm * số lượng tương ứng)
        return (float) $items->sum(fn ($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }
}

