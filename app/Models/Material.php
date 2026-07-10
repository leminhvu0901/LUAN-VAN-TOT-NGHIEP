<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'unit_price',
        'current_stock',
        'is_active',
    ];

    public function imports()
    {
        return $this->hasMany(MaterialImport::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_materials')
                    ->withPivot('quantity_used')
                    ->withTimestamps();
    }
}
