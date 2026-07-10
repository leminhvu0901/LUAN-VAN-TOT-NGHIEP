<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'quantity',
        'total_price',
        'note',
        'expiration_date',
        'remaining_quantity'
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
