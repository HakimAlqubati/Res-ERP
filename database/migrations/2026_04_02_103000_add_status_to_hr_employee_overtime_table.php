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
        Schema::table('hr_employee_overtime', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('reason');
        });

        // Migrate existing records based on the boolean field
        DB::table('hr_employee_overtime')
            ->where('approved', 1)
            ->update(['status' => 'approved']);
            
        DB::table('hr_employee_overtime')
            ->where('approved', 0)
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_overtime', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
