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
        Schema::table('hr_service_requests', function (Blueprint $table) {
            $table->boolean('accepted')->default(false)->after('status'); // New field
            $table->unsignedBigInteger('equipment_id')->nullable()->after('accepted'); // New field

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_service_requests', function (Blueprint $table) {
            $table->dropColumn(['equipment_id', 'accepted']);
        });
    }
};
