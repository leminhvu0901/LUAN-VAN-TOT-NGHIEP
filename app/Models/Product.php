<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường (không khóa trường nào)

    /**
     * Mối quan hệ: Sản phẩm thuộc về một Danh mục (Category)
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Mối quan hệ: Một sản phẩm có nhiều Kích cỡ khác nhau (ProductSize - ví dụ: M, L)
     */
    public function sizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id');
    }

    /**
     * Mối quan hệ: Một sản phẩm có nhiều hình ảnh đi kèm, sắp xếp theo thứ tự hiển thị
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('display_order');
    }

    /**
     * Mối quan hệ: Một sản phẩm được làm từ nhiều Nguyên liệu (Material) thông qua bảng trung gian product_materials
     */
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot('quantity_used')->withTimestamps();
    }

    /**
     * Mối quan hệ: Một sản phẩm có thể thêm nhiều loại Topping (thông qua bảng product_toppings)
     */
    public function toppings()
    {
        return $this->belongsToMany(Topping::class, 'product_toppings');
    }

    /**
     * Mối quan hệ: Khuyến mãi áp dụng riêng cho sản phẩm này (scope='product')
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products');
    }

    /**
     * Mối quan hệ: Danh sách luật "Mua X tặng Y" mà sản phẩm này đóng vai trò là Sản phẩm chính (Phải mua)
     */
    public function buyXGetYAsBuyProduct()
    {
        return $this->hasMany(PromotionBuyXGetY::class, 'buy_product_id');
    }

    /**
     * Mối quan hệ: Danh sách luật "Mua X tặng Y" mà sản phẩm này đóng vai trò là Quà tặng đi kèm
     */
    public function buyXGetYAsGiftProduct()
    {
        return $this->hasMany(PromotionBuyXGetY::class, 'gift_product_id');
    }

    /**
     * Kiểm tra xem kho còn đủ nguyên liệu để chế biến sản phẩm này với số lượng yêu cầu hay không
     * (Chỉ xét các lô nguyên liệu còn hạn sử dụng và đang hoạt động)
     */
    public function hasSufficientMaterials(float $quantity = 1): bool
    {
        // Lấy danh sách nguyên vật liệu công thức cấu thành sản phẩm
        $recipes = $this->materials()->where('materials.is_active', true)->get();
        if ($recipes->isEmpty())
            return true; // Không cần nguyên liệu -> luôn đủ

        foreach ($recipes as $material) {
            // Tính tổng tồn kho khả dụng từ các lô nhập hàng còn hạn sử dụng
            $available = $material->imports()->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today());
                })->sum('remaining_quantity');

            // Nếu lượng tồn kho thực tế nhỏ hơn lượng cần dùng cho công thức -> không đủ nguyên liệu
            if ((float) $available + 0.0001 < (float) $material->pivot->quantity_used * $quantity)
                return false;
        }
        return true;
    }

    /**
     * Accessor: Lấy URL hình ảnh đầy đủ của sản phẩm. Trả về ảnh mặc định (placeholder) nếu không có ảnh
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->image)) {
            return asset('images/products/placeholder.jpg');
        }

        return upload_url($this->image);
    }

    /**
     * Accessor: Tính toán và lấy thông tin chương trình giảm giá tốt nhất đang được áp dụng cho sản phẩm này
     * Trả về mảng thông tin [promotion, discount_amount, sale_price, old_price, percent, label] hoặc null
     */
    public function getDiscountInfoAttribute()
    {
        $now = now();

        // 1. Quét khuyến mãi áp dụng trực tiếp cho sản phẩm này
        $productPromos = $this->promotions()
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->get();

        // 2. Quét khuyến mãi áp dụng cho toàn bộ danh mục chứa sản phẩm này
        $categoryPromos = collect();
        if ($this->category_id) {
            $categoryPromos = Promotion::query()
                ->where('scope', 'category')
                ->where('is_active', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->where(function ($q) {
                    $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
                })
                ->whereHas('categories', function ($q) {
                    $q->where('categories.id', $this->category_id);
                })
                ->get();
        }

        // Gộp chung tất cả các khuyến mãi tìm được
        $allPromos = $productPromos->concat($categoryPromos);

        if ($allPromos->isEmpty()) {
            return null; // Không có khuyến mãi nào phù hợp
        }

        $bestPromo = null;
        $maxDiscount = 0;

        // Lặp qua từng khuyến mãi để tìm cái có mức giảm giá cao nhất
        foreach ($allPromos as $promo) {
            // Kiểm tra điều kiện lặp lại (theo giờ, theo các ngày trong tuần)
            if ($promo->is_recurring) {
                $nowStr = $now->format('H:i:s');
                $currentDay = $now->dayOfWeekIso;
                if (is_array($promo->recurring_days) && count($promo->recurring_days) > 0 && !in_array($currentDay, $promo->recurring_days)) {
                    continue;
                }
                if ($promo->recurring_start_time && $nowStr < $promo->recurring_start_time)
                    continue;
                if ($promo->recurring_end_time && $nowStr > $promo->recurring_end_time)
                    continue;
            }

            // Tính số tiền được giảm tương ứng với từng kiểu khuyến mãi (phần trăm hoặc giảm tiền mặt trực tiếp)
            if ($promo->type === 'percent') {
                $discount = round($this->base_price * ((float) $promo->value / 100));
                if ($promo->max_discount_amount) {
                    $discount = min($discount, (float) $promo->max_discount_amount);
                }
            } else {
                $discount = (float) $promo->value;
            }
            $discount = min($discount, (float) $this->base_price); // Số tiền giảm không vượt quá giá gốc

            // Chọn mức giảm tốt nhất
            if ($discount > $maxDiscount) {
                $maxDiscount = $discount;
                $bestPromo = $promo;
            }
        }

        if (!$bestPromo || $maxDiscount <= 0) {
            return null;
        }

        $rawSalePrice = max(0, (float) $this->base_price - $maxDiscount);
        // Làm tròn LÊN đến bội số 1.000đ gần nhất (Ceil to 1.000đ) để giá tiền hiển thị đẹp mắt
        $salePrice = ceil($rawSalePrice / 1000) * 1000;

        // Nếu làm tròn lên vô tình làm giá bán mới lớn hơn hoặc bằng giá gốc -> trừ bớt đi 1.000đ để giữ lại ưu đãi
        if ($salePrice >= (float) $this->base_price && $rawSalePrice < (float) $this->base_price) {
            $salePrice = max(0, (float) $this->base_price - 1000);
        }

        // 
        $finalDiscount = max(0, (float) $this->base_price - $salePrice);
        if ($finalDiscount <= 0) {
            return null;
        }

        // Tính toán tỷ lệ giảm giá theo phần trăm thực tế
        $percent = $bestPromo->type === 'percent'
            ? (int) round($bestPromo->value)
            : (int) round(($finalDiscount / $this->base_price) * 100);

        return [
            'promotion' => $bestPromo,
            'discount_amount' => $finalDiscount,
            'sale_price' => $salePrice,
            'old_price' => (float) $this->base_price,
            'percent' => $percent,
            'label' => "Giảm {$percent}%",
        ];
    }
}
