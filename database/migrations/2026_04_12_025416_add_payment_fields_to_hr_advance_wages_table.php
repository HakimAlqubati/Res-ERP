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
        Schema::table('hr_advance_wages', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('amount');
            $table->string('bank_account_number')->nullable()->after('payment_method');
            $table->string('transaction_number')->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_advance_wages', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_account_number', 'transaction_number']);
        });
    }
};
