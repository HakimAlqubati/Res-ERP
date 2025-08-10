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
            if (!Schema::hasColumn('hr_salary_transactions', 'payroll_id')) {
                $table->unsignedBigInteger('payroll_id')->after('id');
                $table->foreign('payroll_id')
                    ->references('id')->on('hr_payrolls')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            } else {
                $table->unsignedBigInteger('payroll_id')->nullable(false)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
            $table->dropColumn('payroll_id');
        });
    }
};
