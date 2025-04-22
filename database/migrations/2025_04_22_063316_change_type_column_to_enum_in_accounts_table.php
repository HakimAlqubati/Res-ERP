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
        Schema::table('accounts', function (Blueprint $table) {
            // حذف الحقل القديم (إن كان موجود)
            $table->dropColumn('type');
        });

        Schema::table('accounts', function (Blueprint $table) {
            // إعادة إضافته كـ enum
            $table->enum('type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'expense'
            ])->default('asset')->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('type')->default('asset')->after('code');
        });
    }
};
