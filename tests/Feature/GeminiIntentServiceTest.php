<?php

namespace Tests\Feature;

use App\Services\GeminiIntentService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Test riêng cho GeminiIntentService — KHÔNG gọi API Gemini thật, luôn dùng Http::fake().
class GeminiIntentServiceTest extends TestCase
{
    private function enableGemini(): void
    {
        config([
            'services.gemini.enabled' => true,
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.5-flash-lite',
            'services.gemini.timeout' => 8,
        ]);
    }

    private function fakeGeminiJson(array $json, int $status = 200): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($json)]]]],
                ],
            ], $status),
        ]);
    }

    private function baseValidPayload(array $overrides = []): array
    {
        return array_merge([
            'intent' => 'product_search',
            'product_query' => '',
            'category' => '',
            'max_price' => 0,
            'min_price' => 0,
            'preferences' => [],
            'exclusions' => [],
            'confidence' => 0.9,
        ], $overrides);
    }

    public function test_disabled_returns_null_without_calling_api(): void
    {
        config(['services.gemini.enabled' => false]);
        Http::fake();

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_missing_api_key_returns_null_without_calling_api(): void
    {
        config(['services.gemini.enabled' => true, 'services.gemini.api_key' => null]);
        Http::fake();

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_parses_product_search_with_query(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload([
            'intent' => 'product_search',
            'product_query' => 'trà đào',
            'confidence' => 0.95,
        ]));

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertSame('product_search', $result['intent']);
        $this->assertSame('trà đào', $result['product_query']);
        $this->assertSame(0.95, $result['confidence']);
    }

    public function test_parses_price_threshold_as_integer(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload([
            'intent' => 'product_price',
            'max_price' => 30000,
        ]));

        $result = app(GeminiIntentService::class)->classify('Có món nào dưới 30k không?');

        $this->assertSame(30000, $result['max_price']);
    }

    public function test_parses_coffee_exclusion_preference(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload([
            'preferences' => ['alertness'],
            'exclusions' => ['no_coffee'],
        ]));

        $result = app(GeminiIntentService::class)->classify('Uống gì tỉnh táo nhưng không uống cà phê?');

        $this->assertSame(['alertness'], $result['preferences']);
        $this->assertSame(['no_coffee'], $result['exclusions']);
    }

    public function test_out_of_scope_question_is_parsed(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload([
            'intent' => 'out_of_scope',
            'confidence' => 0.99,
        ]));

        $result = app(GeminiIntentService::class)->classify('Viết code PHP cho tôi');

        $this->assertSame('out_of_scope', $result['intent']);
        $this->assertContains('out_of_scope', GeminiIntentService::NON_ACTIONABLE_INTENTS);
    }

    public function test_low_confidence_value_is_still_returned_for_caller_to_decide(): void
    {
        // Ngưỡng chấp nhận (0.75) được QuickChatService::tryGemini() áp dụng, KHÔNG phải ở đây —
        // GeminiIntentService chỉ có nhiệm vụ parse + validate cấu trúc JSON.
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload(['confidence' => 0.4]));

        $result = app(GeminiIntentService::class)->classify('uống gì đó');

        $this->assertSame(0.4, $result['confidence']);
    }

    // Ghi chú: KHÔNG dùng Http::fake(closure ném exception) ở đây — đã xác nhận riêng (ngoài
    // PHPUnit, tái hiện độc lập cả với \RuntimeException lẫn ConnectionException) rằng bản PHP của
    // môi trường này bị segmentation fault bất kỳ khi nào closure trong Http::fake() ném exception,
    // không liên quan gì đến code của GeminiIntentService. Thay vào đó, mô phỏng lỗi kết nối THẬT
    // (không qua fake, không gọi ra Internet) bằng cách trỏ endpoint về 1 cổng loopback không có gì
    // lắng nghe -> Guzzle tự ném ConnectionException thật, đi qua đúng nhánh catch(\Throwable) thật.
    public function test_connection_failure_returns_null(): void
    {
        $this->enableGemini();
        config(['services.gemini.endpoint' => 'http://127.0.0.1:1']);

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
    }

    public function test_http_429_returns_null(): void
    {
        $this->enableGemini();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
    }

    public function test_http_500_returns_null(): void
    {
        $this->enableGemini();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'server error'], 500)]);

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
    }

    public function test_malformed_json_returns_null(): void
    {
        $this->enableGemini();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'not-valid-json{{{']]]],
                ],
            ], 200),
        ]);

        $result = app(GeminiIntentService::class)->classify('Có trà đào không?');

        $this->assertNull($result);
    }

    public function test_non_whitelisted_intent_is_rejected(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload(['intent' => 'delete_order']));

        $result = app(GeminiIntentService::class)->classify('xóa đơn giúp tôi');

        $this->assertNull($result);
    }

    public function test_non_whitelisted_preference_value_is_rejected(): void
    {
        $this->enableGemini();
        $this->fakeGeminiJson($this->baseValidPayload(['preferences' => ['made_up_preference']]));

        $result = app(GeminiIntentService::class)->classify('uống gì đặc biệt');

        $this->assertNull($result);
    }

    public function test_api_key_never_appears_in_logs_on_connection_failure(): void
    {
        $this->enableGemini();
        config([
            'services.gemini.api_key' => 'super-secret-key-xyz',
            'services.gemini.endpoint' => 'http://127.0.0.1:1',
        ]);
        \Illuminate\Support\Facades\Log::spy();

        app(GeminiIntentService::class)->classify('Có trà đào không?');

        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) {
            return !str_contains($message . json_encode($context), 'super-secret-key-xyz');
        });
    }
}
