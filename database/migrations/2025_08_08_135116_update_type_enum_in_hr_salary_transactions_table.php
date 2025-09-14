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
        DB::statement("ALTER TABLE hr_salary_transactions 
            MODIFY COLUMN type ENUM(
                'salary',
                'allowance',
                'deduction',
                'advance',
                'installment',
                'bonus',
                'overtime',
                'penalty',
                'other',
                'net_salary'
            ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE hr_salary_transactions 
            MODIFY COLUMN type ENUM(
                'salary',
                'allowance',
                'deduction',
                'advance',
                'installment',
                'bonus',
                'overtime',
                'penalty',
                'other'
            ) NOT NULL");
    }
};
