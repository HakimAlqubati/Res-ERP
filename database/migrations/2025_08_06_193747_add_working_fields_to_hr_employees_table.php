<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   
     public function up(): void {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('working_days')->nullable()->after('salary'); 
        });
    }

    public function down(): void {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['working_days']);
        });
    }
};
