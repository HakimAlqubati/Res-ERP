<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ترحيل البيانات من عمود assigned_to إلى جدول hr_service_request_employees
     * ثم حذف العمود القديم.
     */
    public function up(): void
    {
        // إذا كان العمود محذوفاً مسبقاً (بيئة multi-tenant أو إعادة تشغيل) → تخطّ
        if (! Schema::hasColumn('hr_service_requests', 'assigned_to')) {
            return;
        }

        // --- 1. ترحيل البيانات القديمة ---
        DB::table('hr_service_requests')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->each(function ($row) {
                $employeeExists = DB::table('hr_employees')
                    ->where('id', $row->assigned_to)
                    ->exists();

                if ($employeeExists) {
                    DB::table('hr_service_request_employees')->insertOrIgnore([
                        'service_request_id' => $row->id,
                        'employee_id'        => $row->assigned_to,
                        'is_primary'         => true,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            });

        // --- 2. حذف العمود القديم ---
        Schema::table('hr_service_requests', function (Blueprint $table) {
            $table->dropColumn('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     * إعادة عمود assigned_to واستعادة أول موظف رئيسي لكل طلب.
     */
    public function down(): void
    {
        // استعادة العمود
        Schema::table('hr_service_requests', function (Blueprint $table) {
            $table->bigInteger('assigned_to')->nullable()->after('branch_area_id');
        });

        // استعادة البيانات من الموظف الرئيسي
        DB::table('hr_service_request_employees')
            ->where('is_primary', true)
            ->orderBy('service_request_id')
            ->each(function ($row) {
                DB::table('hr_service_requests')
                    ->where('id', $row->service_request_id)
                    ->update(['assigned_to' => $row->employee_id]);
            });
    }
};
