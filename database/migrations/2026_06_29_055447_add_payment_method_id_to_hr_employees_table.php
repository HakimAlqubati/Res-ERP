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
            $table->foreignId('payment_method_id')->nullable()->constrained('hr_employee_payment_method')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
