<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_prices', function (Blueprint $table) {
            // $table->dropColumn(['show_in_invoices', 'use_in_orders']); // حذف الحقول القديمة
            $table->enum('usage_scope', ['all', 'supply_only', 'out_only', 'manufacturing_only', 'none'])
                ->default('all')
                ->after('minimum_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_prices', function (Blueprint $table) {
            // إعادة الحقول القديمة
            // $table->boolean('show_in_invoices')->default(true);
            // $table->boolean('use_in_orders')->default(true);

            // حذف الحقل الجديد
            $table->dropColumn('usage_scope');
        });
    }
};