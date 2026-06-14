<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::table('promotions')->where('code', 'HAPPY10')->exists()) {
            DB::table('promotions')->insert([
                'code' => 'HAPPY10',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 0,
                'start_at' => now(),
                'end_at' => null,
                'is_active' => 1,
                'description' => 'Giảm 10% cho 20 đơn hàng đầu tiên',
                'usage_limit' => 20,
                'used_count' => 0,
                'max_discount_amount' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
