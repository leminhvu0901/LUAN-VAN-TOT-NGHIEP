<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('material_imports', function (Blueprint $table) {
            $table->decimal('remaining_quantity', 10, 2)->after('quantity')->nullable();
        });
    }

    public function down()
    {
        Schema::table('material_imports', function (Blueprint $table) {
            $table->dropColumn('remaining_quantity');
        });
    }
};
