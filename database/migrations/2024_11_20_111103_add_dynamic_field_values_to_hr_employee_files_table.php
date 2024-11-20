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
        Schema::table('hr_employee_files', function (Blueprint $table) {
            $table->json('dynamic_field_values')->nullable()->after('description'); // Add JSON column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_files', function (Blueprint $table) {
            $table->dropColumn('dynamic_field_values'); // Rollback column
        });
    }
};
