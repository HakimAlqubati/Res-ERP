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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // في بعض الجداول قد يكون اسم القيد مختلفًا؛
            // إن كنت لا تعرفه، نفّذ describe أو استخدم أمر SHOW CREATE TABLE لمعرفة الاسم الدقيق.
            // سنحاول إسقاط أي FK معروف ثم نعيد إنشاءه بكاسكيد.

            // جرّب إسقاط FK باسم قياسي إن كان موجودًا:
            try {
                $table->dropForeign(['payroll_id']);
            } catch (\Throwable $e) {
                // تجاهل لو لم يوجد
            }

            // لو العمود غير موجود أساسًا أنشئه:
            if (!Schema::hasColumn('hr_salary_transactions', 'payroll_id')) {
                $table->unsignedBigInteger('payroll_id')->after('id');
            }

            // إعادة إنشاء المفتاح الأجنبي مع CASCADE
            $table->foreign('payroll_id')
                ->references('id')
                ->on('hr_payrolls')
                ->onDelete('cascade'); // مهم: الكاسكيد عند الحذف
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // إسقاط المفتاح الأجنبي بالكاسكيد
            try {
                $table->dropForeign(['payroll_id']);
            } catch (\Throwable $e) {
                // تجاهل لو لم يوجد
            }

            // إن كنت أضفت العمود في up() ولم يكن موجودًا من قبل وتريد التراجع:
            // علّق السطر التالي إن كان العمود أصلاً موجودًا قبل هذه الهجرة.
            // $table->dropColumn('payroll_id');
        });
    }
};
