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
            $table->string('sub_type', 50)
                  ->nullable()
                  ->after('type'); // يوضع بعد حقل type
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropColumn('sub_type');
        });
    }
};
