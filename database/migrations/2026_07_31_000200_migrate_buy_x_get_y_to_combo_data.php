<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Chuyển dữ liệu "Mua X tặng Y" cũ, scope=buy_x_get_y, bảng promotion_buy_x_get_y sang cấu trúc
// "Combo" mới, scope=combo, promotion_combos 1-1 + promotion_combo_items nhiều dòng, dùng Query
// Builder thuần, không Eloquent để migration này vẫn chạy đúng kể cả sau khi model
// PromotionBuyXGetY bị xoá khỏi codebase. KHÔNG xoá bảng promotion_buy_x_get_y ở đây tách riêng
// migration drop sau, để rollback được từng bước.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotion_buy_x_get_y') || !Schema::hasTable('promotion_combos')) {
            return;
        }

        $rows = DB::table('promotion_buy_x_get_y')->get();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                // Combo cũ chỉ có phần "Tặng quà", không có phần "Giảm giá", discount_type/discount_value = null.
                DB::table('promotion_combos')->insert([
                    'promotion_id' => $row->promotion_id,
                    'discount_type' => null,
                    'discount_value' => null,
                    'max_discount_amount' => null,
                    'gift_product_id' => $row->gift_product_id,
                    'gift_quantity' => $row->gift_quantity,
                    'auto_add_gift' => $row->auto_add_gift,
                    'max_applications_per_order' => $row->max_applications_per_order,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                DB::table('promotion_combo_items')->insert([
                    'promotion_id' => $row->promotion_id,
                    'product_id' => $row->buy_product_id,
                    'quantity' => $row->buy_quantity,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                DB::table('promotions')->where('id', $row->promotion_id)->update(['scope' => 'combo']);
            });
        }
    }

    // Best-effort: chỉ dựng lại được các combo có ĐÚNG 1 sản phẩm + có bật "Tặng quà" khớp hình dạng
    // schema cũ. Combo nhiều sản phẩm hoặc combo có bật "Giảm giá", tạo SAU khi rework không có
    // tương đương ở schema cũ nên bị bỏ qua khi rollback, đây là giới hạn đã biết của down(), không
    // phải bug.
    public function down(): void
    {
        if (!Schema::hasTable('promotion_buy_x_get_y') || !Schema::hasTable('promotion_combos')) {
            return;
        }

        $combos = DB::table('promotion_combos')->whereNotNull('gift_product_id')->get();

        foreach ($combos as $combo) {
            $items = DB::table('promotion_combo_items')->where('promotion_id', $combo->promotion_id)->get();
            if ($items->count() !== 1) {
                continue;
            }
            $item = $items->first();

            DB::transaction(function () use ($combo, $item) {
                DB::table('promotion_buy_x_get_y')->insert([
                    'promotion_id' => $combo->promotion_id,
                    'buy_product_id' => $item->product_id,
                    'buy_quantity' => $item->quantity,
                    'gift_product_id' => $combo->gift_product_id,
                    'gift_quantity' => $combo->gift_quantity,
                    'max_applications_per_order' => $combo->max_applications_per_order,
                    'auto_add_gift' => $combo->auto_add_gift,
                    'created_at' => $combo->created_at,
                    'updated_at' => $combo->updated_at,
                ]);

                DB::table('promotions')->where('id', $combo->promotion_id)->update(['scope' => 'buy_x_get_y']);
            });
        }
    }
};
