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
        Schema::table('hr_tasks', function (Blueprint $table) {
            $table->enum('task_status', ['pending', 'in_progress', 'review', 'cancelled', 'failed',  'completed'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_tasks', function (Blueprint $table) {
            $table->dropColumn('task_status');
        });
    }
};
