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
            $table->string('code')->unique()->after('id'); 
            $table->decimal('remaining_total', 12, 2)->default(0)->after('advance_amount');
            $table->unsignedInteger('paid_installments')->default(0)->after('remaining_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_advance_requests', function (Blueprint $table) {
            $table->dropColumn(['code', 'remaining_total', 'paid_installments']);
        });
    }
};
