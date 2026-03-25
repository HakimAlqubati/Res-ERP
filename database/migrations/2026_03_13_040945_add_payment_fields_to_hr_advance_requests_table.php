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
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            $table->string('payment_method')->nullable();
            $table->string('bank_account_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_account_number']);
        });
    }
};
