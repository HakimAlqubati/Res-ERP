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
        Schema::table('user_login_histories', function (Blueprint $table) {
            $table->string('username')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('username');
            $table->string('phonenumber')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_login_histories', function (Blueprint $table) {
            $table->dropColumn(['username', 'email', 'phonenumber']);
        });
    }
};
