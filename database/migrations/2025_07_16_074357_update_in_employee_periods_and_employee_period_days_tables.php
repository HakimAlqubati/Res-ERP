<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // حذف start_date و end_date من جدول hr_employee_period_days إذا كانت موجودة
        if (Schema::hasColumn('hr_employee_period_days', 'start_date')) {
            Schema::table('hr_employee_period_days', function (Blueprint $table) {
                $table->dropColumn('start_date');
            });
        }
        if (Schema::hasColumn('hr_employee_period_days', 'end_date')) {
            Schema::table('hr_employee_period_days', function (Blueprint $table) {
                $table->dropColumn('end_date');
            });
        }

        // إضافة start_date و end_date إلى جدول hr_employee_periods إذا لم تكن موجودة
        if (!Schema::hasColumn('hr_employee_periods', 'start_date')) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('period_id');
            });
        }
        if (!Schema::hasColumn('hr_employee_periods', 'end_date')) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            });
        }

        // حذف الـ unique القديم إذا موجود
        $indexes = DB::select("SHOW INDEX FROM hr_employee_periods WHERE Key_name = 'unique_employee_period'");
        if (count($indexes) > 0) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->dropUnique('unique_employee_period');
            });
        }

        // إضافة unique جديد إذا غير موجود
        $indexes = DB::select("SHOW INDEX FROM hr_employee_periods WHERE Key_name = 'unique_employee_period_start_date'");
        if (count($indexes) == 0) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->unique(['employee_id', 'period_id', 'start_date'], 'unique_employee_period_start_date');
            });
        }
    }

    public function down()
    {
        // حذف unique الجديد إذا موجود
        $indexes = DB::select("SHOW INDEX FROM hr_employee_periods WHERE Key_name = 'unique_employee_period_start_date'");
        if (count($indexes) > 0) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->dropUnique('unique_employee_period_start_date');
            });
        }

        // حذف start_date و end_date من hr_employee_periods إذا كانت موجودة
        if (Schema::hasColumn('hr_employee_periods', 'start_date')) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->dropColumn('start_date');
            });
        }
        if (Schema::hasColumn('hr_employee_periods', 'end_date')) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->dropColumn('end_date');
            });
        }

        // إعادة unique القديم إذا لم يكن موجود
        $indexes = DB::select("SHOW INDEX FROM hr_employee_periods WHERE Key_name = 'unique_employee_period'");
        if (count($indexes) == 0) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->unique(['employee_id', 'period_id'], 'unique_employee_period');
            });
        }

        // إعادة start_date و end_date لجدول الأيام إذا لم تكن موجودة
        if (!Schema::hasColumn('hr_employee_period_days', 'start_date')) {
            Schema::table('hr_employee_period_days', function (Blueprint $table) {
                $table->date('start_date')->nullable();
            });
        }
        if (!Schema::hasColumn('hr_employee_period_days', 'end_date')) {
            Schema::table('hr_employee_period_days', function (Blueprint $table) {
                $table->date('end_date')->nullable();
            });
        }
    }
};