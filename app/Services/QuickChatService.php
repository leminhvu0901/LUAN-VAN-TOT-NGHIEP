<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Xử lý câu hỏi tự do của chatbox bằng Gemini AI.
// Luồng: câu hỏi -> Gemini CHỈ phân loại thành JSON (intent + tham số) -> Laravel validate rồi tự
// truy vấn dữ liệu THẬT (Product/Category/Promotion/Setting) và dựng câu trả lời. Gemini không bao
// giờ tự bịa sản phẩm/giá/khuyến mãi/chính sách. Không ghi dữ liệu (chỉ get()/first()), không đọc
// user_id từ request, không log nội dung câu hỏi.
class QuickChatService
{
    // GeminiIntentService có thể được inject qua constructor (test bind mock) hoặc tự resolve qua
    // container khi cần (xem tryGemini) — tham số để null vì Laravel bỏ qua tham số optional khi tự
    // resolve QuickChatService.
    public function __construct(private ?GeminiIntentService $gemini = null)
    {
    }

    // Trả lời theo câu hỏi tự do đã gõ — chỉ dùng Gemini.
    public function ask(string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            return $this->plainResponse('Vui lòng nhập câu hỏi.');
        }

        // Gemini phân loại -> Laravel truy vấn dữ liệu thật và dựng câu trả lời.
        if ($handled = $this->tryGemini($question, $parsed)) {
            return $handled;
        }

        // Gemini KHÔNG gọi được (tắt/thiếu key/lỗi mạng/quá tải/hết quota/JSON hỏng) -> báo thẳng để
        // biết đang hỏng, không im lặng.
        if ($parsed === null) {
            return $this->plainResponse('Xin lỗi, hệ thống hỗ trợ hiện chưa phản hồi được (lỗi kết nối hoặc quá tải). Bạn vui lòng thử lại sau ít phút nhé!');
        }

        // Gemini phản hồi bình thường nhưng câu hỏi ngoài phạm vi / độ tin cậy thấp / chưa có handler
        // tương ứng -> câu fallback chuẩn kèm nút gợi ý các chủ đề chính.
        return [
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
            'items' => [],
            'action_url' => null,
            'suggestions' => $this->fallbackSuggestions(),
        ];
    }

    // Gọi Gemini để phân loại câu hỏi (JSON). Trả về null ở BẤT KỲ bước nào không dùng được (không
    // gọi được, JSON không hợp lệ, confidence thấp, intent ngoài phạm vi, không map được handler).
    // $parsed (tham chiếu) cho biết Gemini CÓ phản hồi hợp lệ hay không -> giúp ask() phân biệt
    // "Gemini hỏng/không gọi được" (null) với "Gemini hiểu nhưng câu hỏi ngoài phạm vi" (có mảng).
    private function tryGemini(string $question, ?array &$parsed = null): ?array
    {
        // Lấy qua container nếu constructor không được inject — Laravel bỏ qua tham số optional khi
        // tự resolve service. Cách này vẫn nhận đúng mock đã bind trong test.
        $this->gemini ??= app(GeminiIntentService::class);

        $parsed = $this->gemini->classify($question);
        if ($parsed === null) {
            return null;
        }

        $threshold = (float) config('quick_chat.gemini_confidence_threshold', 0.75);
        if ($parsed['confidence'] < $threshold) {
            return null;
        }

        if (in_array($parsed['intent'], GeminiIntentService::NON_ACTIONABLE_INTENTS, true)) {
            return null;
        }

        $response = $this->handleGeminiIntent($parsed);
        if ($response === null) {
            return null;
        }

        $response['intent'] = $parsed['intent'];
        return $response;
    }

    // Ánh xạ intent đã được Gemini phân loại (và validate) sang câu trả lời thật. Chỉ có 2 hướng:
    // (1) intent sản phẩm -> truy vấn có cấu trúc (productResponseFromStructured), (2) intent tĩnh đã
    // có sẵn -> findIntent()+buildResponseForIntent(). Không map được -> null.
    private function handleGeminiIntent(array $parsed): ?array
    {
        $productIntents = ['product_search', 'product_price', 'cheapest_product', 'best_seller', 'new_products', 'product_menu'];
        if (in_array($parsed['intent'], $productIntents, true)) {
            return $this->productResponseFromStructured($parsed);
        }

        $mappedId = config('quick_chat.gemini_intent_map.' . $parsed['intent']);
        if (!$mappedId) {
            return null;
        }

        $mappedIntent = $this->findIntent($mappedId);
        if (!$mappedIntent) {
            return null;
        }

        return $this->buildResponseForIntent($mappedIntent);
    }

    // Dựng câu trả lời sản phẩm từ JSON ĐÃ ĐƯỢC VALIDATE của Gemini (không phải text thô) — truy vấn
    // dữ liệu THẬT qua baseProductQuery/applyOrder/productResponse. preferences/exclusions chỉ được
    // phép là các key có thật trong config('quick_chat.product_needs') (đã validate ở GeminiIntentService).
    private function productResponseFromStructured(array $parsed): array
    {
        $excludeCategoryIds = [];
        $sugarGuidance = null;

        foreach ($parsed['exclusions'] as $needKey) {
            $need = config('quick_chat.product_needs.' . $needKey);
            if ($need && isset($need['excluded_categories'])) {
                $excludeCategoryIds = array_merge($excludeCategoryIds, $this->categoryIdsMatching($need['excluded_categories']));
            }
        }

        $categoryIds = [];
        if ($parsed['category'] !== '') {
            $categoryIds = $this->categoryIdsMatching([$parsed['category']]);
        }
        $preferenceSort = null;
        foreach ($parsed['preferences'] as $needKey) {
            $need = config('quick_chat.product_needs.' . $needKey);
            if (!$need) {
                continue;
            }
            if (isset($need['preferred_categories'])) {
                $categoryIds = array_merge($categoryIds, $this->categoryIdsMatching($need['preferred_categories']));
            }
            // Need mang 'sort' (rẻ/đắt/bán chạy) -> áp làm thứ tự sắp xếp, không lọc danh mục.
            if (isset($need['sort'])) {
                $preferenceSort = $need['sort'];
            }
            // Need chỉ mang 'answer' (vd less_sweet) -> không lọc sản phẩm, chỉ bổ sung hướng dẫn.
            if (isset($need['answer']) && !isset($need['preferred_categories']) && !isset($need['excluded_categories']) && !isset($need['sort'])) {
                $sugarGuidance = $need['answer'];
            }
        }
        $categoryIds = array_values(array_diff(array_unique($categoryIds), $excludeCategoryIds));

        $query = $this->baseProductQuery(array_values(array_unique($excludeCategoryIds)));

        if (!empty($categoryIds)) {
            $query->whereIn('products.category_id', $categoryIds);
        }
        if ($parsed['product_query'] !== '') {
            $query->where(DB::raw('LOWER(products.name)'), 'like', '%' . mb_strtolower($parsed['product_query'], 'UTF-8') . '%');
        }
        if ($parsed['max_price'] > 0) {
            $query->where('products.base_price', '<=', $parsed['max_price']);
        }
        if ($parsed['min_price'] > 0) {
            $query->where('products.base_price', '>=', $parsed['min_price']);
        }

        // "Sản phẩm mới" -> sắp theo thời gian tạo thật (KHÔNG có cột "is_new" -> không bịa, chỉ dùng
        // đúng dữ liệu created_at thật + câu chữ trung thực "cập nhật gần đây").
        if ($parsed['intent'] === 'new_products') {
            $products = $query->orderByDesc('products.created_at')->limit(4)->get();
            return $this->productResponse($products, 'Đây là một số sản phẩm được cập nhật gần đây:', $sugarGuidance);
        }

        // Ưu tiên sắp xếp theo intent ('cheapest_product'), sau đó tới sắp xếp suy ra từ preferences
        // ('cheap' -> price_asc, 'expensive' -> price_desc, 'popular' -> bán chạy).
        $sortMode = $parsed['intent'] === 'cheapest_product' ? 'price_asc' : $preferenceSort;
        $products = $this->applyOrder($query, $sortMode)->limit(4)->get();

        $answer = $this->findIntent('product')['answer'] ?? 'Đây là một số sản phẩm bạn có thể quan tâm:';
        return $this->productResponse($products, $answer, $sugarGuidance);
    }

    // Trả lời trực tiếp theo 1 intent đã biết id — dùng khi khách bấm nút gợi ý (không qua Gemini).
    public function askByIntent(string $intentId): array
    {
        $intent = $this->findIntent($intentId);
        if (!$intent) {
            return [
                'intent' => null,
                'answer' => config('quick_chat.fallback_freeform'),
                'items' => [],
                'action_url' => null,
                'suggestions' => $this->fallbackSuggestions(),
            ];
        }

        return $this->buildResponseForIntent($intent);
    }

    private function plainResponse(string $answer): array
    {
        return [
            'intent' => null,
            'answer' => $answer,
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    private function findIntent(string $id): ?array
    {
        foreach (config('quick_chat.intents', []) as $intent) {
            if ($intent['id'] === $id) {
                return $intent;
            }
        }
        return null;
    }

    // Nút gợi ý hiển thị SAU câu fallback — mỗi nút gửi lại đúng intent đã biết qua askByIntent().
    private function fallbackSuggestions(): array
    {
        return array_map(
            fn($s) => ['intent_id' => $s['intent_id'], 'topic_key' => null, 'question' => null, 'label' => $s['label']],
            config('quick_chat.fallback_suggestions', [])
        );
    }

    private function buildResponseForIntent(array $intent): array
    {
        $response = match ($intent['handler']) {
            'product' => $this->handleProductListing($intent),
            'promotion' => $this->handlePromotion($intent),
            'order_tracking' => $this->handleOrderTracking(),
            'opening_hours' => $this->handleOpeningHours(),
            'contact' => $this->handleContact(),
            default => $this->handleStatic($intent),
        };

        $response['intent'] = $intent['id'];

        // Câu hỏi "payment" chung chung: kèm gợi ý bấm sâu hơn (MoMo/COD) bên cạnh câu trả lời tổng quan.
        if (!empty($intent['suggest_intents'])) {
            $extra = [];
            foreach ($intent['suggest_intents'] as $subId) {
                $sub = $this->findIntent($subId);
                if ($sub) {
                    $extra[] = ['intent_id' => $sub['id'], 'topic_key' => null, 'label' => $sub['label']];
                }
            }
            $response['suggestions'] = array_merge($response['suggestions'] ?? [], $extra);
        }

        return $response;
    }

    private function handleStatic(array $intent): array
    {
        return [
            'answer' => $intent['answer'],
            'items' => [],
            'action_url' => $intent['action_route'] ? route($intent['action_route']) : null,
            'suggestions' => [],
        ];
    }

    // Nút chủ đề "Sản phẩm"/"Giá bán" (không kèm câu hỏi cụ thể) -> liệt kê các món bán chạy nhất.
    private function handleProductListing(array $intent): array
    {
        $products = $this->applyOrder($this->baseProductQuery(), null)->limit(4)->get();

        return $this->productResponse($products, $intent['answer'], null);
    }

    // Câu truy vấn sản phẩm cơ bản (đang bán + tổng số đã bán), có thể loại 1 số danh mục.
    private function baseProductQuery(array $excludeCategoryIds = [])
    {
        $query = Product::query()
            ->select('products.*', DB::raw('COALESCE(o.total_sold, 0) as total_sold'))
            ->leftJoin(
                DB::raw("(SELECT oi.product_id, SUM(oi.quantity) as total_sold
                          FROM order_items oi
                          JOIN orders o ON o.id = oi.order_id
                          WHERE o.status = 'completed' AND o.payment_status = 'paid' AND o.deleted_at IS NULL
                          GROUP BY oi.product_id) as o"),
                'products.id',
                '=',
                'o.product_id'
            )
            ->where('products.is_active', 1);

        if (!empty($excludeCategoryIds)) {
            $query->whereNotIn('products.category_id', $excludeCategoryIds);
        }

        return $query;
    }

    // Áp ORDER BY theo 'sort' ('price_asc'|'price_desc'), mặc định sắp theo bán chạy nhất.
    private function applyOrder($query, ?string $sort)
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('products.base_price'),
            'price_desc' => $query->orderByDesc('products.base_price'),
            default => $query->orderByDesc('total_sold'),
        };
    }

    private function buildProductItems($products): array
    {
        return $products->map(fn($p) => [
            'name' => $p->name,
            'type' => 'product',
            'price' => number_format($p->base_price, 0, ',', '.') . 'đ',
            'image_url' => $p->image_url,
            'url' => route('product.show', $p->slug),
        ])->values()->all();
    }

    // Ghép câu dẫn + (tùy chọn) hướng dẫn mức đường; nếu không có sản phẩm -> empty-state trung thực.
    private function productResponse($products, ?string $answer, ?string $sugarGuidance): array
    {
        if ($products->isEmpty()) {
            return $this->honestNoMatch($sugarGuidance);
        }

        if ($sugarGuidance !== null) {
            $answer = trim(($answer ?? '') . ' ' . $sugarGuidance);
        }

        return [
            'answer' => $answer,
            'items' => $this->buildProductItems($products),
            'action_url' => route('products'),
            'suggestions' => [],
        ];
    }

    private function honestNoMatch(?string $sugarGuidance): array
    {
        $answer = config('quick_chat.product_no_match_answer');
        if ($sugarGuidance !== null) {
            $answer = trim($answer . ' ' . $sugarGuidance);
        }

        return [
            'answer' => $answer,
            'items' => [],
            'action_url' => route('products'),
            'suggestions' => $this->categorySuggestions(),
        ];
    }

    // Nút gợi ý = các danh mục thật (bấm vào sẽ hỏi lại bằng tên danh mục).
    private function categorySuggestions(): array
    {
        return Category::where('is_active', 1)->orderBy('display_order')->pluck('name')
            ->map(fn($name) => ['intent_id' => null, 'topic_key' => null, 'question' => $name, 'label' => $name])
            ->values()->all();
    }

    // Tìm ID các danh mục (đang bật) có tên chứa 1 trong các chuỗi cần khớp (accent-insensitive).
    private function categoryIdsMatching(array $matches): array
    {
        if (empty($matches)) {
            return [];
        }

        $ids = [];
        foreach (Category::where('is_active', 1)->get(['id', 'name']) as $cat) {
            $nameLower = mb_strtolower($cat->name, 'UTF-8');
            $nameAscii = \Illuminate\Support\Str::ascii($nameLower);
            foreach ($matches as $m) {
                $mLower = mb_strtolower($m, 'UTF-8');
                if (str_contains($nameLower, $mLower) || str_contains($nameAscii, \Illuminate\Support\Str::ascii($mLower))) {
                    $ids[] = $cat->id;
                    break;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function handlePromotion(array $intent): array
    {
        $now = now();
        $promotions = Promotion::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->whereIn('applies_to', ['all', 'delivery'])
            ->limit(4)
            ->get();

        return [
            'answer' => $intent['answer'],
            'items' => $promotions->map(fn($promo) => [
                'type' => 'promotion',
                'code' => $promo->code ?: ('Ưu đãi #' . $promo->id),
                'value' => $promo->type === 'percent'
                    ? 'Giảm ' . rtrim(rtrim(number_format($promo->value, 2, '.', ''), '0'), '.') . '%'
                    : 'Giảm ' . number_format($promo->value, 0, ',', '.') . 'đ',
                'min_order_amount' => $promo->min_order_amount ? number_format($promo->min_order_amount, 0, ',', '.') . 'đ' : null,
                'min_quantity' => $promo->min_quantity,
                'end_at' => $promo->end_at ? \Carbon\Carbon::parse($promo->end_at)->format('d/m/Y') : null,
            ])->values()->all(),
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    private function handleOrderTracking(): array
    {
        if (Auth::check()) {
            return [
                'answer' => config('quick_chat.answers.order_tracking_auth'),
                'items' => [],
                'action_url' => route('orders'),
                'suggestions' => [],
            ];
        }

        return [
            'answer' => config('quick_chat.answers.order_tracking_guest'),
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    private function handleOpeningHours(): array
    {
        $open = Setting::getValue('store_open_time', config('quick_chat.defaults.open_time'));
        $close = Setting::getValue('store_close_time', config('quick_chat.defaults.close_time'));

        return [
            'answer' => sprintf(
                'Cửa hàng mở cửa hàng ngày từ %s đến %s.',
                \Carbon\Carbon::parse($open)->format('H:i'),
                \Carbon\Carbon::parse($close)->format('H:i')
            ),
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    private function handleContact(): array
    {
        $phone = Setting::getValue('store_phone', config('quick_chat.defaults.phone'));
        $email = Setting::getValue('store_email', config('quick_chat.defaults.email'));
        $address = Setting::getValue('store_address', config('quick_chat.defaults.address'));

        $parts = [];
        if ($address) {
            $parts[] = $address;
        }
        if ($phone) {
            $parts[] = 'Điện thoại: ' . $phone;
        }
        if ($email) {
            $parts[] = 'Email: ' . $email;
        }

        return [
            'answer' => $parts ? implode(' — ', $parts) : 'Vui lòng xem thông tin liên hệ tại chân trang website.',
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }
}
