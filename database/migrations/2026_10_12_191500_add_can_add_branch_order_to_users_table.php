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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_add_branch_order')->default(false)->after('active');
        });

        // ترحيل البيانات الحالية من جدول hr_employees للمستخدمين المرتبطين
        if (Schema::hasTable('hr_employees') && Schema::hasColumn('hr_employees', 'can_add_branch_order')) {
            DB::statement("
                UPDATE users u
                INNER JOIN hr_employees e ON e.user_id = u.id
                SET u.can_add_branch_order = e.can_add_branch_order
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_add_branch_order');
        });
    }
};
