<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Chatbox không còn dùng AI — nút gợi ý chủ đề trả lời tĩnh/truy vấn thật qua askByIntent(); ô nhập
// câu hỏi tự do luôn trả về câu fallback + gợi ý nút bấm (không phân loại ý định).
class QuickChatServiceTest extends TestCase
{
    use RefreshDatabase;

    // Seed danh mục + sản phẩm mô phỏng dữ liệu thật.
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

    // ---------------------------------------------------------------------------------------------
    // Validate ở controller
    // ---------------------------------------------------------------------------------------------

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

    public function test_rate_limit_blocks_after_20_requests_per_minute(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/quick-chat/ask', ['intent' => 'opening_hours'])->assertOk();
        }

        $this->postJson('/quick-chat/ask', ['intent' => 'opening_hours'])->assertStatus(429);
    }

    // ---------------------------------------------------------------------------------------------
    // Nút gợi ý chủ đề (askByIntent)
    // ---------------------------------------------------------------------------------------------

    public function test_suggestion_button_resubmits_matching_intent(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['intent' => 'shipping']);

        $response->assertOk()->assertJson(['intent' => 'shipping']);
    }

    public function test_product_listing_button_returns_active_products_only(): void
    {
        $this->seedCatalog();
        DB::table('products')->where('slug', 'milo-dam-da')->update(['is_active' => 0]);

        $response = $this->postJson('/quick-chat/ask', ['intent' => 'product']);

        $response->assertOk()->assertJson(['intent' => 'product']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->isNotEmpty());
        $this->assertFalse($names->contains('Milo dầm đá'));
    }

    // ---------------------------------------------------------------------------------------------
    // Câu hỏi tự do — luôn trả về fallback kèm gợi ý, không phân loại ý định.
    // ---------------------------------------------------------------------------------------------

    public function test_free_text_question_always_returns_fallback_with_suggestions(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'có trà đào không']);

        $response->assertOk()->assertJson([
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
        ]);
        $this->assertNotEmpty($response->json('suggestions'));
        $this->assertEmpty($response->json('items'));
    }

    public function test_fallback_suggestion_buttons_resolve_to_real_intents(): void
    {
        $response = $this->postJson('/quick-chat/ask', ['question' => 'câu hỏi bất kỳ']);
        $response->assertOk();

        foreach ($response->json('suggestions') as $suggestion) {
            $intent = collect(config('quick_chat.intents'))->firstWhere('id', $suggestion['intent_id']);
            $this->assertNotNull($intent, "fallback_suggestions chứa intent_id '{$suggestion['intent_id']}' không tồn tại trong config('quick_chat.intents').");
        }
    }
}
