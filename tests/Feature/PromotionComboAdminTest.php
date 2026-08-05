<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotionComboAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeProduct(int $categoryId, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Trà sữa trân châu',
            'slug' => 'sp-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 35000,
            'category_id' => $categoryId,
            'is_active' => true,
        ], $overrides));
    }

    private function baseComboPayload(array $overrides = []): array
    {
        return array_merge([
            'scope' => 'combo',
            'apply_for' => 'all',
            'applies_to' => 'all',
        ], $overrides);
    }

    public function test_combo_create_page_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $this->makeProduct($categoryId);

        $this->actingAs($admin)->get('/admin/promotions/create')
            ->assertOk()
            ->assertSee('Combo');
    }

    public function test_combo_edit_page_renders_with_prefilled_items_and_reward(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId, ['name' => 'Trà A']);
        $gift = $this->makeProduct($categoryId, ['name' => 'Quà B']);

        $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [2],
            'combo_has_discount' => '1',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'combo_has_gift' => '1',
            'gift_product_id' => $gift->id,
            'gift_quantity' => 1,
        ]));
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();

        $this->actingAs($admin)->get("/admin/promotions/{$promotion->id}/edit")
            ->assertOk()
            ->assertSee('Trà A')
            ->assertSee('Quà B');
    }

    public function test_admin_can_create_combo_with_discount_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId, ['name' => 'A']);
        $b = $this->makeProduct($categoryId, ['name' => 'B']);

        $response = $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id, $b->id],
            'combo_quantities' => [2, 1],
            'combo_has_discount' => '1',
            'discount_type' => 'percent',
            'discount_value' => 15,
            'combo_max_discount_amount' => 20000,
        ]));

        $response->assertRedirect('/admin/promotions');
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();
        $this->assertSame('percent', $promotion->combo->discount_type);
        $this->assertEquals(15, $promotion->combo->discount_value);
        $this->assertEquals(20000, $promotion->combo->max_discount_amount);
        $this->assertNull($promotion->combo->gift_product_id);
        $this->assertCount(2, $promotion->comboItems);
        $this->assertSame(2, $promotion->comboItems->firstWhere('product_id', $a->id)->quantity);
        $this->assertSame(1, $promotion->comboItems->firstWhere('product_id', $b->id)->quantity);
    }

    public function test_admin_can_create_combo_with_gift_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId, ['name' => 'A']);
        $gift = $this->makeProduct($categoryId, ['name' => 'Gift']);

        $response = $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [3],
            'combo_has_gift' => '1',
            'gift_product_id' => $gift->id,
            'gift_quantity' => 1,
        ]));

        $response->assertRedirect('/admin/promotions');
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();
        $this->assertNull($promotion->combo->discount_type);
        $this->assertSame($gift->id, $promotion->combo->gift_product_id);
        $this->assertSame(1, $promotion->combo->gift_quantity);
    }

    public function test_admin_can_create_combo_with_both_discount_and_gift(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId, ['name' => 'A']);
        $gift = $this->makeProduct($categoryId, ['name' => 'Gift']);

        $response = $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [1],
            'combo_has_discount' => '1',
            'discount_type' => 'fixed',
            'discount_value' => 10000,
            'combo_has_gift' => '1',
            'gift_product_id' => $gift->id,
            'gift_quantity' => 1,
        ]));

        $response->assertRedirect('/admin/promotions');
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();
        $this->assertSame('fixed', $promotion->combo->discount_type);
        $this->assertEquals(10000, $promotion->combo->discount_value);
        $this->assertSame($gift->id, $promotion->combo->gift_product_id);
    }

    public function test_creating_combo_without_discount_or_gift_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId);

        $response = $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [1],
        ]));

        $response->assertSessionHasErrors('combo_has_discount');
        $this->assertSame(0, Promotion::where('scope', 'combo')->count());
    }

    public function test_creating_combo_with_mismatched_product_and_quantity_arrays_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId);
        $b = $this->makeProduct($categoryId);

        $response = $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id, $b->id],
            'combo_quantities' => [1],
            'combo_has_gift' => '1',
            'gift_product_id' => $a->id,
            'gift_quantity' => 1,
        ]));

        $response->assertSessionHasErrors('combo_product_ids');
        $this->assertSame(0, Promotion::where('scope', 'combo')->count());
    }

    public function test_admin_can_update_combo_items_and_reward_config(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId, ['name' => 'A']);
        $b = $this->makeProduct($categoryId, ['name' => 'B']);
        $c = $this->makeProduct($categoryId, ['name' => 'C']);

        $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [1],
            'combo_has_discount' => '1',
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]));
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();

        $response = $this->actingAs($admin)->put("/admin/promotions/{$promotion->id}", $this->baseComboPayload([
            'combo_product_ids' => [$b->id, $c->id],
            'combo_quantities' => [2, 2],
            'combo_has_discount' => '1',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
        ]));

        $response->assertRedirect('/admin/promotions');
        $promotion->refresh();
        $this->assertSame('fixed', $promotion->combo->discount_type);
        $this->assertEquals(5000, $promotion->combo->discount_value);
        $this->assertCount(2, $promotion->comboItems);
        $this->assertNull($promotion->comboItems->firstWhere('product_id', $a->id));
        $this->assertNotNull($promotion->comboItems->firstWhere('product_id', $b->id));
    }

    public function test_switching_scope_away_from_combo_deletes_combo_relations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = $this->makeCategory();
        $a = $this->makeProduct($categoryId);

        $this->actingAs($admin)->post('/admin/promotions', $this->baseComboPayload([
            'combo_product_ids' => [$a->id],
            'combo_quantities' => [1],
            'combo_has_gift' => '1',
            'gift_product_id' => $a->id,
            'gift_quantity' => 1,
        ]));
        $promotion = Promotion::where('scope', 'combo')->firstOrFail();

        $this->actingAs($admin)->put("/admin/promotions/{$promotion->id}", [
            'scope' => 'order',
            'type' => 'fixed',
            'value' => 10000,
            'apply_for' => 'all',
            'applies_to' => 'all',
        ]);

        $this->assertNull($promotion->fresh()->combo);
        $this->assertCount(0, $promotion->fresh()->comboItems);
    }
}
