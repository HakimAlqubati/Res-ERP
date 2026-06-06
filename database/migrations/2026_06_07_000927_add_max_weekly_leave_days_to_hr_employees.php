<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * يُضيف حقل max_weekly_leave_days للموظف لتخصيص الحد الأقصى للإجازة الأسبوعية الشهرية.
     * القيمة null تعني استخدام الإعداد الافتراضي للنظام (4 أيام).
     * أي قيمة صحيحة تُطبَّق كحد أقصى مخصص لهذا الموظف تحديداً.
     */
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_weekly_leave_days')
                  ->nullable()
                  ->default(null)
                  ->after('has_auto_weekly_leave')
                  ->comment('حد أقصى مخصص لأيام الإجازة الأسبوعية الشهرية — null = استخدم الافتراضي (4 أيام)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('max_weekly_leave_days');
        });
    }
};
