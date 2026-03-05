<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->boolean('is_mtd_deduction')
                ->default(false)
                ->after('has_brackets')
                ->comment('If true, this deduction applies to any employee with is_mtd_applicable=true, regardless of condition_applied_v2');
        });
    }

    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('is_mtd_deduction');
        });
    }
};
