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
        Schema::create('pos_import_data_details', function (Blueprint $table) {
            $table->id();
            // رأس الاستيراد
            $table->foreignId('pos_import_data_id')
                ->constrained('pos_import_data')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // لو تم حذف الرأس نهائياً (forceDelete) تُحذف التفاصيل

            // بنود الملف
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 18, 6)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // تسريع الاستعلامات ومنع التكرار غير المقصود لنفس (product+unit) داخل نفس الملف
            $table->unique(['pos_import_data_id', 'product_id', 'unit_id'], 'uidx_pos_imp_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_import_data_details');
    }
};
