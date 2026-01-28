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
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_advance_requests', 'code')) {
                $table->string('code')->unique()->after('id');
            }
            if (!Schema::hasColumn('hr_advance_requests', 'remaining_total')) {
                $table->decimal('remaining_total', 12, 2)->default(0)->after('advance_amount');
            }
            if (!Schema::hasColumn('hr_advance_requests', 'paid_installments')) {
                $table->unsignedInteger('paid_installments')->default(0)->after('remaining_total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            if (Schema::hasColumn('hr_advance_requests', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('hr_advance_requests', 'remaining_total')) {
                $table->dropColumn('remaining_total');
            }
            if (Schema::hasColumn('hr_advance_requests', 'paid_installments')) {
                $table->dropColumn('paid_installments');
            }
        });
    }
};
