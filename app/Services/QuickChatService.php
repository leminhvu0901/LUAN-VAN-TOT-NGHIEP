<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickChatService
{
    // Xử lý câu hỏi tự do của người dùng — trả lời mẫu chung kèm gợi ý nút bấm (không phân loại ý định).
    public function ask(string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            return $this->plainResponse('Vui lòng nhập câu hỏi.');
        }

        return [
            'intent' => null,
            'answer' => config('quick_chat.fallback_freeform'),
            'items' => [],
            'action_url' => null,
            'suggestions' => $this->fallbackSuggestions(),
        ];
    }

    // Trả lời trực tiếp khi người dùng chọn nút gợi ý
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

    // Đóng gói câu trả lời dạng văn bản đơn giản
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

    // Tìm cấu hình ý định (intent) trong tệp config theo ID
    private function findIntent(string $id): ?array
    {
        foreach (config('quick_chat.intents', []) as $intent) {
            if ($intent['id'] === $id) {
                return $intent;
            }
        }
        return null;
    }

    // Tạo danh sách nút gợi ý mặc định khi không tìm thấy kết quả phù hợp
    private function fallbackSuggestions(): array
    {
        return array_map(
            fn($s) => ['intent_id' => $s['intent_id'], 'topic_key' => null, 'question' => null, 'label' => $s['label']],
            config('quick_chat.fallback_suggestions', [])
        );
    }

    // Điều hướng đến hàm xử lý tương ứng của từng intent
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

    // Xử lý các câu trả lời tĩnh không cần truy vấn dữ liệu động
    private function handleStatic(array $intent): array
    {
        if (!empty($intent['action_route'])) {
            $actionUrl = route($intent['action_route']);
        } else {
            $actionUrl = null;
        }

        return [
            'answer' => $intent['answer'],
            'items' => [],
            'action_url' => $actionUrl,
            'suggestions' => [],
        ];
    }

    // Xử lý hiển thị danh sách sản phẩm bán chạy
    private function handleProductListing(array $intent): array
    {
        $products = $this->applyOrder($this->baseProductQuery(), null)->limit(4)->get();

        return $this->productResponse($products, $intent['answer'], null);
    }

    // Tạo câu truy vấn sản phẩm cơ bản có tính tổng số lượng đã bán
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

    // Thêm điều kiện sắp xếp cho câu truy vấn sản phẩm
    private function applyOrder($query, ?string $sort)
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('products.base_price'),
            'price_desc' => $query->orderByDesc('products.base_price'),
            default => $query->orderByDesc('total_sold'),
        };
    }

    // Định dạng danh sách sản phẩm để trả về cho giao diện chat
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

    // Đóng gói câu trả lời gồm danh sách sản phẩm và hướng dẫn
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

    // Trả về câu trả lời khi không tìm thấy sản phẩm nào phù hợp
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

    // Tạo danh sách gợi ý theo danh mục sản phẩm
    private function categorySuggestions(): array
    {
        return Category::where('is_active', 1)->orderBy('display_order')->pluck('name')
            ->map(fn($name) => ['intent_id' => null, 'topic_key' => null, 'question' => $name, 'label' => $name])
            ->values()->all();
    }

    // Xử lý hiển thị danh sách các chương trình khuyến mãi đang chạy
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

        $items = $promotions->map(function ($promo) {
            if ($promo->code) {
                $code = $promo->code;
            } else {
                $code = 'Ưu đãi #' . $promo->id;
            }

            if ($promo->type === 'percent') {
                $value = 'Giảm ' . rtrim(rtrim(number_format($promo->value, 2, '.', ''), '0'), '.') . '%';
            } else {
                $value = 'Giảm ' . number_format($promo->value, 0, ',', '.') . 'đ';
            }

            if ($promo->min_order_amount) {
                $minOrderAmount = number_format($promo->min_order_amount, 0, ',', '.') . 'đ';
            } else {
                $minOrderAmount = null;
            }

            if ($promo->end_at) {
                $endAt = \Carbon\Carbon::parse($promo->end_at)->format('d/m/Y');
            } else {
                $endAt = null;
            }

            return [
                'type' => 'promotion',
                'code' => $code,
                'value' => $value,
                'min_order_amount' => $minOrderAmount,
                'min_quantity' => $promo->min_quantity,
                'end_at' => $endAt,
            ];
        })->values()->all();

        return [
            'answer' => $intent['answer'],
            'items' => $items,
            'action_url' => null,
            'suggestions' => [],
        ];
    }

    // Xử lý hướng dẫn tra cứu đơn hàng cho khách
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

    // Xử lý hiển thị thời gian mở cửa của cửa hàng
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

    // Xử lý hiển thị thông tin liên hệ của cửa hàng
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

        if (!empty($parts)) {
            $answer = implode(' — ', $parts);
        } else {
            $answer = 'Vui lòng xem thông tin liên hệ tại chân trang website.';
        }

        return [
            'answer' => $answer,
            'items' => [],
            'action_url' => null,
            'suggestions' => [],
        ];
    }
}
