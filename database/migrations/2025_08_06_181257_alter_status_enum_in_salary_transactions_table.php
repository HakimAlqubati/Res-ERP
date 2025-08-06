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
        // ⚠️ يجب استخدام raw SQL لتعديل ENUM في MySQL
        DB::statement("ALTER TABLE `hr_salary_transactions` 
            MODIFY COLUMN `status` ENUM('approved', 'pending', 'rejected', 'deferred') 
            NOT NULL DEFAULT 'approved'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `hr_salary_transactions` 
            MODIFY COLUMN `status` ENUM('approved', 'pending', 'rejected') 
            NOT NULL DEFAULT 'approved'");
    }
};
