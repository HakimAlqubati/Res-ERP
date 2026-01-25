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
            $table->string('halal_logo')->nullable()->after('allergen_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_halal_certificates', function (Blueprint $table) {
            $table->dropColumn('halal_logo');
        });
    }
};
