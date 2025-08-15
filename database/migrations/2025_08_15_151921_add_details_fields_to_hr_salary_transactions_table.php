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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->decimal('qty', 10, 2)->nullable()->after('operation');   // الكمية (ساعات/أيام/…)
            $table->decimal('rate', 12, 4)->nullable()->after('qty');        // سعر الوحدة
            $table->decimal('multiplier', 6, 3)->nullable()->after('rate');  // المعامل (مثل 1.5)
      
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropColumn(['qty', 'rate', 'multiplier']);

        });
    }
};
