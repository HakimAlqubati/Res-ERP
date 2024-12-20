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
        return;
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->unique(['employee_id', 'leave_type_id', 'year', 'month'], 'unique_leave_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return;
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->dropUnique('unique_leave_balance');
        });
    }
};
