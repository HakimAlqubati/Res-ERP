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
        Schema::table('hr_employee_face_data', function (Blueprint $table) {
            $table->boolean('face_added')->default(false)->after('active');
        });
    }

    public function down()
    {
        Schema::table('hr_employee_face_data', function (Blueprint $table) {
            $table->dropColumn('face_added');
        });
    }
};