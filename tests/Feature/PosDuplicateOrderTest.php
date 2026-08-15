<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Chặn tạo trùng đơn ở màn hình bán tại quầy khi lễ tân bấm đúp hoặc gửi lại form cũ.
class PosDuplicateOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Cố định giờ trong khung mở cửa mặc định 08:00-22:00 để OrderService không chặn vì đóng cửa.
        $this->travelTo(Carbon::parse('14:00:00'));
    }

    private function makeStaff(): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'staff_type' => 'receptionist',
            'is_active' => true,
        ]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['name' => 'Trà sữa', 'is_active' => true]);

        return Product::create([
            'name' => 'Trà sữa trân châu',
            'slug' => 'sp-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 50000,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
    }

    private function fillCart(User $staff, Product $product): void
    {
        $cart = Cart::firstOrCreate(['user_id' => $staff->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50000,
        ]);
    }

    // Lấy token mà màn hình tạo đơn phát ra, đây là thứ form gửi kèm khi submit.
    private function tokenFromCreateScreen(User $staff): string
    {
        $this->actingAs($staff)->get(route('staff.reception.orders.create'))->assertOk();

        return (string) session('pos_order_token');
    }

    public function test_man_hinh_tao_don_phat_ra_token_chong_trung(): void
    {
        $staff = $this->makeStaff();
        $token = $this->tokenFromCreateScreen($staff);

        $this->assertNotSame('', $token, 'Màn hình tạo đơn phải phát ra token chống trùng');
    }

    public function test_bam_dup_cung_token_chi_tao_dung_mot_don(): void
    {
        $staff = $this->makeStaff();
        $product = $this->makeProduct();
        $this->fillCart($staff, $product);

        $token = $this->tokenFromCreateScreen($staff);
        $body = [
            'payment_method' => 'cash',
            'pickup_mode' => 'dine_in',
            'idempotency_key' => $token,
        ];

        $this->actingAs($staff)->post(route('staff.reception.orders.store'), $body);

        // Lễ tân bấm lại lần hai, form cũ nên vẫn gửi lên đúng token đó.
        $this->fillCart($staff, $product);
        $this->actingAs($staff)->post(route('staff.reception.orders.store'), $body);

        $this->assertSame(1, Order::count(), 'Bấm đúp phải chỉ tạo đúng 1 đơn');
    }

    public function test_gui_thieu_token_thi_bi_chan(): void
    {
        $staff = $this->makeStaff();
        $product = $this->makeProduct();
        $this->fillCart($staff, $product);

        $this->actingAs($staff)
            ->post(route('staff.reception.orders.store'), [
                'payment_method' => 'cash',
                'pickup_mode' => 'dine_in',
            ])
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, Order::count());
    }

    public function test_token_khong_khop_phien_thi_bi_chan(): void
    {
        $staff = $this->makeStaff();
        $product = $this->makeProduct();
        $this->fillCart($staff, $product);

        $this->tokenFromCreateScreen($staff);

        // Token đúng định dạng nhưng không phải token đã phát cho phiên này.
        $this->actingAs($staff)
            ->post(route('staff.reception.orders.store'), [
                'payment_method' => 'cash',
                'pickup_mode' => 'dine_in',
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            ])
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, Order::count());
    }

    // Đơn lễ tân tạo hộ khách lưu user_id là khách hàng, nên phép tìm đơn trùng không được lọc theo nhân viên đang đăng nhập.
    public function test_don_tao_ho_khach_van_nhan_ra_trung_key(): void
    {
        $staff = $this->makeStaff();
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $this->fillCart($staff, $product);

        $token = $this->tokenFromCreateScreen($staff);
        $body = [
            'payment_method' => 'cash',
            'pickup_mode' => 'dine_in',
            'customer_id' => $customer->id,
            'idempotency_key' => $token,
        ];

        $this->actingAs($staff)->post(route('staff.reception.orders.store'), $body);
        $order = Order::firstOrFail();
        $this->assertSame($customer->id, $order->user_id, 'Đơn phải đứng tên khách hàng');

        // Gọi thẳng service với đúng key đó, phải trả về đơn cũ chứ không được ném lỗi trùng khóa.
        $again = app(\App\Services\OrderService::class)->create($staff, [
            'idempotency_key' => $token,
            'delivery_type' => 'pickup',
            'pickup_mode' => 'dine_in',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
        ], 'cash');

        $this->assertSame($order->id, $again->id, 'Phải trả về đúng đơn đã tạo, không tạo đơn mới');
        $this->assertSame(1, Order::count());
    }
}
