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
        Schema::table('hr_task_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('morphable_id');
            $table->string('morphable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_task_steps', function (Blueprint $table) {
            $table->dropColumn(['morphable_id', 'morphable_type']);
        });
    }
};
