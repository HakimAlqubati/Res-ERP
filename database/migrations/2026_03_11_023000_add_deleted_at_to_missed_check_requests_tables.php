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
        Schema::table('hr_missed_check_in_requests', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_missed_check_in_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
