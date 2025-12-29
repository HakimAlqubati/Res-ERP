<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\SalaryTransaction;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

class PayrollActions
{
    /**
     * Approve Action for PayrollRun table.
     * 
     * Shows a confirmation dialog and updates status to approved.
     * Only visible when status is pending or completed.
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')->button()
            ->label(__('Approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('Approve Payroll'))
            ->modalDescription(__('Are you sure you want to approve this payroll? This will sync it with the financial system.'))
            ->modalSubmitActionLabel(__('Yes, Approve'))
            ->visible(
                fn(PayrollRun $record): bool =>
                in_array($record->status, [PayrollRun::STATUS_PENDING, PayrollRun::STATUS_COMPLETED])
            )
            ->action(function (PayrollRun $record): void {
                // IMPORTANT: Update child Payrolls FIRST before updating PayrollRun
                // Because the Observer on PayrollRun will trigger financial sync
                // which needs the Payrolls to be approved
                $record->payrolls()
                    ->where('status', Payroll::STATUS_PENDING)
                    ->update([
                        'status' => Payroll::STATUS_APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                // Now update the PayrollRun status (this triggers the Observer)
                // Note: Installments are marked as paid in PayrollRunObserver
                $record->update([
                    'status' => PayrollRun::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                Notification::make()
                    ->title(__('Payroll Approved'))
                    ->body(__('Payroll has been approved and synced with financial system.'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Early Installment Payment Action for PayrollRun table.
     * 
     * Allows paying future advance installments while payroll is pending.
     * Creates SalaryTransaction records that will be processed when payroll is approved.
     */
    public static function earlyInstallmentPaymentAction(): Action
    {
        return Action::make('earlyPayment')
            ->button()
            ->label(__('lang.early_installment_payment'))
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn(PayrollRun $record): bool => $record->status === PayrollRun::STATUS_PENDING)
            ->schema(function (PayrollRun $record) {
                return self::buildEarlyPaymentSchema($record);
            })
            ->modalHeading(__('lang.early_installment_payment'))
            ->modalDescription(__('lang.early_installment_payment_description'))
            ->modalSubmitActionLabel(__('lang.add_to_payroll'))
            ->action(function (PayrollRun $record, array $data): void {
                self::processEarlyPayment($record, $data);
            });
    }

    /**
     * Build the schema for early payment modal.
     */
    private static function buildEarlyPaymentSchema(PayrollRun $record): array
    {
        // Get all employee IDs in this payroll run
        $employeeIds = $record->payrolls()->pluck('employee_id')->toArray();

        if (empty($employeeIds)) {
            return [
                Placeholder::make('no_employees')
                    ->label('')
                    ->content(__('lang.no_employees_in_payroll')),
            ];
        }

        // Get the current period end date
        $periodEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $record->year, $record->month)));

        // Find all unpaid installments for employees in this payroll
        // Includes: overdue + current period + future installments
        // Excludes: already scheduled in other payroll runs
        $installments = EmployeeAdvanceInstallment::query()
            ->whereIn('employee_id', $employeeIds)
            ->unpaid()
            ->scheduled()
            ->availableForEarlyPayment()
            ->with(['employee:id,name,employee_no', 'advanceRequest:id,code,advance_amount'])
            ->orderBy('employee_id')
            ->orderBy('due_date')
            ->get();

        if ($installments->isEmpty()) {
            return [
                Placeholder::make('no_installments')
                    ->label('')
                    ->content(__('lang.no_future_installments')),
            ];
        }

        // Build options for checkbox list
        $options = $installments->mapWithKeys(function ($inst) {
            $label = sprintf(
                '%s (%s) - %s - %s - %s',
                $inst->employee->name ?? 'N/A',
                $inst->employee->employee_no ?? 'N/A',
                $inst->advanceRequest->code ?? 'N/A',
                formatMoneyWithCurrency($inst->installment_amount),
                $inst->due_date?->format('Y-m-d') ?? 'N/A'
            );
            return [$inst->id => $label];
        })->toArray();

        return [
            Placeholder::make('info')
                ->label('')
                ->content(__('lang.select_installments_to_pay')),
            CheckboxList::make('installment_ids')
                ->label(__('lang.future_installments'))
                ->options($options)
                ->required()
                ->columns(1)
                ->bulkToggleable()
                ->descriptions(
                    $installments->mapWithKeys(function ($inst) {
                        return [$inst->id => __('lang.sequence') . ': ' . $inst->sequence];
                    })->toArray()
                ),
        ];
    }

    /**
     * Process the early payment action.
     */
    private static function processEarlyPayment(PayrollRun $record, array $data): void
    {
        $installmentIds = $data['installment_ids'] ?? [];

        if (empty($installmentIds)) {
            Notification::make()
                ->title(__('lang.error'))
                ->body(__('lang.no_installments_selected'))
                ->danger()
                ->send();
            return;
        }

        $installments = EmployeeAdvanceInstallment::whereIn('id', $installmentIds)
            ->with(['advanceRequest:id,code'])
            ->get();

        $periodEnd = \Carbon\Carbon::create($record->year, $record->month, 1)->endOfMonth();
        $createdCount = 0;

        foreach ($installments as $installment) {
            // Find the payroll for this employee
            $payroll = $record->payrolls()
                ->where('employee_id', $installment->employee_id)
                ->first();

            if (!$payroll) {
                continue;
            }

            // Check if SalaryTransaction already exists for this installment
            $existingTx = SalaryTransaction::where('reference_type', EmployeeAdvanceInstallment::class)
                ->where('reference_id', $installment->id)
                ->first();

            if ($existingTx) {
                continue;
            }

            // Build description
            $desc = __('lang.early_installment_payment') . ' (' .
                ($installment->advanceRequest->code ?? 'N/A') . ', ' .
                __('lang.sequence') . ' ' . $installment->sequence . ', ' .
                __('lang.due_date') . ' ' . $installment->due_date?->format('Y-m-d') . ')';

            try {
                // Create SalaryTransaction
                SalaryTransaction::create([
                    'employee_id' => $installment->employee_id,
                    'payroll_id' => $payroll->id,
                    'payroll_run_id' => $record->id,
                    'date' => $periodEnd->toDateString(),
                    'amount' => $installment->installment_amount,
                    'currency' => SalaryTransaction::defaultCurrency(),
                    'type' => SalaryTransactionType::TYPE_DEDUCTION->value,
                    'sub_type' => SalaryTransactionSubType::EARLY_ADVANCE_INSTALLMENT->value,
                    'reference_type' => EmployeeAdvanceInstallment::class,
                    'reference_id' => $installment->id,
                    'description' => $desc,
                    'operation' => SalaryTransaction::OPERATION_SUB,
                    'year' => $record->year,
                    'month' => $record->month,
                    'created_by' => auth()->id(),
                    'status' => SalaryTransaction::STATUS_PENDING,
                ]);

                // Update payroll total_deductions
                $payroll->increment('total_deductions', $installment->installment_amount);
                $payroll->decrement('net_salary', $installment->installment_amount);

                $createdCount++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Duplicate record - skip
                continue;
            }
        }

        // Update PayrollRun totals
        $record->refresh();
        $record->total_deductions = $record->payrolls()->sum('total_deductions');
        $record->total_net = $record->payrolls()->sum('net_salary');
        $record->save();

        Notification::make()
            ->title(__('lang.success'))
            ->body(__('lang.early_installments_added', ['count' => $createdCount]))
            ->success()
            ->send();
    }
}
