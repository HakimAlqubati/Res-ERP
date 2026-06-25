<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hr_approval_policies', 'final_handler')) {
            return;
        }

        Schema::table('hr_approval_policies', function (Blueprint $table) {
            $table->dropColumn('final_handler');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('hr_approval_policies', 'final_handler')) {
            return;
        }

        Schema::table('hr_approval_policies', function (Blueprint $table) {
            $table->string('final_handler')->nullable();
        });
    }
};
