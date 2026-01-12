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
        Schema::create('acc_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Al-Rajhi Corporate Account"
            $table->string('account_number');
            $table->string('iban')->nullable();

            // Foreign Keys
            $table->foreignId('currency_id')->constrained('acc_currencies')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('acc_accounts')->cascadeOnDelete();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_bank_accounts');
    }
};
