<?php

namespace Tests\Feature;

use App\Http\Controllers\Backend\Admin\MaterialController;
use App\Models\Material;
use App\Models\MaterialImport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MaterialImportConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('material_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('remaining_quantity', 10, 2)->nullable();
            $table->decimal('total_price', 12, 2);
            $table->string('note')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity_used', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_materials');
        Schema::dropIfExists('products');
        Schema::dropIfExists('material_imports');
        Schema::dropIfExists('materials');

        parent::tearDown();
    }

    public function test_new_import_rejects_non_positive_price_and_expiration_today(): void
    {
        $material = $this->createMaterial();
        $request = Request::create('/admin/materials/' . $material->id . '/imports', 'POST', [
            'quantity' => 10,
            'total_price' => 0,
            'expiration_date' => today()->toDateString(),
        ]);

        try {
            app(MaterialController::class)->storeImport($request, $material);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('total_price', $exception->errors());
            $this->assertArrayHasKey('expiration_date', $exception->errors());
        }

        $this->assertDatabaseCount('material_imports', 0);
    }

    public function test_material_fields_respect_length_and_cost_limits(): void
    {
        $request = Request::create('/admin/materials', 'POST', [
            'name' => str_repeat('a', 51),
            'unit' => str_repeat('b', 21),
            'unit_price' => 1000000000,
        ]);

        try {
            app(MaterialController::class)->store($request);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
            $this->assertArrayHasKey('unit', $exception->errors());
            $this->assertArrayHasKey('unit_price', $exception->errors());
        }

        $this->assertDatabaseCount('materials', 0);
    }

    public function test_material_unit_rejects_numbers(): void
    {
        $request = Request::create('/admin/materials', 'POST', [
            'name' => 'Sữa tươi',
            'unit' => 'Thùng 24',
            'unit_price' => 100000,
        ]);

        try {
            app(MaterialController::class)->store($request);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unit', $exception->errors());
        }

        $this->assertDatabaseCount('materials', 0);
    }

    public function test_legacy_unit_with_pack_size_can_remain_unchanged_during_other_edits(): void
    {
        $material = $this->createMaterial(['unit' => 'Lốc 50 cái']);
        $request = Request::create('/admin/materials/' . $material->id, 'PUT', [
            'name' => 'Ly nhựa',
            'unit' => 'Lốc 50 cái',
            'unit_price' => 100000,
        ]);

        app(MaterialController::class)->update($request, $material);

        $material->refresh();
        $this->assertSame('Ly nhựa', $material->name);
        $this->assertSame('Lốc 50 cái', $material->unit);
    }

    public function test_new_import_updates_stock_and_average_cost_atomically(): void
    {
        $material = $this->createMaterial([
            'current_stock' => 10,
            'unit_price' => 5,
        ]);
        $request = Request::create('/admin/materials/' . $material->id . '/imports', 'POST', [
            'quantity' => 10,
            'total_price' => 100,
            'expiration_date' => today()->addDay()->toDateString(),
        ]);

        app(MaterialController::class)->storeImport($request, $material);

        $material->refresh();
        $this->assertSame(20.0, (float) $material->current_stock);
        $this->assertSame(7.5, (float) $material->unit_price);
        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'total_price' => 100,
        ]);
    }

    public function test_new_import_cannot_make_average_cost_reach_one_billion(): void
    {
        $material = $this->createMaterial();
        $request = Request::create('/admin/materials/' . $material->id . '/imports', 'POST', [
            'quantity' => 1,
            'total_price' => 1000000000,
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(MaterialController::class)->storeImport($request, $material);
        } finally {
            $this->assertDatabaseCount('material_imports', 0);
            $this->assertSame(0.0, (float) $material->fresh()->current_stock);
        }
    }

    public function test_import_quantity_cannot_be_reduced_below_consumed_quantity(): void
    {
        $material = $this->createMaterial([
            'current_stock' => 4,
            'unit_price' => 10,
        ]);
        $import = MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => 10,
            'remaining_quantity' => 4,
            'total_price' => 100,
        ]);
        $request = Request::create('/admin/materials/imports/' . $import->id, 'PUT', [
            'quantity' => 5,
            'total_price' => 100,
        ]);

        $this->expectException(ValidationException::class);
        app(MaterialController::class)->updateImport($request, $import);
    }

    public function test_editing_partially_consumed_import_recalculates_only_remaining_value(): void
    {
        $material = $this->createMaterial([
            'current_stock' => 4,
            'unit_price' => 10,
        ]);
        $import = MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => 10,
            'remaining_quantity' => 4,
            'total_price' => 100,
        ]);
        $request = Request::create('/admin/materials/imports/' . $import->id, 'PUT', [
            'quantity' => 10,
            'total_price' => 200,
        ]);

        app(MaterialController::class)->updateImport($request, $import);

        $material->refresh();
        $this->assertSame(4.0, (float) $material->current_stock);
        $this->assertSame(20.0, (float) $material->unit_price);
        $this->assertSame(4.0, (float) $import->fresh()->remaining_quantity);
    }

    public function test_search_and_status_filters_are_grouped_together(): void
    {
        $this->createMaterial([
            'name' => 'Đường',
            'current_stock' => 10,
        ]);
        $this->createMaterial([
            'name' => 'Sữa',
            'current_stock' => 0,
        ]);
        $request = Request::create('/admin/materials', 'GET', [
            'search' => 'Đường',
            'status' => 'out_of_stock',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(MaterialController::class)->index($request);

        $this->assertStringNotContainsString('Đường', $response->getData(true)['html']);
    }

    public function test_bulk_delete_all_respects_excluded_material_ids(): void
    {
        $first = $this->createMaterial(['name' => 'Đường']);
        $excluded = $this->createMaterial(['name' => 'Sữa']);
        $third = $this->createMaterial(['name' => 'Trà']);
        $request = Request::create('/admin/materials/bulk-delete', 'POST', [
            'delete_all_pages' => '1',
            'excluded_material_ids' => [$excluded->id],
        ]);

        app(MaterialController::class)->bulkDelete($request);

        $this->assertDatabaseMissing('materials', ['id' => $first->id]);
        $this->assertDatabaseHas('materials', ['id' => $excluded->id]);
        $this->assertDatabaseMissing('materials', ['id' => $third->id]);
    }

    public function test_material_with_active_lot_cannot_be_deleted(): void
    {
        $material = $this->createMaterial();
        MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => 1,
            'remaining_quantity' => 1,
            'total_price' => 10,
        ]);

        app(MaterialController::class)->destroy($material);

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_material_with_depleted_lot_history_cannot_be_deleted(): void
    {
        $material = $this->createMaterial(['current_stock' => 0]);
        MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => 1,
            'remaining_quantity' => 0,
            'total_price' => 10,
        ]);

        app(MaterialController::class)->destroy($material);

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_bulk_delete_skips_materials_with_active_lots(): void
    {
        $activeMaterial = $this->createMaterial(['name' => 'Còn lô']);
        $depletedMaterial = $this->createMaterial(['name' => 'Hết lô']);
        MaterialImport::create([
            'material_id' => $activeMaterial->id,
            'quantity' => 2,
            'remaining_quantity' => 1,
            'total_price' => 20,
        ]);
        MaterialImport::create([
            'material_id' => $depletedMaterial->id,
            'quantity' => 2,
            'remaining_quantity' => 0,
            'total_price' => 20,
        ]);
        $request = Request::create('/admin/materials/bulk-delete', 'POST', [
            'delete_all_pages' => '1',
        ]);

        app(MaterialController::class)->bulkDelete($request);

        $this->assertDatabaseHas('materials', ['id' => $activeMaterial->id]);
        $this->assertDatabaseHas('materials', ['id' => $depletedMaterial->id]);
    }

    private function createMaterial(array $attributes = []): Material
    {
        return Material::create(array_merge([
            'name' => 'Đường',
            'unit' => 'kg',
            'unit_price' => 0,
            'current_stock' => 0,
        ], $attributes));
    }
}
