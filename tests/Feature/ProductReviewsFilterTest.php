<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kiểm tra lọc theo sao/có hình ảnh + phân trang ("Xem thêm đánh giá") + tag "(Bạn)" cho danh sách
 * đánh giá sản phẩm. Route AJAX riêng /products/{id}/reviews đã bị xóa khi bỏ AJAX (Giai đoạn 8) -
 * lọc giờ áp dụng ngay khi tải trang qua query string thật, dùng lại 2 trang gốc: chi tiết sản phẩm
 * (ProductController::show(), full view) và "Xem đánh giá" (ReviewController::create(), compact view).
 */
class ProductReviewsFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Drink', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Product::create(array_merge([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . Str::random(6), 'sku' => 'SKU-' . Str::random(6),
            'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => true,
        ], $overrides));
    }

    /**
     * Trang "Xem đánh giá" (view=compact) đòi hỏi user đăng nhập có đơn hàng completed thật chứa sản
     * phẩm đó (ReviewController::create() tự kiểm tra) - khác endpoint AJAX cũ không cần điều kiện này.
     */
    private function makeCompletedOrderFor(User $user, Product $product): Order
    {
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 30000, 'discount_amount' => 0,
            'final_amount' => 30000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        return $order;
    }

    public function test_reviews_endpoint_filters_by_rating(): void
    {
        $product = $this->makeProduct();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Review::create(['user_id' => $userA->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Rất ngon', 'is_visible' => 1]);
        Review::create(['user_id' => $userB->id, 'product_id' => $product->id, 'rating' => 3, 'comment' => 'Tạm ổn', 'is_visible' => 1]);

        $response = $this->get(route('product.show', $product->slug) . '?rating=5');

        $response->assertOk();
        $response->assertSee('Rất ngon');
        $response->assertDontSee('Tạm ổn');
    }

    public function test_reviews_endpoint_filters_by_has_image(): void
    {
        $product = $this->makeProduct();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Review::create(['user_id' => $userA->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Có ảnh nè', 'image' => json_encode(['reviews/a.jpg']), 'is_visible' => 1]);
        Review::create(['user_id' => $userB->id, 'product_id' => $product->id, 'rating' => 4, 'comment' => 'Không ảnh', 'is_visible' => 1]);

        $viewer = User::factory()->create();
        $order = $this->makeCompletedOrderFor($viewer, $product);

        $response = $this->actingAs($viewer)->get("/orders/{$order->id}/products/{$product->id}/review?has_image=1");

        $response->assertOk();
        $response->assertSee('Có ảnh nè');
        $response->assertDontSee('Không ảnh');
    }

    public function test_reviews_endpoint_without_filter_returns_all_visible_reviews(): void
    {
        $product = $this->makeProduct();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Review::create(['user_id' => $userA->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Đánh giá A', 'is_visible' => 1]);
        Review::create(['user_id' => $userB->id, 'product_id' => $product->id, 'rating' => 2, 'comment' => 'Đánh giá B', 'is_visible' => 1]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Đánh giá A');
        $response->assertSee('Đánh giá B');
    }

    public function test_reviews_endpoint_hides_invisible_reviews(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Đánh giá đã ẩn', 'is_visible' => 0]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertDontSee('Đánh giá đã ẩn');
    }

    public function test_reviews_endpoint_paginates_and_load_more_reflects_remaining_pages(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        for ($i = 1; $i <= 6; $i++) {
            Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Đánh giá số ' . $i, 'is_visible' => 1]);
        }

        $page1 = $this->get(route('product.show', $product->slug));
        $page1->assertOk();
        $page1->assertSee('review-loadmore-btn', false);
        $this->assertEquals(5, substr_count($page1->getContent(), 'pd-review-item'));

        $page2 = $this->get(route('product.show', $product->slug) . '?page=2');
        $page2->assertOk();
        $page2->assertDontSee('review-loadmore-btn', false);
        $this->assertEquals(1, substr_count($page2->getContent(), 'pd-review-item'));
    }

    public function test_you_tag_shown_only_for_own_review_and_only_when_logged_in(): void
    {
        $product = $this->makeProduct();
        $me = User::factory()->create();
        $other = User::factory()->create();
        Review::create(['user_id' => $me->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Của tôi', 'is_visible' => 1]);
        Review::create(['user_id' => $other->id, 'product_id' => $product->id, 'rating' => 4, 'comment' => 'Của người khác', 'is_visible' => 1]);

        // Khách chưa đăng nhập -> không thấy tag (Bạn) ở đâu cả.
        $guestResponse = $this->get(route('product.show', $product->slug));
        $guestResponse->assertOk();
        $guestResponse->assertDontSee('(Bạn)');

        // Đăng nhập đúng chủ tài khoản -> CHỈ review của chính mình có tag, không lem sang review khác.
        $response = $this->actingAs($me)->get(route('product.show', $product->slug));
        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('(Bạn)', $content);
        $this->assertEquals(1, substr_count($content, '(Bạn)'));
    }

    public function test_product_show_page_renders_filter_buttons_with_correct_counts(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Ngon', 'is_visible' => 1]);
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 4, 'comment' => 'Ổn', 'image' => json_encode(['reviews/x.jpg']), 'is_visible' => 1]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertSee('5 sao (1)');
        $response->assertSee('4 sao (1)');
        $response->assertSee('Có hình ảnh (1)');
    }

    /**
     * Trước đây trang "Xem đánh giá" chỉ có nút lọc 5 sao/4 sao (thiếu hẳn 1-3 sao) — giờ phải đủ cả
     * 5 mức sao.
     */
    public function test_review_create_page_renders_filter_buttons_for_all_five_stars(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 30000, 'discount_amount' => 0,
            'final_amount' => 30000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk();
        foreach ([1, 2, 3, 4, 5] as $star) {
            $response->assertSee($star . ' sao (0)');
        }
        $response->assertSee('Có hình ảnh (0)');
    }
}
