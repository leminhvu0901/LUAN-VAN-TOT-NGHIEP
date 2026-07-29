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
 * Ảnh tải lên MỚI được lưu với tiền tố "uploads/..." (public/uploads, gắn Railway Volume), ảnh CŨ
 * lưu đường dẫn trần "products/x.jpg" (public/images). Nhiều view từng ghép cứng asset('images/' . $path)
 * -> ảnh mới ra URL sai "/images/uploads/products/x.jpg" và hiển thị vỡ (báo cáo 30/07/2026: thêm sản
 * phẩm có ảnh, lưu xong vào lại thì mất ảnh). Bộ test này khoá lại URL thật sự render ra cho CẢ 2 dạng.
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

    public function test_product_image_url_accessor_handles_new_and_legacy_paths(): void
    {
        $newStyle = $this->makeProduct('uploads/products/abc.jpg');
        $this->assertSame(asset('uploads/products/abc.jpg'), $newStyle->image_url);

        $legacy = $this->makeProduct('products/old.jpg');
        $this->assertSame(asset('images/products/old.jpg'), $legacy->image_url);
    }

    public function test_admin_product_edit_page_renders_uploads_url_for_new_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct('uploads/products/abc.jpg');

        $response = $this->actingAs($admin)->get("/admin/products/{$product->id}/edit");

        $response->assertOk();
        $response->assertSee(asset('uploads/products/abc.jpg'), false);
        // URL hỏng của lỗi cũ - phải KHÔNG còn xuất hiện nữa.
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }

    public function test_admin_product_list_renders_uploads_url_for_new_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeProduct('uploads/products/abc.jpg');

        $response = $this->actingAs($admin)->get('/admin/products');

        $response->assertOk();
        $response->assertSee(asset('uploads/products/abc.jpg'), false);
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }

    public function test_order_item_product_image_url_accessor_handles_new_legacy_and_empty(): void
    {
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách',
            'customer_phone' => '0900000000', 'delivery_address' => 'Test',
            'total_amount' => 35000, 'discount_amount' => 0, 'final_amount' => 35000,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        $product = $this->makeProduct('uploads/products/abc.jpg');

        $newStyle = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'A',
            'product_image' => 'uploads/products/abc.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);
        $legacy = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'B',
            'product_image' => 'products/old.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);
        $empty = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'C',
            'product_image' => null, 'quantity' => 1, 'unit_price' => 35000,
        ]);

        $this->assertSame(asset('uploads/products/abc.jpg'), $newStyle->product_image_url);
        $this->assertSame(asset('images/products/old.jpg'), $legacy->product_image_url);
        $this->assertSame(asset('images/products/placeholder.jpg'), $empty->product_image_url);
    }

    public function test_admin_order_detail_renders_uploads_url_for_new_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct('uploads/products/abc.jpg');
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách',
            'customer_phone' => '0900000000', 'delivery_address' => 'Test',
            'total_amount' => 35000, 'discount_amount' => 0, 'final_amount' => 35000,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Trà sữa',
            'product_image' => 'uploads/products/abc.jpg', 'quantity' => 1, 'unit_price' => 35000,
        ]);

        $response = $this->actingAs($admin)->get("/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee(asset('uploads/products/abc.jpg'), false);
        $response->assertDontSee(asset('images/uploads/products/abc.jpg'), false);
    }
}
