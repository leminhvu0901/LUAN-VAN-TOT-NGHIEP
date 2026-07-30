<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('display_order');
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot('quantity_used')->withTimestamps();
    }

    public function toppings()
    {
        return $this->belongsToMany(Topping::class, 'product_toppings');
    }

    // Khuyến mãi giảm giá theo sản phẩm (scope='product') có chọn sản phẩm này.
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products');
    }

    // Các cấu hình "Mua X tặng Y" mà sản phẩm này là sản phẩm PHẢI MUA.
    public function buyXGetYAsBuyProduct()
    {
        return $this->hasMany(PromotionBuyXGetY::class, 'buy_product_id');
    }

    // Các cấu hình "Mua X tặng Y" mà sản phẩm này là QUÀ TẶNG.
    public function buyXGetYAsGiftProduct()
    {
        return $this->hasMany(PromotionBuyXGetY::class, 'gift_product_id');
    }

    public function hasSufficientMaterials(float $quantity = 1): bool
    {
        $recipes = $this->materials()->where('materials.is_active', true)->get();
        if ($recipes->isEmpty()) return true;

        foreach ($recipes as $material) {
            $available = $material->imports()->where('quantity', '>', 0)->where('remaining_quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today());
                })->sum('remaining_quantity');
            if ((float) $available + 0.0001 < (float) $material->pivot->quantity_used * $quantity) return false;
        }
        return true;
    }

    public function getImageUrlAttribute()
    {
        if (empty($this->image)) {
            return asset('images/products/placeholder.jpg');
        }

        return upload_url($this->image);
    }

    /**
     * Lấy thông tin khuyến mãi/giảm giá đang áp dụng cho sản phẩm này.
     * Trả về mảng chứa sale_price, old_price, percent, label hoặc null nếu không có giảm giá.
     */
    public function getDiscountInfoAttribute()
    {
        $now = now();

        // 1. Khuyến mãi scope='product' gắn trực tiếp cho sản phẩm này
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

        // 2. Khuyến mãi scope='category' gán cho danh mục sản phẩm
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

        $allPromos = $productPromos->concat($categoryPromos);

        if ($allPromos->isEmpty()) {
            return null;
        }

        $bestPromo = null;
        $maxDiscount = 0;

        foreach ($allPromos as $promo) {
            if ($promo->is_recurring) {
                $nowStr = $now->format('H:i:s');
                $currentDay = $now->dayOfWeekIso;
                if (is_array($promo->recurring_days) && count($promo->recurring_days) > 0 && !in_array($currentDay, $promo->recurring_days)) {
                    continue;
                }
                if ($promo->recurring_start_time && $nowStr < $promo->recurring_start_time) continue;
                if ($promo->recurring_end_time && $nowStr > $promo->recurring_end_time) continue;
            }

            if ($promo->type === 'percent') {
                $discount = round($this->base_price * ((float) $promo->value / 100));
                if ($promo->max_discount_amount) {
                    $discount = min($discount, (float) $promo->max_discount_amount);
                }
            } else {
                $discount = (float) $promo->value;
            }
            $discount = min($discount, (float) $this->base_price);

            if ($discount > $maxDiscount) {
                $maxDiscount = $discount;
                $bestPromo = $promo;
            }
        }

        if (!$bestPromo || $maxDiscount <= 0) {
            return null;
        }

        $rawSalePrice = max(0, (float) $this->base_price - $maxDiscount);
        // Làm tròn LÊN đến bội số 1.000đ gần nhất (Ceil to 1.000đ)
        $salePrice = ceil($rawSalePrice / 1000) * 1000;

        // Nếu làm tròn lên khiến giá bằng hoặc lớn hơn giá gốc, điều chỉnh lại để vẫn có giảm giá
        if ($salePrice >= (float) $this->base_price && $rawSalePrice < (float) $this->base_price) {
            $salePrice = max(0, (float) $this->base_price - 1000);
        }

        $finalDiscount = max(0, (float) $this->base_price - $salePrice);
        if ($finalDiscount <= 0) {
            return null;
        }

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
