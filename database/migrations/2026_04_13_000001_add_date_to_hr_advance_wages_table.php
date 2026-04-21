<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_advance_wages', function (Blueprint $table) {
            $table->date('date')->nullable()->after('month');
        });
    }

    public function down(): void
    {
        Schema::table('hr_advance_wages', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
