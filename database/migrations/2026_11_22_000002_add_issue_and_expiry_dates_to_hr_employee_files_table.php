<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee_files', function (Blueprint $table): void {
            if (! Schema::hasColumn('hr_employee_files', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('attachment');
            }

            if (! Schema::hasColumn('hr_employee_files', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('issue_date');
            }

            $table->index(['active', 'expiry_date'], 'idx_emp_files_active_expiry');
            $table->index(['employee_id', 'file_type_id'], 'idx_emp_files_emp_type');
            $table->index('file_type_id', 'idx_emp_files_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_files', function (Blueprint $table): void {
            $table->dropIndex('idx_emp_files_active_expiry');
            $table->dropIndex('idx_emp_files_emp_type');
            $table->dropIndex('idx_emp_files_type_id');

            $columns = array_values(array_filter(
                ['issue_date', 'expiry_date'],
                fn (string $column): bool => Schema::hasColumn('hr_employee_files', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
