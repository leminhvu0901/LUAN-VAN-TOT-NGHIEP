<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Trà sữa',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Trà sữa']);
    }

    public function test_creating_category_rejects_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::create(['name' => 'Cà phê', 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Cà phê',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Trà trái cây', 'is_active' => true]);

        $response = $this->actingAs($admin)->put("/admin/categories/{$category->id}", [
            'name' => 'Trà trái cây mới',
            'is_active' => '1',
            'display_order' => 5,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Trà trái cây mới',
            'display_order' => 5,
        ]);
    }

    public function test_deleting_category_with_products_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Trà sữa', 'is_active' => true]);
        Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()), 'base_price' => 35000,
            'category_id' => $category->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_deleting_empty_category_succeeds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Danh mục rỗng', 'is_active' => true]);

        $response = $this->actingAs($admin)->delete("/admin/categories/{$category->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_bulk_delete_skips_categories_that_still_have_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $emptyCategory = Category::create(['name' => 'Danh mục A', 'is_active' => true]);
        $categoryWithProduct = Category::create(['name' => 'Danh mục B', 'is_active' => true]);
        Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-tran-chau-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()), 'base_price' => 35000,
            'category_id' => $categoryWithProduct->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/categories/bulk-delete', [
            'category_ids' => [$emptyCategory->id, $categoryWithProduct->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $emptyCategory->id]);
        $this->assertDatabaseHas('categories', ['id' => $categoryWithProduct->id]);
    }

    public function test_guest_and_customer_cannot_access_category_management_routes(): void
    {
        $this->get('/admin/categories')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/categories')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_category_management_routes(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/categories')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/categories')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
