<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            // 1. حذف القيد الخاص بالمفتاح الأجنبي أولاً
            // ملاحظة: تأكد من اسم المفتاح الأجنبي الصحيح في قاعدة بياناتك
            // عادة يكون: table_column_foreign
            $table->dropForeign(['branch_id']);

            // 2. الآن يمكنك حذف الفهرس بأمان
            $table->dropUnique('uniq_payrun_branch_year_month');

            // 3. (اختياري) إعادة بناء المفتاح الأجنبي إذا كنت ستبقي العمود
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // استعادة الوضع السابق في حالة التراجع
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            $table->unique(['branch_id', 'year', 'month'], 'uniq_payrun_branch_year_month');
        });
    }
};
