<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kiểm tra các view render đúng URL ảnh cho mọi dạng đường dẫn lưu trong DB.
 * Sau khi gỡ bỏ hệ thống Railway (không còn public/uploads/), mọi đường dẫn
 * đều theo quy ước duy nhất: tương đối so với public/images/.
 */
class UploadedImageUrlRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $imagePath): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()), 'base_price' => 35000,
            'category_id' => $categoryId, 'is_active' => true, 'image' => $imagePath,
        ]);
    }

    public function test_product_image_url_accessor_handles_path(): void
    {
        $product = $this->makeProduct('products/abc.jpg');
        $this->assertSame(asset('images/products/abc.jpg'), $product->image_url);

        $legacy = $this->makeProduct('products/old.jpg');
        $this->assertSame(asset('images/products/old.jpg'), $legacy->image_url);
    }

    public function test_admin_product_edit_page_renders_correct_image_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct('products/abc.jpg');

        $response = $this->actingAs($admin)->get("/admin/products/{$product->id}/edit");

        $response->assertOk();
        $response->assertSee(asset('images/products/abc.jpg'), false);
        // Đảm bảo không còn URL hỏng dạng "/images/uploads/..." nữa
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }

    public function test_admin_product_list_renders_correct_image_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeProduct('products/abc.jpg');

        $response = $this->actingAs($admin)->get('/admin/products');

        $response->assertOk();
        $response->assertSee(asset('images/products/abc.jpg'), false);
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }

    public function test_order_item_product_image_url_accessor_handles_path_and_empty(): void
    {
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách',
            'customer_phone' => '0900000000', 'delivery_address' => 'Test',
            'total_amount' => 35000, 'discount_amount' => 0, 'final_amount' => 35000,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        $product = $this->makeProduct('products/abc.jpg');

        $withImage = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'A',
            'product_image' => 'products/abc.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);
        $legacy = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'B',
            'product_image' => 'products/old.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);
        $empty = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'C',
            'product_image' => null, 'quantity' => 1, 'unit_price' => 35000,
        ]);

        $this->assertSame(asset('images/products/abc.jpg'), $withImage->product_image_url);
        $this->assertSame(asset('images/products/old.jpg'), $legacy->product_image_url);
        $this->assertSame(asset('images/products/placeholder.jpg'), $empty->product_image_url);
    }

    public function test_admin_order_detail_renders_correct_image_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct('products/abc.jpg');
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách',
            'customer_phone' => '0900000000', 'delivery_address' => 'Test',
            'total_amount' => 35000, 'discount_amount' => 0, 'final_amount' => 35000,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Trà sữa',
            'product_image' => 'products/abc.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);

        $response = $this->actingAs($admin)->get("/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee(asset('images/products/abc.jpg'), false);
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }
}
