<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing NULL values to 1 (active)
        DB::table('users')->whereNull('active')->update(['active' => 1]);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('active')->default(1)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('active')->default(null)->nullable()->change();
        });
    }
};
