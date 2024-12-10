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
            $table->dropColumn(['is_monthly', 'used_as_weekend']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_types', function (Blueprint $table) {
            // Re-add the 'is_monthly' and 'used_as_weekend' columns in case of rollback
            $table->boolean('is_monthly')->default(false);
            $table->boolean('used_as_weekend')->default(false);
        });
    }
};
