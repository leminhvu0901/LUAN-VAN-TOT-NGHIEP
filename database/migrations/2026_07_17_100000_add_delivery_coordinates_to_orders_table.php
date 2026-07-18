<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lưu tọa độ GPS của địa chỉ giao hàng ngay tại thời điểm đặt đơn (snapshot, không tham chiếu
     * user_addresses vì địa chỉ khách có thể sửa/xóa sau này) — để nhân viên vận chuyển mở đúng
     * điểm trên bản đồ thay vì tìm theo chuỗi địa chỉ text (dễ ra nhiều kết quả trùng tên đường).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_latitude')) {
                $table->dropColumn('delivery_latitude');
            }
            if (Schema::hasColumn('orders', 'delivery_longitude')) {
                $table->dropColumn('delivery_longitude');
            }
        });
    }
};
