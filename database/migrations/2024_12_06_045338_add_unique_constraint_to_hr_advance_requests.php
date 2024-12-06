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
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            $table->unique(['application_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'employee_id']);
        });
    }
};
