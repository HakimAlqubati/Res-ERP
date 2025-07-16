<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->dropUnique('unique_employee_period_day');
        });
    }

    public function down()
    {
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_id', 'day_of_week'], 'unique_employee_period_day');
        });
    }
};