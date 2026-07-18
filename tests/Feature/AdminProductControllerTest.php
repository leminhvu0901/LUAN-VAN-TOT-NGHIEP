<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeProduct(int $categoryId, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Trà sữa trân châu',
            'slug' => 'tra-sua-tran-chau-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 35000,
            'category_id' => $categoryId,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Sau khi lưu sản phẩm, phải quay lại ĐÚNG trang danh sách đã lọc/phân trang trước đó (lấy từ
     * Referer lúc vào trang Sửa) — không được bật về trang index trần làm mất bộ lọc đang áp dụng.
     */
    public function test_updating_product_redirects_back_to_previously_filtered_index_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $product = $this->makeProduct($categoryId);

        $filteredUrl = url('/admin/products?search=tran+chau&status=active&page=2');

        $this->actingAs($admin);
        // Vào trang Sửa với Referer là URL đã lọc — controller phải đọc lại đúng URL này.
        $editResponse = $this->withHeaders(['referer' => $filteredUrl])->get("/admin/products/{$product->id}/edit");
        $editResponse->assertStatus(200);
        // {{ }} của Blade tự escape HTML nên '&' trong query string trở thành '&amp;' khi render.
        $editResponse->assertSee('name="back_url" value="' . htmlspecialchars($filteredUrl) . '"', false);

        $response = $this->from($filteredUrl)->put("/admin/products/{$product->id}", [
            'back_url' => $filteredUrl,
            'name' => 'Trà sữa trân châu (đã sửa)',
            'category_id' => $categoryId,
            'base_price' => 40000,
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect($filteredUrl);
    }

    /**
     * Không có back_url hợp lệ (hoặc bị giả mạo trỏ ra domain khác) -> phải rơi về trang index mặc
     * định, không redirect ra ngoài (chặn open-redirect).
     */
    public function test_updating_product_falls_back_to_index_when_back_url_missing_or_unsafe(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $product = $this->makeProduct($categoryId);

        $this->actingAs($admin);

        // Không gửi back_url -> về index mặc định.
        $response = $this->put("/admin/products/{$product->id}", [
            'name' => 'Trà sữa trân châu (đã sửa)',
            'category_id' => $categoryId,
            'base_price' => 40000,
            'is_active' => '1',
        ]);
        $response->assertRedirect(route('admin.products.index'));

        // back_url giả mạo trỏ ra domain khác -> vẫn về index mặc định, không redirect ra ngoài.
        $response = $this->put("/admin/products/{$product->id}", [
            'back_url' => 'https://evil.example.com/phishing',
            'name' => 'Trà sữa trân châu (đã sửa lần 2)',
            'category_id' => $categoryId,
            'base_price' => 40000,
            'is_active' => '1',
        ]);
        $response->assertRedirect(route('admin.products.index'));
    }

    /**
     * Tương tự khi TẠO sản phẩm mới — vào trang Thêm từ danh sách đã lọc thì lưu xong phải quay lại
     * đúng danh sách đó.
     */
    public function test_creating_product_redirects_back_to_previously_filtered_index_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $filteredUrl = url('/admin/products?category_id=' . $categoryId . '&sort=price_asc');

        $this->actingAs($admin);
        $createResponse = $this->withHeaders(['referer' => $filteredUrl])->get('/admin/products/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('name="back_url" value="' . htmlspecialchars($filteredUrl) . '"', false);

        $response = $this->from($filteredUrl)->post('/admin/products', [
            'back_url' => $filteredUrl,
            'name' => 'Trà sữa mới',
            'category_id' => $categoryId,
            'base_price' => 30000,
            'sku' => '', // Form thật luôn gửi field này (dù để trống) — khớp hành vi submit thật.
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect($filteredUrl);
    }
}
