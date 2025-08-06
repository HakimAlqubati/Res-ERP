<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'branch_id', 'year', 'month'],
                'unique_payroll_employee_month'
            );
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropUnique('unique_payroll_employee_month');
        });
    }
};
