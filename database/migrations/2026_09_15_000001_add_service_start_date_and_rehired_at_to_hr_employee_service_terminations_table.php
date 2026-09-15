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
        Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employee_service_terminations', 'service_start_date')) {
                $table->date('service_start_date')->nullable()->after('termination_date');
            }
            if (!Schema::hasColumn('hr_employee_service_terminations', 'rehired_at')) {
                $table->timestamp('rehired_at')->nullable()->after('rejected_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_service_terminations', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('hr_employee_service_terminations', 'service_start_date')) {
                $columnsToDrop[] = 'service_start_date';
            }
            if (Schema::hasColumn('hr_employee_service_terminations', 'rehired_at')) {
                $columnsToDrop[] = 'rehired_at';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
