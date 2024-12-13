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
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->enum('applied_by', ['employee', 'employer', 'both'])->default('employee')->after('has_brackets');
            $table->decimal('employer_amount', 15, 2)->default(0)->after('applied_by'); // Employer's contribution amount
            $table->decimal('employer_percentage', 5, 2)->default(0)->after('employer_amount'); // Employer's contribution percentage
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('applied_by');
            $table->dropColumn('employer_amount');
            $table->dropColumn('employer_percentage');
        });
    }
};
