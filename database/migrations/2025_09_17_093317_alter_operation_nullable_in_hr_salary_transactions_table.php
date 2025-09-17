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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE hr_salary_transactions MODIFY operation ENUM('+', '-') NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE hr_salary_transactions MODIFY operation ENUM('+', '-') NOT NULL DEFAULT '+'");
        });
    }
};
