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
        Schema::table('hr_allowances', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_allowances', 'financial_category_id')) {
                $table->foreignId('financial_category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
            }
        });

        Schema::table('hr_monthly_incentives', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_monthly_incentives', 'financial_category_id')) {
                $table->foreignId('financial_category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            if (Schema::hasColumn('hr_allowances', 'financial_category_id')) {
                $table->dropForeign(['financial_category_id']);
                $table->dropColumn('financial_category_id');
            }
        });

        Schema::table('hr_monthly_incentives', function (Blueprint $table) {
            if (Schema::hasColumn('hr_monthly_incentives', 'financial_category_id')) {
                $table->dropForeign(['financial_category_id']);
                $table->dropColumn('financial_category_id');
            }
        });
    }
};
