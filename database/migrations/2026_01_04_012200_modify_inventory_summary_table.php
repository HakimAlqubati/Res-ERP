<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_summary', function (Blueprint $table) {
            // حذف الحقول غير المطلوبة
            $table->dropColumn(['total_in', 'total_out', 'last_in_price', 'last_in_transaction_id']);

            // إضافة حقل package_size
            $table->float('package_size')->default(1)->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_summary', function (Blueprint $table) {
            $table->decimal('total_in', 16, 4)->default(0);
            $table->decimal('total_out', 16, 4)->default(0);
            $table->decimal('last_in_price', 16, 6)->nullable();
            $table->unsignedBigInteger('last_in_transaction_id')->nullable();
            $table->dropColumn('package_size');
        });
    }
};
