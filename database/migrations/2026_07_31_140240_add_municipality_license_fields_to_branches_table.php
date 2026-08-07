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
        Schema::table('branches', function (Blueprint $table) {
            $table->date('municipality_license_issue_date')->nullable();
            $table->date('municipality_license_end_date')->nullable();
            $table->text('municipality_license_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'municipality_license_issue_date',
                'municipality_license_end_date',
                'municipality_license_notes',
            ]);
        });
    }
};
