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
        Schema::create('acc_cash_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Main Showroom Fund", "POS Drawer #1"

            // Foreign Keys
            $table->foreignId('currency_id')->constrained('acc_currencies')->cascadeOnDelete();
            $table->foreignId('gl_account_id')->constrained('acc_accounts')->cascadeOnDelete();
            $table->foreignId('keeper_id')->nullable()->constrained('users')->nullOnDelete();

            // Control Limits
            $table->decimal('max_limit', 20, 4)->default(0);

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
        Schema::dropIfExists('acc_cash_boxes');
    }
};
