<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * هذا الجدول يُوثّق كيف تم توزيع عبء راتب الموظف بين الفروع
     * في حالة انتقاله بين الفروع خلال فترة الراتب.
     * لا يؤثر على حساب الراتب — هو طبقة محاسبية بحتة.
     */
    public function up(): void
    {
        Schema::create('hr_payroll_branch_splits', function (Blueprint $table) {
            $table->id();

            // الراتب الذي ينتمي إليه هذا التوزيع
            $table->foreignId('payroll_id')
                  ->constrained('hr_payrolls')
                  ->onDelete('cascade');

            // الموظف
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')
                  ->references('id')->on('hr_employees')
                  ->onDelete('cascade');

            // الفرع الذي يتحمل هذه الحصة من الراتب
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('restrict');

            // نطاق الفترة التي يتحملها هذا الفرع
            $table->date('from_date');
            $table->date('to_date');

            // أيام هذا الفرع من إجمالي الشهر
            $table->unsignedSmallInteger('days_count');
            $table->unsignedSmallInteger('total_days');

            // النسبة والمبلغ المخصص لهذا الفرع
            $table->decimal('ratio', 8, 4);           // مثال: 0.3226
            $table->decimal('allocated_amount', 12, 2); // مثال: 967.74

            // الوضع المستخدم وقت الحساب (للتتبع التاريخي)
            // القيم: previous_branch | pro_rated | new_branch
            $table->string('liability_mode', 30)->default('pro_rated');

            $table->timestamps();

            // فهرس سريع للبحث عن splits حسب الفرع أو الراتب
            $table->index(['payroll_id', 'branch_id']);
            $table->index(['employee_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_branch_splits');
    }
};
