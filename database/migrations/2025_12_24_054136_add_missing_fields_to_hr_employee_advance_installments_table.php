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
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            // Direct link to advance request
            if (!Schema::hasColumn('hr_employee_advance_installments', 'advance_request_id')) {
                $table->foreignId('advance_request_id')->nullable()->after('application_id')
                    ->constrained('hr_advance_requests')->nullOnDelete();
            }

            // Year and month for easy filtering
            if (!Schema::hasColumn('hr_employee_advance_installments', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('hr_employee_advance_installments', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('year');
            }

            // Notes for documentation
            if (!Schema::hasColumn('hr_employee_advance_installments', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }

            // Skipped/Cancelled reasons
            if (!Schema::hasColumn('hr_employee_advance_installments', 'skipped_reason')) {
                $table->string('skipped_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('hr_employee_advance_installments', 'cancelled_reason')) {
                $table->string('cancelled_reason')->nullable()->after('skipped_reason');
            }

            // Auditing fields for cancellation
            if (!Schema::hasColumn('hr_employee_advance_installments', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_reason')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('hr_employee_advance_installments', 'cancelled_at')) {
                $table->datetime('cancelled_at')->nullable()->after('cancelled_by');
            }

            // Manual payment tracking
            if (!Schema::hasColumn('hr_employee_advance_installments', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_date')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('hr_employee_advance_installments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('paid_by')
                    ->comment('payroll, cash, bank_transfer');
            }

            // Original amount before any adjustments
            if (!Schema::hasColumn('hr_employee_advance_installments', 'original_amount')) {
                $table->decimal('original_amount', 12, 2)->nullable()->after('installment_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_advance_installments', function (Blueprint $table) {
            $columns = [
                'advance_request_id',
                'year',
                'month',
                'notes',
                'skipped_reason',
                'cancelled_reason',
                'cancelled_by',
                'cancelled_at',
                'paid_by',
                'payment_method',
                'original_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('hr_employee_advance_installments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
