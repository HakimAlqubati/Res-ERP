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
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->enum('condition_applied_v2', [
                'all',
                'citizen_employee',
                'citizen_employee_and_foreign_has_emp_pass'
            ])->default('all')->after('condition_applied'); // Adjust the 'after' column position if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('condition_applied_v2');
        });
    }
};
