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
        Schema::table('hr_employee_branch_logs', function (Blueprint $table) {
            $table->index(['employee_id', 'start_at', 'end_at'], 'hr_ebl_emp_start_end_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_branch_logs', function (Blueprint $table) {
            $table->dropIndex('hr_ebl_emp_start_end_idx');
        });
    }
};
