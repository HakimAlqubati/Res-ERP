<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restores any soft-deleted child detail records where the parent employee application is active (restored).
     */
    public function up(): void
    {
        DB::table('hr_missed_check_in_requests')
            ->join('hr_employee_applications', 'hr_missed_check_in_requests.application_id', '=', 'hr_employee_applications.id')
            ->whereNull('hr_employee_applications.deleted_at')
            ->whereNotNull('hr_missed_check_in_requests.deleted_at')
            ->update(['hr_missed_check_in_requests.deleted_at' => null]);

        DB::table('hr_missed_check_out_requests')
            ->join('hr_employee_applications', 'hr_missed_check_out_requests.application_id', '=', 'hr_employee_applications.id')
            ->whereNull('hr_employee_applications.deleted_at')
            ->whereNotNull('hr_missed_check_out_requests.deleted_at')
            ->update(['hr_missed_check_out_requests.deleted_at' => null]);

        DB::table('hr_employee_meal_requests')
            ->join('hr_employee_applications', 'hr_employee_meal_requests.application_id', '=', 'hr_employee_applications.id')
            ->whereNull('hr_employee_applications.deleted_at')
            ->whereNotNull('hr_employee_meal_requests.deleted_at')
            ->update(['hr_employee_meal_requests.deleted_at' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as this is a data integrity cleanup
    }
};
