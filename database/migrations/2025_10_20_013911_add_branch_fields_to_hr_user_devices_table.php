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
        Schema::table('hr_user_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->after('user_id');
            $table->unsignedBigInteger('branch_area_id')->nullable()->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_user_devices', function (Blueprint $table) {
            if (Schema::hasColumn('hr_user_devices', 'branch_area_id')) {
                $table->dropColumn('branch_area_id');
            }
            if (Schema::hasColumn('hr_user_devices', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};
