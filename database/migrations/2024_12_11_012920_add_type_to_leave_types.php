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
        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->enum('type', ['yearly', 'monthly', 'weekly', 'special'])->default('yearly')->after('used_as_weekend');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
