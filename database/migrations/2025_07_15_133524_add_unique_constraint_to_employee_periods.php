<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
            DB::statement('
            DELETE t1 FROM hr_employee_periods t1
            INNER JOIN hr_employee_periods t2
            WHERE
                t1.id > t2.id
                AND t1.employee_id = t2.employee_id
                AND t1.period_id = t2.period_id
        ');
        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->unique(['employee_id', 'period_id'], 'unique_employee_period');
        });
    }

    public function down()
    {
        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->dropUnique('unique_employee_period');
        });
    }
};