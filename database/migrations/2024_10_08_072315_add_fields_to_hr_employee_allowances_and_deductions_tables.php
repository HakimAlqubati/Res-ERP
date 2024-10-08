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
        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->boolean('is_percentage')->default(false)->after('amount');
            $table->decimal('percentage', 8, 2)->nullable()->after('is_percentage');
            $table->decimal('amount')->nullable()->change();
        });

        Schema::table('hr_employee_deductions', function (Blueprint $table) {
            $table->boolean('is_percentage')->default(false)->after('amount');
            $table->decimal('percentage', 8, 2)->nullable()->after('is_percentage');
            $table->decimal('amount')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->dropColumn(['is_percentage', 'percentage']);
            $table->decimal('amount')->nullable(false)->change(); // Revert amount to not nullable
        });

        Schema::table('hr_employee_deductions', function (Blueprint $table) {
            $table->dropColumn(['is_percentage', 'percentage']);
            $table->decimal('amount')->nullable(false)->change(); // Revert amount to not nullable
        });
    }
};
