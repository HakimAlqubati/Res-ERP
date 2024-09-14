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
        Schema::table('hr_leave_applications', function (Blueprint $table) {
            $table->date('from_date')->after('status');
            $table->date('to_date')->after('status');
            $table->date('days_count')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_applications', function (Blueprint $table) {
            $table->dropColumn(['from_date', 'to_date', 'days_count']);
        });
    }
};
