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
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            //
        });
    }
};
