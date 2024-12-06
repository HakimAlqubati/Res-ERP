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
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            // Add 'year' and 'month' columns as string (VARCHAR)
            $table->string('year', 4)->nullable()->after('reason'); // Adjust 'after' column if needed
            $table->string('month', 2)->nullable()->after('year'); // Adjust 'after' column if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            // Drop 'year' and 'month' columns
            $table->dropColumn(['year', 'month']);
        });
    }
};
