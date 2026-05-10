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
        Schema::disableForeignKeyConstraints();

        // 1. Update existing records to round the values
        \Illuminate\Support\Facades\DB::table('hr_leave_balances')->update([
            'entitled_days' => \Illuminate\Support\Facades\DB::raw('ROUND(entitled_days)'),
            'supposed_days' => \Illuminate\Support\Facades\DB::raw('ROUND(supposed_days)'),
            'used_days' => \Illuminate\Support\Facades\DB::raw('ROUND(used_days)'),
            'pending_days' => \Illuminate\Support\Facades\DB::raw('ROUND(pending_days)'),
        ]);

        // 2. Change columns to integer
        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->integer('entitled_days')->default(0)->change();
            $table->integer('supposed_days')->default(0)->change();
            $table->integer('used_days')->default(0)->change();
            $table->integer('pending_days')->default(0)->change();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('hr_leave_balances', function (Blueprint $table) {
            $table->decimal('entitled_days', 8, 2)->default(0)->change();
            $table->double('supposed_days')->default(0)->change();
            $table->decimal('used_days', 8, 2)->default(0)->change();
            $table->decimal('pending_days', 8, 2)->default(0)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};
