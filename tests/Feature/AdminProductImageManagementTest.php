<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AdminProductControllerTest.php đã có sẵn, chỉ kiểm tra hành vi redirect back_url. File này bổ sung
 * phần CHƯA có test: upload/xoá ảnh chính + ảnh phụ (gallery), viết trong phiên sửa lỗi "ảnh mất khi
 * Railway deploy lại" (2026-07-29). Test này phát hiện thêm 3 chỗ vẫn hard-code tiền tố "images/" khi
 * xoá file cũ (update/xoá gallery/xoá sản phẩm) - sai với ảnh mới lưu ở "uploads/..." - đã sửa cùng lúc.
 */
class AdminProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_creating_product_with_image_and_gallery_stores_files_in_uploads_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Trà sữa trân châu',
            'category_id' => $categoryId,
            'base_price' => 35000,
            'sku' => '',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('main.jpg'),
            'gallery' => [UploadedFile::fake()->image('g1.jpg'), UploadedFile::fake()->image('g2.jpg')],
        ]);

        $response->assertSessionHasNoErrors();
        $product = Product::firstOrFail();

        $this->assertStringStartsWith('uploads/products/', $product->image);
        $this->assertFileExists(upload_path($product->image));

        $product->load('images');
        $this->assertCount(2, $product->images);
        foreach ($product->images as $galleryImage) {
            $this->assertStringStartsWith('uploads/products/gallery/', $galleryImage->image_path);
            $this->assertFileExists(upload_path($galleryImage->image_path));
        }
    }

    public function test_updating_product_image_deletes_the_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Trà sữa trân châu',
            'category_id' => $categoryId,
            'base_price' => 35000,
            'sku' => '',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('main.jpg'),
        ]);
        $product = Product::firstOrFail();
        $oldPath = upload_path($product->image);
        $this->assertFileExists($oldPath);

        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'Trà sữa trân châu (ảnh mới)',
            'category_id' => $categoryId,
            'base_price' => 35000,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('replacement.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists(upload_path($product->image));
    }

    public function test_deleting_a_single_gallery_image_removes_its_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Trà sữa trân châu', 'category_id' => $categoryId, 'base_price' => 35000,
            'sku' => '', 'is_active' => '1',
            'gallery' => [UploadedFile::fake()->image('g1.jpg')],
        ]);
        $galleryImage = ProductImage::firstOrFail();
        $path = upload_path($galleryImage->image_path);
        $this->assertFileExists($path);

        $response = $this->actingAs($admin)->deleteJson("/admin/products/gallery/{$galleryImage->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('product_images', ['id' => $galleryImage->id]);
        $this->assertFileDoesNotExist($path);
    }

    public function test_deleting_product_without_order_history_removes_its_image_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Trà sữa trân châu', 'category_id' => $categoryId, 'base_price' => 35000,
            'sku' => '', 'is_active' => '1',
            'image' => UploadedFile::fake()->image('main.jpg'),
            'gallery' => [UploadedFile::fake()->image('g1.jpg')],
        ]);
        $product = Product::with('images')->firstOrFail();
        $mainPath = upload_path($product->image);
        $galleryPath = upload_path($product->images->first()->image_path);
        $this->assertFileExists($mainPath);
        $this->assertFileExists($galleryPath);

        $response = $this->actingAs($admin)->delete("/admin/products/{$product->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertFileDoesNotExist($mainPath);
        $this->assertFileDoesNotExist($galleryPath);
    }

    public function test_deleting_product_with_order_history_soft_disables_instead_of_deleting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $product = Product::create([
            'name' => 'Trà sữa đã bán', 'slug' => 'tra-sua-da-ban-' . uniqid(), 'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 35000, 'category_id' => $categoryId, 'is_active' => true,
        ]);
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách vãng lai',
            'customer_phone' => '0900000000', 'delivery_address' => 'Test address',
            'total_amount' => 35000, 'discount_amount' => 0, 'final_amount' => 35000,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        DB::table('order_items')->insert([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price' => 35000,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/products/{$product->id}");

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_guest_and_customer_cannot_access_product_management_routes(): void
    {
        $this->get('/admin/products')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/products')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_product_management_routes(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/products')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/products')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
