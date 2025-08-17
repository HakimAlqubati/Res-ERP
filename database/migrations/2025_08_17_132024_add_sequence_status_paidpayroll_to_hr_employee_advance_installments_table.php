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
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            // رقم القسط
            $table->unsignedInteger('sequence')->after('application_id');

            // حالة القسط
            $table->enum('status', ['scheduled', 'paid', 'skipped', 'cancelled'])
                  ->default('scheduled')
                  ->after('due_date');

            // الربط مع الراتب الذي خصم القسط
            $table->unsignedBigInteger('paid_payroll_id')->nullable()->after('paid_date');

            // FK للربط مع جدول الرواتب
            $table->foreign('paid_payroll_id')->references('id')->on('hr_payrolls')->nullOnDelete();

            // فهرس للتسلسل داخل نفس السلفة
            $table->unique(['application_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'sequence']);
            $table->dropForeign(['paid_payroll_id']);
            $table->dropColumn(['sequence', 'status', 'paid_payroll_id']);
        });
    }
};
