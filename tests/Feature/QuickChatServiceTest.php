<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuickChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recognizes_momo_intent(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'Có thanh toán MoMo không?']);

        $response->assertOk()->assertJson(['intent' => 'momo']);
    }

    public function test_recognizes_cod_intent_via_phrase_without_literal_cod_word(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'Có trả tiền khi nhận hàng không?']);

        $response->assertOk()->assertJson(['intent' => 'cod']);
    }

    public function test_recognizes_shipping_intent(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'Phí ship bao nhiêu?']);

        $response->assertOk()->assertJson(['intent' => 'shipping']);
    }

    public function test_recognizes_intent_from_unaccented_vietnamese_input(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'thanh toan momo duoc khong']);

        $response->assertOk()->assertJson(['intent' => 'momo']);
    }

    public function test_recognizes_product_intent_from_natural_recommendation_phrasing(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'uống gì giải nhiệt']);

        $response->assertOk()->assertJson(['intent' => 'product']);
    }

    public function test_out_of_scope_question_returns_fallback(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'Hôm nay đội nào đá bóng?']);

        $response->assertOk()
            ->assertJson([
                'intent' => null,
                'answer' => config('quick_chat.fallback_freeform'),
                'items' => [],
            ]);

        $labels = collect($response->json('suggestions'))->pluck('label');
        $this->assertEquals(
            ['Sản phẩm', 'Giá bán', 'Khuyến mãi', 'Thanh toán', 'Giao hàng', 'Theo dõi đơn hàng'],
            $labels->all()
        );
    }

    public function test_fallback_suggestion_button_resubmits_matching_intent(): void
    {
        // Nút gợi ý "Giao hàng" gửi lại đúng intent 'shipping' qua askByIntent(), bỏ qua chấm điểm.
        $response = $this->postJson('/quick-chat/ask', ['intent' => 'shipping']);

        $response->assertOk()->assertJson(['intent' => 'shipping']);
    }

    public function test_empty_question_does_not_fail_validation(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => '   ']);

        $response->assertOk();
        $this->assertNull($response->json('intent'));
    }

    public function test_question_over_300_characters_is_rejected(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => str_repeat('a', 301)]);

        $response->assertStatus(422);
    }

    public function test_script_tag_in_question_does_not_break_response(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => '<script>alert(1)</script> momo']);

        $response->assertOk()->assertJson(['intent' => 'momo']);
    }

    public function test_only_active_products_are_returned(): void
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau', 'sku' => 'TS-1', 'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => true]);
        Product::create(['name' => 'Trà sữa hết bán', 'slug' => 'tra-sua-het-ban', 'sku' => 'TS-2', 'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => false]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'Có những loại trà sữa nào?']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà sữa trân châu'));
        $this->assertFalse($names->contains('Trà sữa hết bán'));
    }

    public function test_tea_question_returns_tea_products_not_bestselling_coffee(): void
    {
        $teaCat = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $coffeeCat = DB::table('categories')->insertGetId(['name' => 'Cà phê', 'slug' => 'ca-phe', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau', 'sku' => 'TS-1', 'base_price' => 30000, 'category_id' => $teaCat, 'is_active' => true]);
        Product::create(['name' => 'Cà phê đen đá', 'slug' => 'ca-phe-den-da', 'sku' => 'CF-1', 'base_price' => 25000, 'category_id' => $coffeeCat, 'is_active' => true]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'có trà nào ngon không']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà sữa trân châu'));
        $this->assertFalse($names->contains('Cà phê đen đá'));
    }

    public function test_only_valid_online_promotions_are_returned(): void
    {
        Promotion::create([
            'code' => 'ONLINE10', 'type' => 'percent', 'value' => 10,
            'is_active' => true, 'applies_to' => 'delivery',
        ]);
        Promotion::create([
            'code' => 'POSONLY', 'type' => 'percent', 'value' => 20,
            'is_active' => true, 'applies_to' => 'pickup',
        ]);
        Promotion::create([
            'code' => 'EXPIRED', 'type' => 'fixed', 'value' => 5000,
            'is_active' => true, 'applies_to' => 'all', 'end_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'Hôm nay có khuyến mãi gì?']);

        $response->assertOk()->assertJson(['intent' => 'promotion']);
        $codes = collect($response->json('items'))->pluck('code');
        $this->assertTrue($codes->contains('ONLINE10'));
        $this->assertFalse($codes->contains('POSONLY'));
        $this->assertFalse($codes->contains('EXPIRED'));
    }

    public function test_single_word_question_returns_suggestions_instead_of_guessing(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'giá']);

        $response->assertOk();
        $this->assertNull($response->json('intent'));
        $this->assertNotEmpty($response->json('suggestions'));
    }

    public function test_rate_limit_blocks_after_20_requests_per_minute(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/quick-chat/ask', ['question' => 'giờ mở cửa'])->assertOk();
        }

        $this->postJson('/quick-chat/ask', ['question' => 'giờ mở cửa'])->assertStatus(429);
    }

    // Seed danh mục + sản phẩm mô phỏng dữ liệu thật (kể cả sản phẩm cà phê không có chữ "cà phê"
    // trong tên như "Bạc xỉu" -> buộc phải lọc theo danh mục, không chỉ theo tên).
    private function seedCatalog(): void
    {
        $coffee = DB::table('categories')->insertGetId(['name' => 'Cà phê', 'slug' => 'ca-phe', 'is_active' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $milktea = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $fruittea = DB::table('categories')->insertGetId(['name' => 'Trà trái cây', 'slug' => 'tra-trai-cay', 'is_active' => 1, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()]);
        $other = DB::table('categories')->insertGetId(['name' => 'Đồ uống khác', 'slug' => 'do-uong-khac', 'is_active' => 1, 'display_order' => 4, 'created_at' => now(), 'updated_at' => now()]);

        Product::create(['name' => 'Cà phê đen đá', 'slug' => 'ca-phe-den-da', 'sku' => 'CF-1', 'base_price' => 25000, 'category_id' => $coffee, 'is_active' => true]);
        Product::create(['name' => 'Bạc xỉu', 'slug' => 'bac-xiu', 'sku' => 'CF-2', 'base_price' => 30000, 'category_id' => $coffee, 'is_active' => true]);
        Product::create(['name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau', 'sku' => 'TS-1', 'base_price' => 35000, 'category_id' => $milktea, 'is_active' => true]);
        Product::create(['name' => 'Trà đào cam sả', 'slug' => 'tra-dao-cam-sa', 'sku' => 'TT-1', 'base_price' => 32000, 'category_id' => $fruittea, 'is_active' => true]);
        Product::create(['name' => 'Milo dầm đá', 'slug' => 'milo-dam-da', 'sku' => 'DU-1', 'base_price' => 28000, 'category_id' => $other, 'is_active' => true]);
    }

    // Hồi quy đúng lỗi thật đã báo: "món nào đắt nhất"/"rẻ nhất" là yêu cầu SẮP XẾP theo giá cho toàn
    // bộ menu (không phải câu hỏi mơ hồ về loại đồ uống) -> phải trả sản phẩm, sắp đúng thứ tự giá.
    public function test_price_sort_expensive_returns_products_sorted_by_price_desc(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món nào đắt nhất']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertEquals('Trà sữa trân châu', $names->first()); // 35.000đ, cao nhất
        $this->assertStringContainsString('giá khởi điểm cao nhất', $response->json('answer'));
    }

    public function test_price_sort_cheap_returns_products_sorted_by_price_asc(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'uống gì rẻ nhất']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertEquals('Cà phê đen đá', $names->first()); // 25.000đ, thấp nhất
        $this->assertStringContainsString('giá khởi điểm thấp nhất', $response->json('answer'));
    }

    // Sắp xếp giá phải kết hợp được với danh mục cụ thể ("trà sữa nào đắt nhất") thay vì bỏ qua.
    public function test_price_sort_combined_with_specific_category(): void
    {
        $milktea = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $coffee = DB::table('categories')->insertGetId(['name' => 'Cà phê', 'slug' => 'ca-phe', 'is_active' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau', 'sku' => 'TS-1', 'base_price' => 35000, 'category_id' => $milktea, 'is_active' => true]);
        Product::create(['name' => 'Trà sữa khoai môn', 'slug' => 'tra-sua-khoai-mon', 'sku' => 'TS-2', 'base_price' => 45000, 'category_id' => $milktea, 'is_active' => true]);
        Product::create(['name' => 'Cà phê đen đá', 'slug' => 'ca-phe-den-da', 'sku' => 'CF-1', 'base_price' => 99000, 'category_id' => $coffee, 'is_active' => true]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'trà sữa nào đắt nhất']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertEquals('Trà sữa khoai môn', $names->first()); // đắt nhất TRONG NHÓM trà sữa
        $this->assertFalse($names->contains('Cà phê đen đá')); // vẫn phải lọc đúng danh mục, dù giá cao hơn
    }

    public function test_need_tinh_tao_prefers_coffee_products(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'uống gì giúp tỉnh táo?']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Cà phê đen đá'));
        $this->assertTrue($names->contains('Bạc xỉu')); // cà phê không có chữ "cà phê" trong tên
        $this->assertFalse($names->contains('Trà sữa trân châu'));
        $this->assertStringContainsString('tỉnh táo', $response->json('answer'));
    }

    // Hồi quy đúng lỗi thật đã báo: "món nào uống tỉnh ngủ" trước đó rơi vào fallback vì thiếu từ
    // khóa "tỉnh ngủ" trong danh sách nhận diện nhu cầu tỉnh táo.
    public function test_need_tinh_ngu_prefers_coffee_products(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món nào uống tỉnh ngủ']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Cà phê đen đá'));
        $this->assertFalse($names->contains('Trà sữa trân châu'));
    }

    public function test_need_refreshing_prefers_fruit_tea_products(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món nào giải khát']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà đào cam sả'));
        $this->assertFalse($names->contains('Cà phê đen đá'));
    }

    public function test_need_refreshing_recognizes_thanh_mat_phrasing(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'uống gì thanh mát']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà đào cam sả'));
        $this->assertFalse($names->contains('Cà phê đen đá'));
    }

    public function test_negation_excludes_coffee_products(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'tôi không uống cà phê']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertFalse($names->contains('Cà phê đen đá'));
        $this->assertFalse($names->contains('Bạc xỉu'));
        $this->assertTrue($names->isNotEmpty());
    }

    // Câu hỏi mơ hồ ("uống gì ngon", "uống gì rẻ nhất") chỉ trả lời đúng 1 câu thông báo trung thực,
    // KHÔNG kèm sản phẩm bán chạy hay nút gợi ý danh mục (không đoán mò khi chưa rõ ý khách).
    // "ngon"/"bán chạy" giờ là từ khóa CÓ CHỦ ĐÍCH của need 'popular' (khác với thật sự mơ hồ) — dùng
    // câu không chứa từ khóa nào đã cấu hình để kiểm tra đúng nhánh mơ hồ hoàn toàn.
    public function test_vague_question_shows_only_honest_notice_without_extra_content(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'sản phẩm nào phù hợp với tôi']);

        $response->assertOk()->assertJson([
            'intent' => 'product',
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ]);
        $this->assertStringContainsString('chưa nêu rõ loại đồ uống', $response->json('answer'));
    }

    // 'popular' need ("ngon", "bán chạy", "nổi bật"...) khác nhánh mơ hồ ở trên — có tín hiệu RÕ RÀNG
    // nên vẫn trả sản phẩm bán chạy kèm câu dẫn riêng.
    public function test_popular_need_shows_bestseller_products(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'uống gì ngon']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $this->assertStringContainsString('bán chạy', $response->json('answer'));
        $this->assertNotEmpty($response->json('items'));
    }

    public function test_tea_with_low_sugar_returns_tea_and_sugar_guidance(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'tôi muốn uống trà ít ngọt']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà sữa trân châu') || $names->contains('Trà đào cam sả'));
        $this->assertFalse($names->contains('Cà phê đen đá'));
        $this->assertStringContainsString('mức đường', $response->json('answer'));
    }

    public function test_need_recognized_from_unaccented_input(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'uong gi giup tinh tao']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Cà phê đen đá'));
        $this->assertFalse($names->contains('Trà sữa trân châu'));
    }

    public function test_conflicting_need_returns_honest_empty_state(): void
    {
        $this->seedCatalog();

        $response = $this->postJson('/quick-chat/ask', ['question' => 'muốn tỉnh táo nhưng không uống cà phê']);

        $response->assertOk();
        $this->assertEmpty($response->json('items'));
        $this->assertStringContainsString('chưa tìm được gợi ý phù hợp với cả hai yêu cầu', $response->json('answer'));
    }

    // Danh mục hợp lệ nhưng KHÔNG còn sản phẩm active nào — empty-state trung thực, kèm nút gợi ý
    // danh mục (khác nhánh "mâu thuẫn nhu cầu" ở trên nhưng dùng chung productResponse()->honestNoMatch()).
    public function test_no_active_products_in_category_returns_honest_empty_state(): void
    {
        $milktea = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau', 'sku' => 'TS-1', 'base_price' => 35000, 'category_id' => $milktea, 'is_active' => false]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'trà sữa']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $this->assertEmpty($response->json('items'));
        $this->assertNotEmpty($response->json('suggestions'));
    }

    // Hồi quy đúng lỗi thật đã gặp: hỏi "trà sữa" (tên danh mục nguyên cụm) không được lẫn "Cà phê
    // sữa đá" chỉ vì trùng từ "sữa" — phải khớp CHÍNH XÁC theo danh mục, không rơi xuống rời rạc từng từ.
    public function test_milk_tea_category_question_does_not_return_coffee_products(): void
    {
        $coffee = DB::table('categories')->insertGetId(['name' => 'Cà phê', 'slug' => 'ca-phe', 'is_active' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $milktea = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Cà phê sữa đá', 'slug' => 'ca-phe-sua-da', 'sku' => 'CF-1', 'base_price' => 32000, 'category_id' => $coffee, 'is_active' => true]);
        Product::create(['name' => 'Trà sữa ô long nướng', 'slug' => 'tra-sua-o-long-nuong', 'sku' => 'TS-1', 'base_price' => 40000, 'category_id' => $milktea, 'is_active' => true]);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'trà sữa']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà sữa ô long nướng'));
        $this->assertFalse($names->contains('Cà phê sữa đá'));
    }

    // Nhu cầu ánh xạ phải khớp thêm qua MÔ TẢ sản phẩm thật, không chỉ đúng 1 danh mục cứng —
    // "béo ngậy" phải tìm được cả sản phẩm ở danh mục Sữa chua (mô tả chứa "béo ngậy") lẫn Trà sữa.
    public function test_need_matches_products_by_description_across_categories(): void
    {
        $milktea = DB::table('categories')->insertGetId(['name' => 'Trà sữa', 'slug' => 'tra-sua', 'is_active' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $yogurt = DB::table('categories')->insertGetId(['name' => 'Sữa chua', 'slug' => 'sua-chua', 'is_active' => 1, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['name' => 'Trà sữa khoai môn', 'slug' => 'tra-sua-khoai-mon', 'sku' => 'TS-1', 'base_price' => 40000, 'category_id' => $milktea, 'is_active' => true]);
        Product::create(['name' => 'Sữa chua đào', 'slug' => 'sua-chua-dao', 'sku' => 'SC-1', 'base_price' => 25000, 'category_id' => $yogurt, 'is_active' => true, 'description' => 'Sữa chua béo ngậy phủ đầy đào tươi ngọt thơm.']);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'muốn uống gì béo ngậy']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà sữa khoai môn')); // khớp qua category_match
        $this->assertTrue($names->contains('Sữa chua đào')); // khớp qua description_keywords, khác category
    }
}
