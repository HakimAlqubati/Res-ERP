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
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->integer('month')->after('transaction_date');
            $table->integer('year')->after('month');
        });

        // Populate existing records
        DB::statement("UPDATE financial_transactions SET month = MONTH(transaction_date), year = YEAR(transaction_date)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn(['month', 'year']);
        });
    }
};
