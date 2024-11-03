<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_tasks', function (Blueprint $table) {
            DB::statement("ALTER TABLE hr_tasks MODIFY COLUMN task_status ENUM('new', 'pending', 'in_progress', 'closed', 'rejected') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_tasks', function (Blueprint $table) {
            DB::statement("ALTER TABLE hr_tasks MODIFY COLUMN task_status ENUM('new', 'pending', 'in_progress', 'closed') NOT NULL");
        });
    }
};
