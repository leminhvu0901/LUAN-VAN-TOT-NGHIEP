<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Trước đây ReviewController::destroy()/bulkDelete() chỉ xóa dòng DB, KHÔNG xóa file ảnh
 * (mảng JSON đường dẫn trong cột "image") - khác với Banner/Product đã dọn file khi xóa.
 * Đã bổ sung deleteReviewImageFiles() dùng chung, test này xác nhận file ảnh biến mất theo.
 */
class AdminReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()), 'base_price' => 35000,
            'category_id' => $categoryId, 'is_active' => true,
        ]);
    }

    private function makeReviewWithImages(int $userId, int $productId, int $imageCount = 1): Review
    {
        $images = [];
        foreach (range(1, $imageCount) as $i) {
            $filename = 'review_' . uniqid() . "_{$i}.jpg";
            $path = public_path('images/reviews/' . $filename);
            file_put_contents($path, 'fake-image-content');
            $images[] = 'reviews/' . $filename;
        }

        return Review::create([
            'user_id' => $userId, 'product_id' => $productId, 'rating' => 5,
            'comment' => 'Ngon lắm!', 'image' => json_encode($images), 'is_visible' => true,
        ]);
    }

    public function test_admin_can_create_review_with_images(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->post('/admin/reviews', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'comment' => 'Khá ổn',
            'is_visible' => 1,
            'new_images' => [UploadedFile::fake()->image('r1.jpg')],
        ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHasNoErrors();

        $review = Review::firstOrFail();
        $images = json_decode($review->image, true);
        $this->assertCount(1, $images);
        $this->assertStringStartsWith('reviews/', $images[0]);
        $this->assertFileExists(upload_path($images[0]));
    }

    public function test_admin_can_toggle_review_visibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $review = Review::create([
            'user_id' => $customer->id, 'product_id' => $product->id, 'rating' => 5,
            'comment' => 'Tốt', 'is_visible' => true,
        ]);

        $response = $this->actingAs($admin)->post("/admin/reviews/{$review->id}/toggle-visibility");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse((bool) $review->fresh()->is_visible);
    }

    public function test_deleting_single_review_removes_its_image_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $review = $this->makeReviewWithImages($customer->id, $product->id, 2);
        $paths = array_map('upload_path', json_decode($review->image, true));
        foreach ($paths as $p) {
            $this->assertFileExists($p);
        }

        $response = $this->actingAs($admin)->delete("/admin/reviews/{$review->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        foreach ($paths as $p) {
            $this->assertFileDoesNotExist($p);
        }
    }

    public function test_deleting_single_image_from_review_removes_only_that_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $review = $this->makeReviewWithImages($customer->id, $product->id, 2);
        $images = json_decode($review->image, true);
        [$keep, $remove] = $images;

        $response = $this->actingAs($admin)->delete("/admin/reviews/{$review->id}/image", ['image' => $remove]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFileDoesNotExist(upload_path($remove));
        $this->assertFileExists(upload_path($keep));
        $remainingImages = json_decode($review->fresh()->image, true);
        $this->assertSame([$keep], $remainingImages);
    }

    public function test_bulk_delete_removes_image_files_for_all_selected_reviews(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $review1 = $this->makeReviewWithImages($customer->id, $product->id, 1);
        $review2 = $this->makeReviewWithImages($customer->id, $product->id, 1);
        $path1 = upload_path(json_decode($review1->image, true)[0]);
        $path2 = upload_path(json_decode($review2->image, true)[0]);

        $response = $this->actingAs($admin)->post('/admin/reviews/bulk-delete', [
            'review_ids' => [$review1->id, $review2->id],
        ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', ['id' => $review1->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review2->id]);
        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);
    }

    public function test_guest_and_customer_cannot_access_review_management_routes(): void
    {
        $this->get('/admin/reviews')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/reviews')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_review_management_routes(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/reviews')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/reviews')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
