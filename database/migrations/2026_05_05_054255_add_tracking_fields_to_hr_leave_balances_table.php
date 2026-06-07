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
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            // إجمالي الرصيد المستحق
            $table->decimal('entitled_days', 8, 2)->default(0)->after('balance');

            // الأيام المستهلكة (الطلبات المعتمدة)
            $table->decimal('used_days', 8, 2)->default(0)->after('entitled_days');

            // الأيام المحجوزة (الطلبات المعلقة)
            $table->decimal('pending_days', 8, 2)->default(0)->after('used_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->dropColumn(['entitled_days', 'used_days', 'pending_days']);
        });
    }
};
