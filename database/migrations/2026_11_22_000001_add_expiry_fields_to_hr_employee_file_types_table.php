<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee_file_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('hr_employee_file_types', 'has_expiry')) {
                $table->boolean('has_expiry')->default(false)->after('is_required');
            }

            if (! Schema::hasColumn('hr_employee_file_types', 'expiry_alert_days')) {
                $table->unsignedSmallInteger('expiry_alert_days')->nullable()->default(30)->after('has_expiry');
            }

            $table->index(['active', 'has_expiry'], 'idx_emp_file_types_active_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_file_types', function (Blueprint $table): void {
            $table->dropIndex('idx_emp_file_types_active_expiry');

            $columns = array_values(array_filter(
                ['has_expiry', 'expiry_alert_days'],
                fn (string $column): bool => Schema::hasColumn('hr_employee_file_types', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
