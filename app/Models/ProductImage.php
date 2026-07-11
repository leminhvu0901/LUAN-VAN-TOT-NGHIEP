<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'display_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute()
    {
        if (str_starts_with($this->image_path, 'storage/')) {
            return asset($this->image_path);
        }
        return asset('images/' . $this->image_path);
    }
}
