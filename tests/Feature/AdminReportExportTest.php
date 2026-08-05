<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Nút "Xuất báo cáo" trước đây chỉ gọi window.print() (in trang, không phải xuất file) - người dùng
 * báo cáo cần file Excel thật (30/07/2026). Đã thêm ReportController::export() dùng chung số liệu với
 * index() (qua buildReportData()) để tránh lệch số giữa trang xem và file xuất.
 */
class AdminReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompletedOrderWithProduct(float $amount, string $deliveryType = 'pickup'): Order
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $product = Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()), 'base_price' => $amount,
            'category_id' => $categoryId, 'is_active' => true,
        ]);
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'customer_name' => 'Khách vãng lai',
            'customer_phone' => '0912345678', 'delivery_address' => 'Test address',
            'total_amount' => $amount, 'discount_amount' => 0, 'final_amount' => $amount,
            'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => $deliveryType,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 1, 'unit_price' => $amount,
        ]);

        return $order;
    }

    public function test_export_downloads_an_xlsx_file_with_expected_sheets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeCompletedOrderWithProduct(35000);
        Material::create(['name' => 'Trân châu đen', 'unit' => 'kg', 'unit_price' => 50000, 'current_stock' => 0, 'is_active' => true]);
        Material::create(['name' => 'Sữa tươi', 'unit' => 'lít', 'unit_price' => 30000, 'current_stock' => 5, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/reports/export?preset=30_days');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpFile);
        $sheetNames = $spreadsheet->getSheetNames();

        $this->assertSame([
            'Tổng quan', 'Doanh thu theo ngày', 'Sản phẩm bán chạy',
            'Doanh thu theo danh mục', 'Khách hàng thân thiết', 'Tồn kho nguyên liệu',
        ], $sheetNames);

        $overview = $spreadsheet->getSheetByName('Tổng quan');
        $this->assertSame('Chỉ số', $overview->getCell('A4')->getValue());
        $this->assertSame('Tổng doanh thu', $overview->getCell('A5')->getValue());
        $this->assertSame('35.000đ', $overview->getCell('B5')->getValue());

        $products = $spreadsheet->getSheetByName('Sản phẩm bán chạy');
        $this->assertSame('Trà sữa trân châu', $products->getCell('B5')->getValue());
        $this->assertEquals(1, $products->getCell('D5')->getValue());

        $inventory = $spreadsheet->getSheetByName('Tồn kho nguyên liệu');
        $this->assertSame('Trân châu đen', $inventory->getCell('A5')->getValue());
        $this->assertSame('Đã hết hàng', $inventory->getCell('D5')->getValue());
        $this->assertSame('Sữa tươi', $inventory->getCell('A6')->getValue());
        $this->assertSame('Sắp hết', $inventory->getCell('D6')->getValue());

        @unlink($tmpFile);
    }

    public function test_export_respects_the_active_period_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recentOrder = $this->makeCompletedOrderWithProduct(50000);
        $recentOrder->created_at = now()->subDays(2);
        $recentOrder->save();

        $oldOrder = $this->makeCompletedOrderWithProduct(999000);
        $oldOrder->created_at = now()->subYears(2);
        $oldOrder->save();

        $response = $this->actingAs($admin)->get('/admin/reports/export?preset=7_days');
        $response->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        file_put_contents($tmpFile, $response->streamedContent());
        $spreadsheet = IOFactory::load($tmpFile);

        $revenueSheet = $spreadsheet->getSheetByName('Doanh thu theo ngày');
        $highestRow = $revenueSheet->getHighestRow();
        $totalRow = $revenueSheet->getCell('A' . $highestRow)->getValue();
        $this->assertSame('TỔNG CỘNG', $totalRow);
        // Chỉ đơn trong 7 ngày (50.000) được tính, đơn 2 năm trước (999.000) không được gộp vào.
        $this->assertEquals(50000, $revenueSheet->getCell('B' . $highestRow)->getCalculatedValue());

        @unlink($tmpFile);
    }

    public function test_guest_and_customer_cannot_export_reports(): void
    {
        $this->get('/admin/reports/export')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/reports/export')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_export_reports(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/reports/export')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/reports/export')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
