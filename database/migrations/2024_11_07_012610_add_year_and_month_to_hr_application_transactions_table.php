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
        Schema::table('hr_application_transactions', function (Blueprint $table) {
            $table->integer('year')->nullable()->after('transaction_type_id');
            $table->integer('month')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_application_transactions', function (Blueprint $table) {
            $table->dropColumn(['year', 'month']);
        });
    }
};
