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
        Schema::table('hr_month_salaries', function (Blueprint $table) {
            $table->bigInteger('created_by')->nullable()->after('end_month');
            
            $table->dropColumn('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_month_salaries', function (Blueprint $table) {
            // Rollback 'month' column
            $table->string('month')->nullable(); // Adjust the type based on your original definition

            // Rollback 'created_by' column
            $table->dropColumn('created_by');
        });
    }
};
