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
            $table->decimal('quantity', 10, 2)->change();
            $table->decimal('package_size', 10, 2)->default(1)->change();
        });

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
            $table->decimal('package_size', 10, 2)->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change(); // إذا كان السابق float يمكن تغييره هنا
            $table->double('package_size')->nullable()->change();
        });

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->double('package_size')->nullable()->change();
        });
    }
};
