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
        Schema::rename('hr_monthly_salary_allowances_details', 'hr_monthly_salary_increases_details');

        Schema::table('hr_monthly_salary_increases_details', function (Blueprint $table) {
            // Remove the specified fields
            $table->dropColumn([
                'allowance_id',
                'allowance_name',
                'allowance_amount',
                'is_percentage',
                'amount_value',
                'percentage_value',
            ]);

            // Add new fields
            $table->enum('type', ['bonus', 'incentive', 'allowance'])->after('employee_id'); // Add after employee_id
            $table->string('name')->after('type'); // Add after type
            $table->decimal('amount', 10, 2)->after('name'); // Add after name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_monthly_salary_increases_details', function (Blueprint $table) {
            // Reverse changes in the down method
            $table->decimal('allowance_amount', 10, 2)->nullable();
            $table->boolean('is_percentage')->default(false);
            $table->decimal('amount_value', 10, 2)->nullable();
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->unsignedBigInteger('allowance_id')->nullable();
            $table->string('allowance_name')->nullable();
        });

        Schema::rename('hr_monthly_salary_increases_details', 'hr_monthly_salary_allowances_details');
   
    }
};
