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

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('display_order');
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
