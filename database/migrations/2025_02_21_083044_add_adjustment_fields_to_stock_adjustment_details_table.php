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
        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->date('adjustment_date')->nullable()->after('id');
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete()->after('adjustment_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('store_id');
            $table->enum('adjustment_type', ['increase', 'decrease'])->after('created_by');
            $table->foreignId('reason_id')->nullable()->constrained('stock_adjustment_reasons')->nullOnDelete()->after('adjustment_type');

            // Drop the foreign key and column for stock_adjustment_id
            $table->dropForeign(['stock_adjustment_id']);
            $table->dropColumn('stock_adjustment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            // Re-add the stock_adjustment_id column
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();


            $table->dropColumn('adjustment_date');
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
            $table->dropColumn('adjustment_type');
            $table->dropForeign(['reason_id']);
            $table->dropColumn('reason_id');
        });
    }
};
