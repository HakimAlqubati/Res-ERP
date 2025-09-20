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
            if (!Schema::hasColumn('hr_employee_advance_installments', 'sequence')) {
                $table->unsignedInteger('sequence')->after('application_id');
            }

            if (!Schema::hasColumn('hr_employee_advance_installments', 'status')) {
                $table->enum('status', ['scheduled', 'paid', 'skipped', 'cancelled'])
                    ->default('scheduled')
                    ->after('due_date');
            }

            if (!Schema::hasColumn('hr_employee_advance_installments', 'paid_payroll_id')) {
                $table->unsignedBigInteger('paid_payroll_id')->nullable()->after('paid_date');
                $table->foreign('paid_payroll_id')->references('id')->on('hr_payrolls')->nullOnDelete();
            }

            // الفهرس الفريد
            if (!Schema::hasColumn('hr_employee_advance_installments', 'sequence')) {
                $table->unsignedInteger('sequence')->after('application_id');
                $table->unique(['application_id', 'sequence']);
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employee_advance_installments', 'sequence')) {
                $table->dropUnique(['application_id', 'sequence']);
                $table->dropColumn('sequence');
            }

            if (Schema::hasColumn('hr_employee_advance_installments', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('hr_employee_advance_installments', 'paid_payroll_id')) {
                $table->dropForeign(['paid_payroll_id']);
                $table->dropColumn('paid_payroll_id');
            }
        });
    }
};
