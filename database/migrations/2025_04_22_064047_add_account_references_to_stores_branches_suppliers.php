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
        // 🔹 إضافة الحقل للمخازن (stores)
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('inventory_account_id')
                ->nullable()
                ->after('is_central_kitchen')
                ->constrained('accounts')
                ->nullOnDelete();
        });

        // 🔹 إضافة الحقل للفروع (branches)
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('operational_cost_account_id')
                ->nullable()
                ->after('store_id')
                ->constrained('accounts')
                ->nullOnDelete();
        });

        // 🔹 إضافة الحقل للموردين (suppliers)
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('address')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['inventory_account_id']);
            $table->dropColumn('inventory_account_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['operational_cost_account_id']);
            $table->dropColumn('operational_cost_account_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
