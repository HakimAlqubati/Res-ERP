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
            // Add the 'nationalities_applied' as a nullable JSON column
            $table->json('nationalities_applied')->nullable()->after('is_percentage');

            // Add the 'condition_applied' as an ENUM with the default value 'all'
            $table->enum('condition_applied', ['all', 'specified_nationalities', 'specified_nationalties_and_emp_has_pass'])
                ->default('all')->after('nationalities_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('nationalities_applied');
            $table->dropColumn('condition_applied');
        });
    }
};
