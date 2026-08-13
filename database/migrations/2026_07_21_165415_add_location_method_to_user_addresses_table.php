<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Cách khách đã xác định vị trí: 'gps' | 'map' | 'manual'. Mặc định 'map' cho dữ liệu cũ.
            $table->string('location_method', 10)->default('map')->after('longitude');
            // Địa chỉ tham khảo do Geoapify chuẩn hóa (chỉ để hiển thị/đối chiếu, không thay khu vực khách nhập).
            $table->string('formatted_address', 500)->nullable()->after('location_method');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['location_method', 'formatted_address']);
        });
    }
};
