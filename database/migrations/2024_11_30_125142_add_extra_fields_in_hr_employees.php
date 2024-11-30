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
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->string('tax_identification_number')->nullable()->after('nationality'); 
            
            // Adding the 'MyKad Number' field as nullable string
            $table->string('mykad_number')->nullable()->after('tax_identification_number');
            
            // Adding the 'passport_no' field as nullable string
            $table->string('passport_no')->nullable()->after('mykad_number');

            $table->boolean('has_employee_pass')->default(false)->after('passport_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('tax_identification_number');
            $table->dropColumn('mykad_number');
            $table->dropColumn('passport_no');
        });
    }
};
