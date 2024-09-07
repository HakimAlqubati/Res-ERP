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
        Schema::table('users', function (Blueprint $table) {
            $table->text('avatar')->nullable()->after('email')->default('users/default/avatar.png');
        });
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->text('avatar')->nullable()->after('email')->default('employees/default/avatar.png');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
