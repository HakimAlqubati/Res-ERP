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
            $table->unsignedBigInteger('base_unit_id')->nullable()->after('unit_id');
            $table->decimal('base_quantity', 20, 6)->nullable()->after('quantity');
            $table->decimal('base_unit_package_size', 20, 6)->nullable()->after('base_quantity');

            // Optional: foreign key if you have a units table
            $table->foreign('base_unit_id')->references('id')->on('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['base_unit_package_size']);
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn(['base_unit_id', 'base_quantity']);
        });
    }
};