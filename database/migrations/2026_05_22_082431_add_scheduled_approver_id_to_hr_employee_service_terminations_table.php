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
            // The user designated to be recorded as approver when the cron job auto-approves this request.
            $table->foreignId('scheduled_approver_id')->nullable()->constrained('users')->nullOnDelete()->after('auto_approve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
            $table->dropForeign(['scheduled_approver_id']);
            $table->dropColumn('scheduled_approver_id');
        });
    }
};
