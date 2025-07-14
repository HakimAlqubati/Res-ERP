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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // أولاً نحذف العلاقة القديمة (إن وجدت)
            $table->dropForeign(['payroll_id']);
            // ثم نعيد ربطها بالجدول الجديد
            $table->foreign('payroll_id')->references('id')->on('hr_payrolls')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // في حالة الرجوع، فقط احذف العلاقة الجديدة
            $table->dropForeign(['payroll_id']);
            // يمكنك إعادة العلاقة القديمة هنا إذا أردت (اختياري)
            // $table->foreign('payroll_id')->references('id')->on('hr_month_salaries')->onDelete('set null');
        });
    }
};