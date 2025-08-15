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
            // وحدة الحساب: "hour" | "day" | "piece" | "%" .. إلخ
            $table->string('unit', 16)->nullable()->after('operation');
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
