<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw statement to modify the enum column because Doctrine/Schema builder doesn't support changing ENUM values easily
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE hr_deductions MODIFY COLUMN condition_applied_v2 ENUM('all', 'citizen_employee', 'citizen_employee_and_foreign_has_emp_pass', 'foreign_has_emp_pass') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original enum values
        // WARNING: This might fail or truncate data if there are rows with 'foreign_has_emp_pass'
        // Ideally, we should update those rows first before altering the table back.

        // providing a fallback for the new value before reverting
        \Illuminate\Support\Facades\DB::table('hr_deductions')
            ->where('condition_applied_v2', 'foreign_has_emp_pass')
            ->update(['condition_applied_v2' => 'all']);

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE hr_deductions MODIFY COLUMN condition_applied_v2 ENUM('all', 'citizen_employee', 'citizen_employee_and_foreign_has_emp_pass') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all'");
    }
};
