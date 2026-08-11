<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

use Illuminate\Support\Str;

use App\Exports\PayrollsExport;
use App\Exports\PayrollTransactionsExport;
use App\Models\Payroll;
use App\Models\SalaryTransaction;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Facades\Excel;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';



    public function table(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->deferFilters(false)
            ->defaultKeySort(false)

            ->recordTitleAttribute('employee')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with(['employee.branch', 'branch'])
                      ->leftJoin('hr_employee_service_terminations', 'hr_employee_service_terminations.employee_id', '=', 'hr_payrolls.employee_id');

                if ($this->isShowingBranchSplits()) {
                    return $query->select('hr_payrolls.*', 'hr_employee_service_terminations.termination_reason');
                }

                return $query
                 
                    ->selectRaw('
                        MIN(hr_payrolls.id) as id,
                        hr_payrolls.payroll_run_id,
                        hr_payrolls.employee_id,
                        MIN(hr_payrolls.branch_id) as branch_id,
                        hr_payrolls.year,
                        hr_payrolls.month,
                        MIN(hr_payrolls.period_start_date) as period_start_date,
                        MAX(hr_payrolls.period_end_date) as period_end_date,
                        SUM(hr_payrolls.base_salary) as base_salary,
                        SUM(hr_payrolls.total_allowances) as total_allowances,
                        SUM(hr_payrolls.total_bonus) as total_bonus,
                        SUM(hr_payrolls.overtime_amount) as overtime_amount,
                        SUM(hr_payrolls.total_deductions) as total_deductions,
                        SUM(hr_payrolls.total_advances) as total_advances,
                        SUM(hr_payrolls.total_penalties) as total_penalties,
                        SUM(hr_payrolls.total_insurance) as total_insurance,
                        SUM(hr_payrolls.employer_share) as employer_share,
                        SUM(hr_payrolls.employee_share) as employee_share,
                        SUM(hr_payrolls.taxes_amount) as taxes_amount,
                        SUM(hr_payrolls.other_deductions) as other_deductions,
                        SUM(hr_payrolls.gross_salary) as gross_salary,
                        SUM(hr_payrolls.net_salary) as net_salary,
                        MIN(hr_payrolls.currency) as currency,
                        MIN(hr_payrolls.status) as status,
                        MAX(hr_payrolls.is_paid) as is_paid,
                        MAX(hr_payrolls.payment_date) as payment_date,
                        MAX(hr_payrolls.paid_at) as paid_at,
                        MAX(hr_payrolls.paid_by) as paid_by,
                        MIN(hr_payrolls.created_at) as created_at,
                        MAX(hr_payrolls.updated_at) as updated_at,
                        MAX(hr_employee_service_terminations.termination_reason) as termination_reason,
                        MAX(hr_employee_service_terminations.notes) as termination_notes,
                        1 as is_grouped_row
                    ')
                    ->groupBy('hr_payrolls.payroll_run_id', 'hr_payrolls.employee_id', 'hr_payrolls.year', 'hr_payrolls.month');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->alignCenter()->label('ID')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employee.employee_no')
                    ->alignCenter()->label('Staff No')->default('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employee.name')
                    ->searchable()
                    ->limit(15)
                    ->label(__('lang.employee'))
                    ->color(function (Payroll $record) {
                        if ($record->employee?->serviceTermination && !$record->employee->active) {
                            return 'danger';
                        }
                        return 'primary';
                    })
                    ->tooltip(fn($state) => $state),
                Tables\Columns\TextColumn::make('branch.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('lang.branch'))
                    ->getStateUsing(function (Payroll $record) {
                        if ($this->isShowingBranchSplits()) {
                            return $record->branch?->name;
                        }

                        return $record->employee?->branch?->name;
                    })
                    ->visible(fn () => $this->isShowingBranchSplits())
                    // ->toggleable()
                    ,
                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base')
                    ->numeric()->alignCenter()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),



                Tables\Columns\TextColumn::make('deductions_from_transactions')
                    ->label('Deductions')
                    ->alignCenter()
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function (Payroll $record) {
                        $amount  = SalaryTransaction::query()
                            ->whereIn('payroll_id', $this->payrollIdsForDisplay($record))
                            ->where('operation', '-')
                            ->where('type', '!=', \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD->value)
                            ->sum('amount');
                        return formatMoneyWithCurrency($amount);
                    }),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label(__('Paid'))
                    ->boolean()->alignCenter(),
                TextColumn::make('payment_date')
                    ->label(__('Payment Date'))
                    ->date('Y-m-d')->alignCenter()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->dateTime()->alignCenter()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payer.name')
                    ->label(__('Paid By'))
                    ->alignCenter()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_salary')
                    ->label(__('Net Salary'))->alignCenter()
                    ->getStateUsing(fn(Payroll $record) => (float) $record->getRawOriginal('net_salary'))
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->summarize(Sum::make()->label(__(''))->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))),
                TextColumn::make('termination_reason')
                ->wrap()
                    ->label(__('Termination Reason'))
                    ->alignCenter()
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('termination_notes')
                ->wrap()
                    ->label(__('Termination Notes'))
                    ->alignCenter()
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->alignCenter()->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('hr_payrolls.created_at', $direction);
                    })
                    ->dateTime(),

            ])
            ->filters([
                Filter::make('show_branch_splits')
                    ->label(__('Show Branch'))
                    ->toggle(),
            ])
            ->selectable()
            ->recordActions([

                ActionGroup::make([
                    ForceDeleteAction::make()
                        ->action(function (Payroll $record): void {
                            $ids = $this->payrollIdsForDisplay($record);
                            Payroll::query()->whereIn('id', $ids)->forceDelete();
                        })->label('Force Delete'),
                    DeleteAction::make()
                        ->action(function (Payroll $record): void {
                            $ids = $this->payrollIdsForDisplay($record);
                            Payroll::query()->whereIn('id', $ids)->delete();
                        })->label('Delete'),
                    Action::make('pdfTransactions')
                        ->label('Transactions')
                        ->button()->tooltip('Export Transactions PDF')
                        ->color('primary')
                        ->icon(Heroicon::DocumentArrowDown)
                        ->action(function (Payroll $record) {
                            return app(\App\Modules\HR\Payroll\Reports\TransactionsReport::class)->generate($record->id);
                        }),

                    Action::make('excelPayroll')
                        ->label('Excel')
                        ->button()
                        ->tooltip('Export Transactions to Excel')
                        ->color('info')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->action(function (Payroll $record) {
                            $transactions = SalaryTransaction::query()
                                ->whereIn('payroll_id', $this->payrollIdsForDisplay($record))
                                ->get();
                            $employeeName = $record->employee?->name ?? 'Employee';
                            $netSalary = (float) $record->getRawOriginal('net_salary');
                            $fileName = 'transactions-' . $employeeName . '.xlsx';
                            return Excel::download(new PayrollTransactionsExport($transactions, $employeeName, $netSalary), $fileName);
                        }),
                    self::quickShowAction(),
                ]),

                Action::make('pdfSalarySlip')
                    ->label('Salary Slip')
                    ->button()
                    ->color('success')
                    ->tooltip('Export Salary Slip PDF')
                    ->icon(Heroicon::DocumentArrowDown)
                    ->action(function (Payroll $record) {
                        return app(\App\Modules\HR\Payroll\Reports\SalarySlipReport::class)->generate($record->id);
                    }),




                Action::make('markAsPaid')
                    ->label(__('Mark as Paid'))
                    ->icon('heroicon-o-check-circle')
                    ->color('info')->button()
                    ->requiresConfirmation()
                    ->visible(fn(Payroll $record): bool => $record->status === Payroll::STATUS_APPROVED && !$record->is_paid)
                    ->action(function (Payroll $record): void {
                        Payroll::query()
                            ->whereIn('id', $this->payrollIdsForDisplay($record))
                            ->update([
                                'is_paid'      => true,
                                'payment_date' => now(),
                                'status'       => Payroll::STATUS_PAID,
                                'paid_by'      => auth()->id(),
                                'paid_at'      => now(),
                            ]);
                    }),

            ])

            ->toolbarActions([
                BulkAction::make('delete_payroll')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        try {
                            \Illuminate\Support\Facades\DB::beginTransaction();

                            // Collect all real payroll IDs for every selected (possibly grouped) record
                            $ids = $records
                                ->flatMap(fn(Payroll $record) => $this->payrollIdsForDisplay($record))
                                ->unique()
                                ->values()
                                ->all();

                            Payroll::query()->whereIn('id', $ids)->forceDelete();

                            \Illuminate\Support\Facades\DB::commit();
                            showSuccessNotifiMessage(__('lang.deleted_successfully'));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title(__('lang.error_occurred') ?? 'Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->button()
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->action(function () {
                        $payrolls = $this->getOwnerRecord()->payrolls()->with('employee')->get();
                        $fileName = 'payrolls-' . $this->getOwnerRecord()->name . '.xlsx';
                        return Excel::download(new PayrollsExport($payrolls), $fileName);
                    }),
                BulkAction::make('markAsPaidBulk')
                    ->label(__('Mark as Paid'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(\Filament\Resources\RelationManagers\RelationManager $livewire) => $livewire->getOwnerRecord()->status === \App\Models\PayrollRun::STATUS_APPROVED)
                    ->action(function (Collection $records): void {
                        $ids = $records
                            ->flatMap(fn(Payroll $record) => $this->payrollIdsForDisplay($record))
                            ->unique()
                            ->values()
                            ->all();

                        Payroll::query()
                            ->whereIn('id', $ids)
                            ->update([
                                'is_paid'      => true,
                                'payment_date' => now(),
                                'status'       => Payroll::STATUS_PAID,
                                'paid_by'      => auth()->id(),
                                'paid_at'      => now(),
                            ]);
                    }),
                DeleteBulkAction::make()
                    ->visible(fn(): bool => isSuperAdmin()),
            ]);
    }

    // =========================================================================
    //  Record Actions
    // =========================================================================

    /**
     * Quick-preview modal: shows all financial transactions for a payroll
     * without leaving the page or generating a PDF.
     */
    public static function quickShowAction(): Action
    {
        return Action::make('quickShow')
            ->label('Preview')
            ->button()
            ->color('gray')
            ->icon('heroicon-o-eye')
            ->tooltip('Preview transactions in-page')
            ->modalWidth('2xl')
            ->modalHeading(fn(Payroll $record) => "Transactions — {$record->employee?->name} ({$record->year}/{$record->month})")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->disabledForm()
            ->schema(function (Payroll $record): array {
                $rows = SalaryTransaction::query()
                    ->whereIn('payroll_id', self::payrollIdsForRecord($record))
                    ->orderByRaw("FIELD(operation, '+', '-')")
                    ->orderBy('type')
                    ->get()
                    ->map(fn($tx) => [
                        'operation'   => $tx->operation,
                        'type'        => $tx->type,
                        'description' => $tx->description ?? $tx->notes ?? '—',
                        'amount'      => formatMoneyWithCurrency($tx->amount),
                    ])
                    ->values()
                    ->toArray();

                return [
                    \Filament\Forms\Components\Repeater::make('transactions')
                        ->label('')
                        ->default($rows)
                        ->columnSpanFull()
                        ->table([
                            TableColumn::make('type'),
                            TableColumn::make('description'),
                            TableColumn::make('amount'),
                        ])
                        ->deletable(false)
                        ->addable(false)
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('type')->columnSpan(2),
                            \Filament\Forms\Components\TextInput::make('description')->columnSpan(3),
                            \Filament\Forms\Components\TextInput::make('amount')->columnSpan(2),
                        ])
                        ->columns(8),
                ];
            });
    }

    private function isShowingBranchSplits(): bool
    {
        return (bool) data_get($this->getTableFilterState('show_branch_splits'), 'isActive', false);
    }

    private function payrollIdsForDisplay(Payroll $record): array
    {
        if (! (bool) $record->getAttribute('is_grouped_row')) {
            return [$record->id];
        }

        return self::payrollIdsForRecord($record);
    }

    private static function payrollIdsForRecord(Payroll $record): array
    {
        if (! $record->payroll_run_id || ! $record->employee_id) {
            return [$record->id];
        }

        return Payroll::query()
            ->where('payroll_run_id', $record->payroll_run_id)
            ->where('employee_id', $record->employee_id)
            ->pluck('id')
            ->all();
    }
}
