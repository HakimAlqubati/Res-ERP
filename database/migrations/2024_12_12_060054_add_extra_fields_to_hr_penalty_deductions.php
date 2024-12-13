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
        Schema::table('hr_penalty_deductions', function (Blueprint $table) {
            $table->decimal('percentage', 5, 2)->nullable()->after('deduction_type');
            $table->date('date')->default(now()->toDateString())->after('percentage');
            $table->timestamp('approved_at')->nullable()->after('date');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_penalty_deductions', function (Blueprint $table) {
            //
        });
    }
};
