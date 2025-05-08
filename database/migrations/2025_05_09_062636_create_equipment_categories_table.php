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
        Schema::create('hr_equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('equipment_code_start_with')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('hr_equipment_categories')->nullOnDelete();
            $table->dropColumn('equipment_code_start_with');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment_types', function (Blueprint $table) {
            $table->string('equipment_code_start_with')->nullable();
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('hr_equipment_categories');
    }
};
