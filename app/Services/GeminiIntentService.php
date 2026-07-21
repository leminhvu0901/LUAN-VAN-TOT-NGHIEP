<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Lớp DUY NHẤT gọi Gemini API để PHÂN LOẠI câu hỏi tự do của chatbox thành JSON có cấu trúc.
// KHÔNG tự truy vấn Product/Promotion/Order, KHÔNG chứa logic view/UI, KHÔNG tự viết câu trả lời
// cho khách. Mọi lỗi (disabled/thiếu key/timeout/HTTP lỗi/JSON hỏng/validate fail) đều trả về null
// một cách im lặng — nơi gọi (QuickChatService) tự quyết định rơi về fallback rule-based.
class GeminiIntentService
{
    // Intent có handler thật trong QuickChatService (product qua truy vấn có cấu trúc, hoặc map
    // thẳng sang 1 intent tĩnh qua config('quick_chat.gemini_intent_map')).
    public const ACTIONABLE_INTENTS = [
        'product_search', 'product_price', 'cheapest_product', 'best_seller', 'new_products', 'product_menu',
        'promotion_list', 'promotion_condition', 'payment', 'momo', 'cod', 'shipping',
        'order_tracking', 'opening_hours', 'contact', 'product_options',
    ];

    // Nhận diện được nhưng cố ý xử lý như fallback (không có nội dung để trả lời riêng).
    public const NON_ACTIONABLE_INTENTS = ['out_of_scope', 'ambiguous'];

    // Từ vựng "nhu cầu" đã có sẵn trong config('quick_chat.product_needs') — Gemini chỉ được chọn
    // trong danh sách này, không được tự bịa nhãn preference/exclusion mới.
    public const KNOWN_NEEDS = [
        'alertness', 'refreshing', 'milky', 'less_sweet', 'no_coffee', 'cheap', 'expensive', 'popular',
    ];

    // Trả về mảng đã validate (whitelist) hoặc null nếu không thể phân loại đáng tin cậy.
    public function classify(string $question): ?array
    {
        if (!config('services.gemini.enabled') || !config('services.gemini.api_key')) {
            return null;
        }

        $cacheKey = 'quickchat_gemini_' . md5(mb_strtolower(trim($question), 'UTF-8'));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->callApi($question);

        // Chỉ cache kết quả THÀNH CÔNG — lỗi/timeout không nên "kẹt" fallback trong 45 phút.
        if ($result !== null) {
            Cache::put($cacheKey, $result, now()->addMinutes(45));
        }

        return $result;
    }

    private function callApi(string $question): ?array
    {
        try {
            $endpoint = rtrim(config('services.gemini.endpoint'), '/') . '/' . config('services.gemini.model') . ':generateContent';

            $response = Http::timeout((int) config('services.gemini.timeout'))
                ->withHeaders([
                    'x-goog-api-key' => config('services.gemini.api_key'),
                    'Content-Type' => 'application/json',
                ])
                // Thử lại tối đa 2 lần cho các lỗi TẠM THỜI: mất kết nối/timeout, và HTTP 429 (hết
                // quota tức thời) / 503 (model quá tải) — free-tier Google khá hay gặp các lỗi này.
                ->retry(2, 500, function ($exception) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    $status = method_exists($exception, 'response') && $exception->response
                        ? $exception->response->status()
                        : null;
                    return in_array($status, [429, 503], true);
                }, false)
                ->post($endpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $this->systemPrompt() . "\n\nCâu hỏi khách hàng: " . $question]],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0,
                        'maxOutputTokens' => 300,
                        'responseMimeType' => 'application/json',
                        // Tắt "thinking" — các model Gemini mới mặc định dành phần lớn maxOutputTokens
                        // cho suy luận ẩn trước khi trả JSON, không cần thiết cho tác vụ phân loại đơn
                        // giản này (và có thể khiến response bị cắt cụt do hết token trước khi ra JSON).
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]);

            if (!$response->successful()) {
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            if (!is_string($text) || trim($text) === '') {
                return null;
            }

            $decoded = json_decode($text, true);
            if (!is_array($decoded)) {
                return null;
            }

            return $this->validatePayload($decoded);
        } catch (\Throwable $e) {
            // Không log câu hỏi, không log API key — chỉ log LOẠI lỗi để chẩn đoán.
            Log::warning('gemini_intent_failed', ['type' => get_class($e)]);
            return null;
        }
    }

    private function validatePayload(array $payload): ?array
    {
        $allowedIntents = array_merge(self::ACTIONABLE_INTENTS, self::NON_ACTIONABLE_INTENTS);

        $validator = Validator::make($payload, [
            'intent' => ['required', 'string', 'in:' . implode(',', $allowedIntents)],
            'product_query' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['string', 'in:' . implode(',', self::KNOWN_NEEDS)],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string', 'in:' . implode(',', self::KNOWN_NEEDS)],
            'confidence' => ['required', 'numeric', 'between:0,1'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        $validated = $validator->validated();

        return [
            'intent' => $validated['intent'],
            'product_query' => $validated['product_query'] ?? '',
            'category' => $validated['category'] ?? '',
            'max_price' => (int) ($validated['max_price'] ?? 0),
            'min_price' => (int) ($validated['min_price'] ?? 0),
            'preferences' => array_values($validated['preferences'] ?? []),
            'exclusions' => array_values($validated['exclusions'] ?? []),
            'confidence' => (float) $validated['confidence'],
        ];
    }

    // Chỉ dẫn Gemini PHÂN LOẠI, KHÔNG trả lời trực tiếp khách hàng, KHÔNG bịa dữ liệu Happy Tea.
    private function systemPrompt(): string
    {
        $intents = implode(', ', array_merge(self::ACTIONABLE_INTENTS, self::NON_ACTIONABLE_INTENTS));
        $needs = implode(', ', self::KNOWN_NEEDS);

        return <<<PROMPT
Bạn là bộ phân loại câu hỏi cho chatbox của quán trà sữa/cà phê Happy Tea. Nhiệm vụ DUY NHẤT của bạn
là đọc câu hỏi khách hàng và trả về ĐÚNG 1 đối tượng JSON theo schema bên dưới — KHÔNG trả lời câu hỏi,
KHÔNG viết văn bản giải thích, KHÔNG thêm markdown, CHỈ trả JSON thuần.

Schema bắt buộc:
{
  "intent": string (chỉ chọn 1 trong: {$intents}),
  "product_query": string (tên món khách hỏi, để rỗng nếu không có),
  "category": string (tên danh mục đồ uống khách hỏi, để rỗng nếu không có),
  "max_price": integer (giá tối đa dạng số, vd "30k"/"30.000"/"30 nghìn" => 30000; 0 nếu không có),
  "min_price": integer (giá tối thiểu dạng số; 0 nếu không có),
  "preferences": array các chuỗi (chỉ chọn trong: {$needs}; mảng rỗng nếu không có),
  "exclusions": array các chuỗi (chỉ chọn trong: {$needs}; mảng rỗng nếu không có),
  "confidence": number từ 0 đến 1 (mức tự tin của bạn vào kết quả phân loại)
}

Quy tắc bắt buộc:
- KHÔNG bịa tên sản phẩm, giá, khuyến mãi, hay chính sách cửa hàng — bạn chỉ phân loại, không có dữ
  liệu thật về Happy Tea.
- KHÔNG xác nhận trạng thái 1 đơn hàng/giao dịch MoMo cụ thể nào.
- Nếu câu hỏi không liên quan đến Happy Tea (vd yêu cầu viết code, hỏi kiến thức chung) -> intent =
  "out_of_scope".
- Nếu câu hỏi quá mơ hồ để phân loại -> intent = "ambiguous".
- Chỉ trả về JSON, không kèm bất kỳ ký tự nào khác.
PROMPT;
    }
}
