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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('transactionable_id')->nullable()->after('purchase_invoice_id');
            $table->string('transactionable_type')->nullable()->after('purchase_invoice_id');
            $table->index(['transactionable_type', 'transactionable_id'], 'transactionable_index'); // Shorter index name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('transactionable_index'); // Drop the custom index
            $table->dropColumn(['transactionable_id', 'transactionable_type']);
        });
    }
};
