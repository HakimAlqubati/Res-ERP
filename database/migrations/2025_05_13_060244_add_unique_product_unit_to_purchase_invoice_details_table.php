<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->unique(['purchase_invoice_id', 'product_id', 'unit_id'], 'unique_invoice_product_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->dropUnique('unique_invoice_product_unit');
        });
    }
};
