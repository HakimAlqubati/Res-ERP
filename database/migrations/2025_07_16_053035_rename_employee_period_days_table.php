<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
    {
        Schema::rename('employee_period_days', 'hr_employee_period_days');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('hr_employee_period_days', 'employee_period_days');
    }
};