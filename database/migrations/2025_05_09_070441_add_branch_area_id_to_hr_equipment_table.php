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
        Schema::table('hr_equipment', function (Blueprint $table) {
            $table->foreignId('branch_area_id')
                ->nullable()
                ->constrained('branch_areas')
                ->nullOnDelete()
                ->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment', function (Blueprint $table) {
            $table->dropForeign(['branch_area_id']);
            $table->dropColumn('branch_area_id');
        });
    }
};
