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
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            // تغيير العمود ليكون NOT NULL
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            // إعادة العمود ليكون Nullable إذا أردت التراجع
            $table->string('name')->nullable()->change();
        });
    }
};
