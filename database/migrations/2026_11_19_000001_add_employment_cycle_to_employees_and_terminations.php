<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('hr_employees', 'employment_cycle')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->unsignedInteger('employment_cycle')->default(1)->after('active');
            });
        }

        if (! Schema::hasColumn('hr_employee_service_terminations', 'employment_cycle')) {
            Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
                $table->unsignedInteger('employment_cycle')->default(1)->after('employee_id');
                $table->index(
                    ['employee_id', 'employment_cycle', 'status'],
                    'hr_est_emp_cycle_status_idx'
                );
            });
        }

        // Backfill already-rehired active employees who still carry an earlier approved termination
        $rehiredEmployeeIds = DB::table('hr_employees as e')
            ->where('e.active', 1)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('hr_employee_service_terminations as t')
                    ->whereColumn('t.employee_id', 'e.id')
                    ->where('t.status', 'approved')
                    ->whereNull('t.deleted_at');
            })
            ->pluck('e.id');

        if ($rehiredEmployeeIds->isNotEmpty()) {
            DB::table('hr_employees')
                ->whereIn('id', $rehiredEmployeeIds)
                ->update(['employment_cycle' => 2]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('hr_employee_service_terminations', 'employment_cycle')) {
            Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
                $table->dropIndex('hr_est_emp_cycle_status_idx');
                $table->dropColumn('employment_cycle');
            });
        }

        if (Schema::hasColumn('hr_employees', 'employment_cycle')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->dropColumn('employment_cycle');
            });
        }
    }
};
