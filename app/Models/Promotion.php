<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class, 'promotion_id');
    }
}
