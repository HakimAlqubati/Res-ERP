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
        Schema::table('acc_journal_entry_lines', function (Blueprint $table) {
            // Add sub-ledger links
            $table->foreignId('bank_account_id')->nullable()->constrained('acc_bank_accounts')->nullOnDelete();
            $table->foreignId('cash_box_id')->nullable()->constrained('acc_cash_boxes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acc_journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['cash_box_id']);
            $table->dropColumn(['bank_account_id', 'cash_box_id']);
        });
    }
};
