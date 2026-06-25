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
        Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
            // Indicates that the cron job should auto-approve this request on the termination date.
            $table->boolean('auto_approve')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
            $table->dropColumn('auto_approve');
        });
    }
};
