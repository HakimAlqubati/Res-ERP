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
            $table->boolean('discount_exception_if_absent')->after('salary')->default(0);
            $table->boolean('discount_exception_if_attendance_late')->after('salary')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['discount_exception_if_attendance_late', 'discount_exception_if_absent']);
        });
    }
};
