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
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->boolean('has_cap')->default(false)->after('employer_amount');
            $table->decimal('cap_value', 12, 2)->nullable()->after('has_cap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn(['has_cap', 'cap_value']);
        });
    }
};
