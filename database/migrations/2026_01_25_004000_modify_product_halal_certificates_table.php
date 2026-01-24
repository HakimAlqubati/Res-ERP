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
        Schema::table('product_halal_certificates', function (Blueprint $table) {
            $table->dropColumn(['is_halal_certified', 'halal_certificate_no', 'halal_expiry_date']);
            $table->string('net_weight')->nullable()->after('shelf_life_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_halal_certificates', function (Blueprint $table) {
            $table->boolean('is_halal_certified')->default(false);
            $table->string('halal_certificate_no')->nullable();
            $table->date('halal_expiry_date')->nullable();
            $table->dropColumn('net_weight');
        });
    }
};
