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
        Schema::create('product_halal_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();

            // Shelf Life (مدة الصلاحية)
            $table->unsignedInteger('shelf_life_value')->nullable()->comment('Shelf life duration value');
            $table->enum('shelf_life_unit', ['day', 'week', 'month', 'year'])->default('month')->comment('Shelf life unit');

            // Halal Certificate (شهادة الحلال)
            $table->boolean('is_halal_certified')->default(false)->comment('Is product halal certified');
            $table->string('halal_certificate_no')->nullable()->comment('Halal certificate number e.g. MS 1500');
            $table->date('halal_expiry_date')->nullable()->comment('Halal certificate expiry date');

            // Allergen Info (معلومات الحساسية)
            $table->text('allergen_info')->nullable()->comment('Allergen information for product label');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_halal_certificates');
    }
};
