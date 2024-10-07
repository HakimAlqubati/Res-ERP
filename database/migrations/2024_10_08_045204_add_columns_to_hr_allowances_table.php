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
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->boolean('is_specific')->default(false)->after('is_monthly');
            $table->decimal('percentage', 8, 2)->nullable()->after('is_specific');
            $table->decimal('amount', 10, 2)->nullable()->after('percentage');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->dropColumn(['is_specific', 'percentage', 'amount']);

        });
    }
};
