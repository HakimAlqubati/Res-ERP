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
        Schema::table('hr_employee_payment_method', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->json('payment_details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_payment_method', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('payment_details');
        });
    }
};
