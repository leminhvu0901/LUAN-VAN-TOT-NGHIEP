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

        if (str_starts_with($this->image, 'storage/')) {
            return asset($this->image);
        }

        return asset('images/' . $this->image);
    }
}
