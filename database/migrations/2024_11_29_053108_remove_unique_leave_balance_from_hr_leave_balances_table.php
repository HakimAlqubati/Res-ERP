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
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->dropUnique('unique_leave_balance');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->unique(['employee_id', 'leave_type_id', 'year']); 
        });
    }
};
