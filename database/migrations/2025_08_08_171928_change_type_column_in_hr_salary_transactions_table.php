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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // تغيير نوع العمود من ENUM إلى string
            $table->string('type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // إذا أردت إرجاعه إلى ENUM (ضع القيم القديمة هنا)
            $table->enum('type', [
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
            ])->change();
        });
    }
};
