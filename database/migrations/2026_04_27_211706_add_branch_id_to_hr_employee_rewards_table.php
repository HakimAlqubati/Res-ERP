<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable branch_id
        Schema::table('hr_employee_rewards', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
        });

        // 2. Fill branch_id from hr_employees.branch_id
        DB::statement('
            UPDATE hr_employee_rewards r
            JOIN hr_employees e ON r.employee_id = e.id
            SET r.branch_id = e.branch_id
        ');

        // 3. Make branch_id required (not nullable)
        Schema::table('hr_employee_rewards', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_rewards', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
