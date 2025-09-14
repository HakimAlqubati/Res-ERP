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
            $table->text('response_message')->nullable()->after('face_added');
        });
    }

    public function down()
    {
        Schema::table('hr_employee_face_data', function (Blueprint $table) {
            $table->dropColumn('response_message');
        });
    }
};