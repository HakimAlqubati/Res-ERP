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
        Schema::table('branches', function (Blueprint $table) {
            // DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('branch', 'central_kitchen', 'hq', 'popup', 'reseller') NOT NULL DEFAULT 'branch'");
            DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('branch', 'central_kitchen', 'hq', 'popup', 'reseller')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            DB::statement("ALTER TABLE branches MODIFY COLUMN type ENUM('branch', 'central_kitchen', 'hq', 'popup')");
        });
    }
};
