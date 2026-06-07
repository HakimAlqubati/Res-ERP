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
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            // The full entitlement as defined on the leave type (before any proration)
            $table->double('supposed_days')->default(0)->after('entitled_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->dropColumn('supposed_days');
        });
    }
};
