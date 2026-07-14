<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $table = 'orders';
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
        'inventory_reserved_at' => 'datetime',
        'inventory_released_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Assuming User is in App\Models\User or \App\User
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }
}
