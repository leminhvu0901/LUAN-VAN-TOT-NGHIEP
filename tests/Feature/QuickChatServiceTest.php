<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\GeminiIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Chatbox chỉ dùng Gemini để hiểu câu hỏi tự do. Toàn bộ test LUÔN mock GeminiIntentService, KHÔNG
// gọi API Gemini thật. Gemini chỉ trả JSON phân loại; Laravel truy vấn dữ liệu thật và dựng câu trả lời.
class QuickChatServiceTest extends TestCase
{
    use RefreshDatabase;

    // JSON hợp lệ mẫu mà Gemini trả về (đã qua validate của GeminiIntentService).
    private function geminiPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'out_of_scope',
            'product_query' => '',
            'category' => '',
            'max_price' => 0,
            'min_price' => 0,
            'preferences' => [],
            'exclusions' => [],
            'confidence' => 0.9,
        ], $overrides);
    }

    // Mock GeminiIntentService::classify() trả về payload cho trước (không gọi API thật).
    private function mockGeminiReturns(?array $payload): void
    {
        $this->mock(GeminiIntentService::class, function ($mock) use ($payload) {
            $mock->shouldReceive('classify')->andReturn($payload);
        });
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

    // ---------------------------------------------------------------------------------------------
    // Validate ở controller (không phụ thuộc Gemini)
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
        // Nút bấm intent tĩnh không gọi Gemini -> kiểm tra thuần middleware throttle.
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/quick-chat/ask', ['intent' => 'opening_hours'])->assertOk();
        }

        $this->postJson('/quick-chat/ask', ['intent' => 'opening_hours'])->assertStatus(429);
    }

    // ---------------------------------------------------------------------------------------------
    // Nút gợi ý chủ đề (askByIntent) — không qua Gemini
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
    // Luồng Gemini (mock classify, không gọi API thật)
    // ---------------------------------------------------------------------------------------------

    public function test_every_free_text_question_calls_gemini(): void
    {
        $this->mock(GeminiIntentService::class, function ($mock) {
            $mock->shouldReceive('classify')->once()->andReturn($this->geminiPayload(['intent' => 'out_of_scope']));
        });

        $this->postJson('/quick-chat/ask', ['question' => 'câu hỏi bất kỳ'])->assertOk();
    }

    public function test_gemini_product_search_queries_real_database(): void
    {
        $this->seedCatalog();
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'product_search',
            'product_query' => 'trà đào',
            'confidence' => 0.95,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'có trà đào không']);

        $response->assertOk()->assertJson(['intent' => 'product_search']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertTrue($names->contains('Trà đào cam sả'));
        // Sản phẩm trả về phải là dữ liệu THẬT trong DB (chống Gemini bịa sản phẩm không tồn tại).
        $this->assertTrue(Product::where('slug', 'tra-dao-cam-sa')->exists());
    }

    public function test_gemini_product_search_respects_exclusion(): void
    {
        $this->seedCatalog();
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'product_search',
            'exclusions' => ['no_coffee'],
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món gì không có cà phê']);

        $response->assertOk()->assertJson(['intent' => 'product_search']);
        $names = collect($response->json('items'))->pluck('name');
        $this->assertFalse($names->contains('Cà phê đen đá'));
        $this->assertFalse($names->contains('Bạc xỉu'));
    }

    public function test_gemini_product_search_respects_max_price(): void
    {
        $this->seedCatalog();
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'product_price',
            'max_price' => 30000,
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món nào dưới 30k']);

        $response->assertOk();
        $names = collect($response->json('items'))->pluck('name');
        // 25.000 và 28.000 và 30.000 đạt; 32.000 và 35.000 bị loại.
        $this->assertTrue($names->contains('Cà phê đen đá'));
        $this->assertFalse($names->contains('Trà sữa trân châu'));
    }

    public function test_gemini_cheapest_sorts_by_price_ascending(): void
    {
        $this->seedCatalog();
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'cheapest_product',
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'món nào rẻ nhất']);

        $response->assertOk();
        $names = collect($response->json('items'))->pluck('name');
        $this->assertEquals('Cà phê đen đá', $names->first()); // 25.000đ, thấp nhất
    }

    public function test_gemini_maps_to_existing_static_intent(): void
    {
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'momo',
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'trả bằng ví điện tử được không']);

        $momoIntent = collect(config('quick_chat.intents'))->firstWhere('id', 'momo');
        $response->assertOk()->assertJson([
            'intent' => 'momo',
            'answer' => $momoIntent['answer'],
        ]);
    }

    public function test_gemini_promotion_intent_returns_only_valid_online_promotions(): void
    {
        Promotion::create(['code' => 'ONLINE10', 'type' => 'percent', 'value' => 10, 'is_active' => true, 'applies_to' => 'delivery']);
        Promotion::create(['code' => 'POSONLY', 'type' => 'percent', 'value' => 20, 'is_active' => true, 'applies_to' => 'pickup']);
        Promotion::create(['code' => 'EXPIRED', 'type' => 'fixed', 'value' => 5000, 'is_active' => true, 'applies_to' => 'all', 'end_at' => now()->subDay()]);

        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'promotion_list',
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'đang có ưu đãi gì']);

        // intent phản ánh phân loại của Gemini ('promotion_list'), map sang handler 'promotion'.
        $response->assertOk()->assertJson(['intent' => 'promotion_list']);
        $codes = collect($response->json('items'))->pluck('code');
        $this->assertTrue($codes->contains('ONLINE10'));
        $this->assertFalse($codes->contains('POSONLY'));
        $this->assertFalse($codes->contains('EXPIRED'));
    }

    // ---------------------------------------------------------------------------------------------
    // Fallback / lỗi
    // ---------------------------------------------------------------------------------------------

    public function test_out_of_scope_returns_fallback_with_suggestions(): void
    {
        $this->mockGeminiReturns($this->geminiPayload(['intent' => 'out_of_scope', 'confidence' => 0.99]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'viết code php cho tôi']);

        $response->assertOk()->assertJson([
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
        ]);
        $this->assertNotEmpty($response->json('suggestions'));
    }

    public function test_low_confidence_returns_fallback(): void
    {
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'product_search',
            'product_query' => 'trà đào',
            'confidence' => 0.4,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'gì đó']);

        $response->assertOk()->assertJson([
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
        ]);
    }

    // Gemini KHÔNG gọi được (classify trả null: tắt/lỗi/timeout/quota/JSON hỏng) -> báo thẳng lỗi,
    // KHÔNG âm thầm trả câu khác. Khách không bao giờ thấy lỗi kỹ thuật thô.
    public function test_gemini_unavailable_returns_error_message(): void
    {
        $this->mockGeminiReturns(null);

        $response = $this->postJson('/quick-chat/ask', ['question' => 'có trà đào không']);

        $response->assertOk()->assertJson(['intent' => null]);
        $this->assertStringContainsString('chưa phản hồi được', $response->json('answer'));
        $this->assertEmpty($response->json('items'));
    }

    public function test_non_whitelisted_gemini_intent_falls_back(): void
    {
        // Intent không nằm trong product-intents cũng không map được -> handleGeminiIntent trả null.
        $this->mockGeminiReturns($this->geminiPayload([
            'intent' => 'loyalty_points',
            'confidence' => 0.9,
        ]));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'tôi có bao nhiêu điểm']);

        $response->assertOk()->assertJson([
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
        ]);
    }

    public function test_gemini_api_key_is_never_exposed_in_response(): void
    {
        config(['services.gemini.api_key' => 'super-secret-key-xyz']);
        $this->mockGeminiReturns($this->geminiPayload(['intent' => 'out_of_scope']));

        $response = $this->postJson('/quick-chat/ask', ['question' => 'câu hỏi gì đó']);

        $response->assertOk();
        $this->assertStringNotContainsString('super-secret-key-xyz', $response->getContent());
    }
}
