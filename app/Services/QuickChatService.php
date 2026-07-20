<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Nhận diện + trả lời câu hỏi tự do của chatbox (rule-based keyword/phrase, KHÔNG AI).
// Không ghi dữ liệu (chỉ get()/first()), không đọc user_id từ request, không log nội dung câu hỏi.
class QuickChatService
{
    // Nếu khoảng cách điểm giữa intent cao nhất và intent nhì < mức này -> coi là mập mờ, không đoán.
    private const AMBIGUITY_GAP = 2;

    // Trả lời theo câu hỏi tự do đã gõ.
    public function ask(string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            return [
                'intent' => null,
                'answer' => 'Vui lòng nhập câu hỏi.',
                'items' => [],
                'action_url' => null,
                'suggestions' => [],
            ];
        }

        $normalized = $this->normalize($question);
        $unaccented = Str::ascii($normalized);
        $words = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        // Câu hỏi chỉ 1 từ (vd "giá", "ship", "đơn") -> quá mơ hồ, luôn gợi ý thay vì đoán.
        if (count($words) <= 1) {
            return $this->clarifyResponse($this->mainMenuSuggestions());
        }

        $scores = $this->scoreIntents($normalized, $unaccented);

        // Câu hỏi nêu "nhu cầu" (tỉnh táo, không cà phê, thanh mát...) hoặc ý "ít ngọt" -> vẫn là câu
        // hỏi về sản phẩm/đồ uống, đảm bảo định tuyến vào handler product để áp dụng ánh xạ nhu cầu.
        if ($this->hasProductSignal($normalized, $unaccented)) {
            $scores['product'] = max($scores['product'] ?? 0, 3);
        }

        arsort($scores);
        $scoredIntents = array_filter($scores, fn($s) => $s > 0);

        if (empty($scoredIntents)) {
            // Không khớp intent nào -> trả lời fallback + nút gợi ý 6 chủ đề chính (không kèm sản
            // phẩm/bán chạy để tránh khách hiểu nhầm là chatbot đã hiểu đúng câu hỏi).
            return [
                'intent' => null,
                'answer' => config('quick_chat.fallback_freeform'),
                'items' => [],
                'action_url' => null,
                'suggestions' => $this->fallbackSuggestions(),
            ];
        }

        $ids = array_keys($scoredIntents);
        $topScore = $scoredIntents[$ids[0]];
        $secondId = $ids[1] ?? null;
        $secondScore = $secondId !== null ? $scoredIntents[$secondId] : 0;

        if (count($ids) > 1 && ($topScore - $secondScore) < self::AMBIGUITY_GAP) {
            // Mập mờ: gom các intent đang "hòa" (trong khoảng cách nhỏ hơn ngưỡng so với intent cao nhất).
            $tiedIds = array_filter($ids, fn($id) => ($topScore - $scoredIntents[$id]) < self::AMBIGUITY_GAP);
            $tiedIntents = array_values(array_filter(
                array_map(fn($id) => $this->findIntent($id), $tiedIds)
            ));
            usort($tiedIntents, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));

            return $this->clarifyResponse(array_map(
                fn($intent) => ['intent_id' => $intent['id'], 'topic_key' => null, 'label' => $intent['label']],
                array_slice($tiedIntents, 0, 4)
            ));
        }

        $intent = $this->findIntent($ids[0]);
        if (!$intent) {
            return [
                'intent' => null,
                'answer' => config('quick_chat.fallback_freeform'),
                'items' => [],
                'action_url' => null,
                'suggestions' => $this->mainMenuSuggestions(),
            ];
        }

        return $this->buildResponseForIntent($intent, $normalized);
    }

    // Trả lời trực tiếp theo 1 intent đã biết id — dùng khi khách bấm nút gợi ý (bỏ qua chấm điểm).
    public function askByIntent(string $intentId): array
    {
        $intent = $this->findIntent($intentId);
        if (!$intent) {
            return [
                'intent' => null,
                'answer' => config('quick_chat.fallback_freeform'),
                'items' => [],
                'action_url' => null,
                'suggestions' => $this->mainMenuSuggestions(),
            ];
        }

        return $this->buildResponseForIntent($intent, '');
    }

    private function normalize(string $question): string
    {
        $question = mb_substr($question, 0, 300, 'UTF-8');
        if (class_exists('Normalizer')) {
            $question = \Normalizer::normalize($question, \Normalizer::FORM_C);
        }
        $question = mb_strtolower($question, 'UTF-8');
        // Bỏ các dấu câu phổ biến, giữ nguyên chữ cái/số/khoảng trắng (kể cả tiếng Việt có dấu).
        $question = preg_replace('/[?!.,;:"\'()\[\]]/u', ' ', $question);
        $question = preg_replace('/\s+/u', ' ', $question);
        return trim($question);
    }

    // Chấm điểm từng intent trên cả bản có dấu và không dấu, cộng dồn (không tính trùng 2 lần
    // nếu 1 từ khóa vừa khớp bản có dấu vừa khớp bản không dấu của chính nó).
    private function scoreIntents(string $normalized, string $unaccented): array
    {
        $paddedAccented = ' ' . $normalized . ' ';
        $paddedUnaccented = ' ' . $unaccented . ' ';
        $scores = [];

        foreach (config('quick_chat.intents', []) as $intent) {
            $score = 0;

            foreach ($intent['keywords'] ?? [] as $keyword) {
                if ($this->containsPhrase($paddedAccented, $paddedUnaccented, $keyword)) {
                    $score += 2;
                }
            }

            foreach ($intent['phrases'] ?? [] as $phrase) {
                if ($this->containsPhrase($paddedAccented, $paddedUnaccented, $phrase)) {
                    $score += 3;
                }
            }

            $scores[$intent['id']] = $score;
        }

        return $scores;
    }

    private function containsPhrase(string $paddedAccented, string $paddedUnaccented, string $needle): bool
    {
        $needleAccented = ' ' . mb_strtolower($needle, 'UTF-8') . ' ';
        $needleUnaccented = ' ' . Str::ascii(mb_strtolower($needle, 'UTF-8')) . ' ';

        return str_contains($paddedAccented, $needleAccented) || str_contains($paddedUnaccented, $needleUnaccented);
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

    private function mainMenuSuggestions(): array
    {
        return array_map(
            fn($item) => ['intent_id' => null, 'topic_key' => $item['key'], 'label' => $item['label']],
            config('quick_chat.menu', [])
        );
    }

    // Nút gợi ý hiển thị SAU câu fallback khi không nhận diện được câu hỏi — mỗi nút gửi lại đúng
    // intent đã biết (bỏ qua chấm điểm) qua askByIntent(), không kèm sản phẩm/bán chạy.
    private function fallbackSuggestions(): array
    {
        return array_map(
            fn($s) => ['intent_id' => $s['intent_id'], 'topic_key' => null, 'question' => null, 'label' => $s['label']],
            config('quick_chat.fallback_suggestions', [])
        );
    }

    private function clarifyResponse(array $suggestions): array
    {
        return [
            'intent' => null,
            'answer' => config('quick_chat.clarify_prompt'),
            'items' => [],
            'action_url' => null,
            'suggestions' => $suggestions,
        ];
    }

    private function buildResponseForIntent(array $intent, string $normalized): array
    {
        $response = match ($intent['handler']) {
            'product' => $this->handleProduct($intent, $normalized),
            'promotion' => $this->handlePromotion($intent),
            'order_tracking' => $this->handleOrderTracking($intent),
            'opening_hours' => $this->handleOpeningHours($intent),
            'contact' => $this->handleContact($intent),
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

    // Dùng chung cho product & product_price. Thứ tự ưu tiên nhận diện đúng theo đặc tả:
    // 1) tên sản phẩm cụ thể  2) tên danh mục cụ thể  3) điều kiện loại trừ ("không cà phê")
    // 4) nhu cầu (tỉnh táo/giải khát/béo — khớp theo danh mục ánh xạ HOẶC mô tả sản phẩm thật)
    // 5) giá rẻ/đắt  6) món bán chạy (khi có tín hiệu rõ ràng)  7) từ khóa chung rời rạc (lưới an
    // toàn cuối)  8) mơ hồ hoàn toàn. Kèm: hướng dẫn mức đường (ít ngọt), kết hợp nhiều điều kiện,
    // và empty-state trung thực. Không khẳng định tác dụng y tế.
    private function handleProduct(array $intent, string $normalized): array
    {
        $unaccented = Str::ascii($normalized);

        // Điều kiện loại trừ ("không cà phê") — tính TRƯỚC nhu cầu vì "cà phê" cũng là tín hiệu dương
        // của chính need đó; nếu không loại trước, câu "không cà phê" sẽ bị lọc NHẦM về cà phê.
        $excludeNeed = $this->findNeed($normalized, $unaccented, fn($n) => isset($n['excluded_categories']));
        $excludeCategoryIds = $excludeNeed ? $this->categoryIdsMatching($excludeNeed['excluded_categories']) : [];
        $excludeTokens = $excludeNeed ? $this->tokensFromCategoryMatches($excludeNeed['excluded_categories']) : [];

        // Hướng dẫn mức đường (không tự lọc sai sản phẩm, chỉ bổ sung hướng dẫn) — need chỉ có 'answer'.
        $guidanceNeed = $this->findNeed($normalized, $unaccented, fn($n) =>
            isset($n['answer']) && !isset($n['preferred_categories']) && !isset($n['excluded_categories']) && !isset($n['sort'])
        );
        $sugarGuidance = $guidanceNeed['answer'] ?? null;

        // Sắp xếp (giá rẻ/đắt nhất, hoặc bán chạy khi có tín hiệu rõ ràng như "ngon"/"bán chạy") — đây
        // là yêu cầu SẮP XẾP, áp dụng làm ORDER BY cho bất kỳ bước nào bên dưới tìm được sản phẩm.
        $sortNeed = $this->findNeed($normalized, $unaccented, fn($n) => isset($n['sort']));
        $sortMode = $sortNeed['sort'] ?? null;

        // (1) Tên SẢN PHẨM cụ thể — ưu tiên cao nhất, khớp nguyên cụm tên thật trong câu hỏi.
        $productIds = $this->extractExactProductMatchIds($normalized, $unaccented);
        if (!empty($productIds)) {
            $products = $this->applyOrder($this->baseProductQuery($excludeCategoryIds)
                ->whereIn('products.id', $productIds), $sortMode)->limit(4)->get();

            return $this->productResponse($products, $intent['answer'], $sugarGuidance);
        }

        // (2) Tên DANH MỤC cụ thể — khớp nguyên cụm tên danh mục thật (vd "trà sữa"). KHÔNG rơi xuống
        //     so khớp rời rạc từng từ nữa nếu đã khớp được nguyên cụm (tránh lẫn "cà phê sữa đá").
        $categoryIds = array_values(array_diff($this->extractExactCategoryMatchIds($normalized, $unaccented), $excludeCategoryIds));
        if (!empty($categoryIds)) {
            $products = $this->applyOrder($this->baseProductQuery($excludeCategoryIds)
                ->whereIn('products.category_id', $categoryIds), $sortMode)->limit(4)->get();

            return $this->productResponse($products, $intent['answer'], $sugarGuidance);
        }

        // (4) Nhu cầu ưu tiên (tỉnh táo -> cà phê, giải khát -> trà trái cây, béo -> trà sữa...). Khớp
        //     THEO DANH MỤC ánh xạ HOẶC theo MÔ TẢ sản phẩm thật (description_keywords) — mở rộng kết
        //     quả ra ngoài đúng 1 danh mục cứng (vd "béo ngậy" xuất hiện ở cả Sữa chua, Đồ uống khác).
        $preferNeed = $this->findNeed($normalized, $unaccented, fn($n) => isset($n['preferred_categories']));
        if ($preferNeed) {
            $needCategoryIds = array_values(array_diff(
                $this->categoryIdsMatching($preferNeed['preferred_categories']),
                $excludeCategoryIds
            ));

            // Danh mục CHÍNH của nhu cầu này đã bị loại hoàn toàn -> mâu thuẫn thật sự
            // (vd "tỉnh táo nhưng không uống cà phê") -> không đoán mò bằng mô tả, trả lời trung thực.
            if (empty($needCategoryIds)) {
                return $this->honestNoMatch($sugarGuidance);
            }

            $descKeywords = $preferNeed['description_keywords'] ?? [];
            $products = $this->applyOrder($this->baseProductQuery($excludeCategoryIds)
                ->where(function ($q) use ($needCategoryIds, $descKeywords) {
                    $q->whereIn('products.category_id', $needCategoryIds);
                    foreach ($descKeywords as $kw) {
                        $like = '%' . mb_strtolower($kw, 'UTF-8') . '%';
                        $q->orWhere(DB::raw('LOWER(COALESCE(products.description, \'\'))'), 'like', $like);
                    }
                }), $sortMode)->limit(4)->get();

            return $this->productResponse($products, $preferNeed['intro'], $sugarGuidance);
        }

        // (7) Từ khóa chung rời rạc (lưới an toàn cuối cùng — chỉ dùng khi các bước trên không khớp gì).
        $keywords = array_values(array_diff($this->extractProductKeywords($normalized), $excludeTokens));
        if (!empty($keywords)) {
            $products = $this->applyOrder($this->baseProductQuery($excludeCategoryIds)
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $like = '%' . $kw . '%';
                        $q->orWhere(DB::raw('LOWER(products.name)'), 'like', $like)
                            ->orWhere(DB::raw('LOWER(categories.name)'), 'like', $like);
                    }
                }), $sortMode)->limit(4)->get();

            return $this->productResponse($products, $intent['answer'], $sugarGuidance);
        }

        // (5)/(6) Chỉ có tín hiệu SẮP XẾP (giá rẻ/đắt, hoặc "ngon"/"bán chạy"), không kèm danh mục/nhu
        // cầu nào ("món nào rẻ nhất?") — đây là câu hỏi RÕ RÀNG về toàn bộ menu, không phải mơ hồ.
        if ($sortNeed) {
            $products = $this->applyOrder($this->baseProductQuery($excludeCategoryIds), $sortMode)->limit(4)->get();

            return $this->productResponse($products, $sortNeed['intro'], $sugarGuidance);
        }

        // Chỉ có ý loại nhóm ("không cà phê" đứng riêng, không kèm ý gì khác) -> bán chạy ngoài nhóm bị loại.
        if (!empty($excludeCategoryIds)) {
            $products = $this->baseProductQuery($excludeCategoryIds)
                ->orderByDesc('total_sold')->limit(4)->get();

            return $this->productResponse($products, $excludeNeed['intro'] ?? null, $sugarGuidance);
        }

        // Chỉ có ý "ít ngọt" -> chỉ trả hướng dẫn mức đường, KHÔNG lọc sai sản phẩm.
        if ($sugarGuidance !== null) {
            return [
                'answer' => $sugarGuidance,
                'items' => [],
                'action_url' => route('products'),
                'suggestions' => [],
            ];
        }

        // (8) Câu hỏi mơ hồ hoàn toàn ("uống gì đi") -> CHỈ trả lời đúng 1 câu thông báo trung thực,
        // không kèm sản phẩm hay nút gợi ý gì thêm (không đoán mò khi chưa rõ ý khách).
        return [
            'answer' => config('quick_chat.product_ambiguous_answer'),
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    // Khớp nguyên cụm tên 1 (hoặc nhiều) sản phẩm đang bán xuất hiện trong câu hỏi (accent-insensitive).
    // Bỏ qua tên quá ngắn (< 3 ký tự) để tránh khớp nhầm ngẫu nhiên.
    private function extractExactProductMatchIds(string $normalized, string $unaccented): array
    {
        $ids = [];
        foreach (Product::where('is_active', 1)->get(['id', 'name']) as $p) {
            $nameLower = mb_strtolower($p->name, 'UTF-8');
            if (mb_strlen($nameLower, 'UTF-8') < 3) {
                continue;
            }
            if (str_contains($normalized, $nameLower) || str_contains($unaccented, Str::ascii($nameLower))) {
                $ids[] = $p->id;
            }
        }
        return $ids;
    }

    // Khớp nguyên cụm tên 1 (hoặc nhiều) danh mục đang bật xuất hiện trong câu hỏi (accent-insensitive).
    private function extractExactCategoryMatchIds(string $normalized, string $unaccented): array
    {
        $ids = [];
        foreach (Category::where('is_active', 1)->get(['id', 'name']) as $c) {
            $nameLower = mb_strtolower($c->name, 'UTF-8');
            if (str_contains($normalized, $nameLower) || str_contains($unaccented, Str::ascii($nameLower))) {
                $ids[] = $c->id;
            }
        }
        return $ids;
    }

    // Câu truy vấn sản phẩm cơ bản (đang bán + tổng số đã bán), có thể loại 1 số danh mục.
    private function baseProductQuery(array $excludeCategoryIds)
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

    // Áp ORDER BY theo 'sort' của need đã khớp ('price_asc'|'price_desc'), mặc định vẫn sắp theo bán
    // chạy nhất ('best_seller' cũng rơi vào default vì đó chính là tiêu chí mặc định). Dùng chung cho
    // mọi bước tìm sản phẩm trong handleProduct().
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

    // Nút gợi ý = các danh mục thật (bấm vào sẽ hỏi lại bằng tên danh mục -> khớp qua từ khóa).
    private function categorySuggestions(): array
    {
        return Category::where('is_active', 1)->orderBy('display_order')->pluck('name')
            ->map(fn($name) => ['intent_id' => null, 'topic_key' => null, 'question' => $name, 'label' => $name])
            ->values()->all();
    }

    // Có tín hiệu "câu hỏi về đồ uống" qua bất kỳ nhu cầu nào đã cấu hình không (để định tuyến câu hỏi
    // vào intent 'product' ngay cả khi không chứa từ khóa sản phẩm chung chung nào).
    private function hasProductSignal(string $normalized, string $unaccented): bool
    {
        foreach (config('quick_chat.product_needs', []) as $need) {
            if ($this->matchesAnyTrigger($need['keywords'] ?? [], $normalized, $unaccented)) {
                return true;
            }
        }
        return false;
    }

    // Tìm need ĐẦU TIÊN (theo thứ tự khai báo trong config) vừa khớp từ khóa VỪA thỏa điều kiện
    // $filter — $filter phân loại need theo TRƯỜNG nào có mặt (preferred_categories/excluded_
    // categories/sort/answer-only), tránh phải viết if/else riêng cho từng tên need trong service.
    private function findNeed(string $normalized, string $unaccented, callable $filter): ?array
    {
        foreach (config('quick_chat.product_needs', []) as $key => $need) {
            if (!$filter($need)) {
                continue;
            }
            if ($this->matchesAnyTrigger($need['keywords'] ?? [], $normalized, $unaccented)) {
                return array_merge($need, ['key' => $key]);
            }
        }
        return null;
    }

    // Tách từng từ đơn từ danh sách excluded_categories (dùng để loại khỏi từ khóa rời rạc ở lưới an
    // toàn cuối — vd loại trừ "cà phê" thì "cà" và "phê" không được lọt vào bước từ khóa chung nữa).
    private function tokensFromCategoryMatches(array $matches): array
    {
        $tokens = [];
        foreach ($matches as $m) {
            foreach (preg_split('/\s+/u', mb_strtolower($m, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) as $t) {
                $tokens[] = $t;
            }
        }
        return array_values(array_unique($tokens));
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
            $nameAscii = Str::ascii($nameLower);
            foreach ($matches as $m) {
                $mLower = mb_strtolower($m, 'UTF-8');
                if (str_contains($nameLower, $mLower) || str_contains($nameAscii, Str::ascii($mLower))) {
                    $ids[] = $cat->id;
                    break;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function matchesAnyTrigger(array $triggers, string $normalized, string $unaccented): bool
    {
        $paddedAccented = ' ' . $normalized . ' ';
        $paddedUnaccented = ' ' . $unaccented . ' ';
        foreach ($triggers as $trigger) {
            $t = mb_strtolower($trigger, 'UTF-8');
            if (str_contains($paddedAccented, ' ' . $t . ' ')
                || str_contains($paddedUnaccented, ' ' . Str::ascii($t) . ' ')
                || str_contains($paddedAccented, $t)
                || str_contains($paddedUnaccented, Str::ascii($t))) {
                return true;
            }
        }
        return false;
    }

    // Các từ chung chung không dùng để lọc sản phẩm (dù chúng có thể xuất hiện trong 1 tên danh mục
    // như "Đồ uống khác") — nếu không loại, câu "uống gì..." sẽ bị lọc nhầm về đúng danh mục đó.
    private const PRODUCT_STOPWORDS = [
        'uong', 'do', 'thuc', 'mon', 'san', 'pham', 'menu', 'giai', 'nhiet', 'khat', 'khac',
        'co', 'khong', 'nao', 'gi', 'cho', 'toi', 'minh', 'muon', 'xin', 'giup', 'ngon',
        'nhat', 're', 'mac', 'dat', 'loai', 'cac', 'nhung', 'va', 'hay', 'hoac', 'ne',
        'vay', 'the', 'la', 'cua', 'thich', 'ua', 'ban', 'dang',
    ];

    // Tách các "từ có nghĩa" trong câu hỏi mà đồng thời là 1 từ trong tên sản phẩm/danh mục thật
    // (vd "trà", "cà phê", "khoai môn"), bỏ qua từ chung chung. Trả về dạng CÓ DẤU (đúng như trong
    // CSDL) để dùng cho LIKE. Câu không nhắc từ cụ thể nào -> trả mảng rỗng (dùng danh sách bán chạy).
    private function extractProductKeywords(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }

        // Gom mọi từ đơn xuất hiện trong tên sản phẩm/danh mục: bản không dấu -> bản có dấu (để LIKE).
        $nameWords = [];
        $names = Product::where('is_active', 1)->pluck('name')
            ->merge(Category::where('is_active', 1)->pluck('name'));
        foreach ($names as $name) {
            $tokens = preg_split('/\s+/u', mb_strtolower($name, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($tokens as $token) {
                $nameWords[Str::ascii($token)] = $token;
            }
        }

        $stopwords = array_flip(self::PRODUCT_STOPWORDS);
        $keywords = [];
        foreach (preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $ascii = Str::ascii(mb_strtolower($word, 'UTF-8'));
            if (mb_strlen($ascii, 'UTF-8') < 2) {
                continue;
            }
            if (isset($stopwords[$ascii]) || !isset($nameWords[$ascii])) {
                continue;
            }
            $keywords[$nameWords[$ascii]] = true; // dùng bản có dấu, khử trùng lặp
        }

        return array_keys($keywords);
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

    private function handleOrderTracking(array $intent): array
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

    private function handleOpeningHours(array $intent): array
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

    private function handleContact(array $intent): array
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
