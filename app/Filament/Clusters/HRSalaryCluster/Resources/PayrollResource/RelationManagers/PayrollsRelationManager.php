<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

use Illuminate\Support\Str;

use App\Exports\PayrollsExport;
use App\Exports\PayrollTransactionsExport;
use App\Models\Payroll;
use App\Models\SalaryTransaction;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
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

            ->recordTitleAttribute('employee')
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
                    ->tooltip(fn($state) => $state),
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
                        $amount  = $record->transactions()
                            ->where('operation', '-')
                            ->where('type', '!=', \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD->value)
                            ->sum('amount');
                        return formatMoneyWithCurrency($amount);
                    }),

                TextColumn::make('net_salary')
                    ->label(__('Net Salary'))->alignCenter()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->alignCenter()->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->dateTime(),

            ])
            ->selectable()
            ->recordActions([

                ForceDeleteAction::make(),
                DeleteAction::make(),

                Action::make('pdfSalarySlip')
                    ->label('Salary Slip')
                    ->button()
                    ->color('success')
                    ->tooltip('Export Salary Slip PDF')
                    ->icon(Heroicon::DocumentArrowDown)
                    ->action(function (Payroll $record) {
                        return app(\App\Modules\HR\Payroll\Reports\SalarySlipReport::class)->generate($record->id);
                    }),

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
                        $transactions = $record->transactions()->get();
                        $employeeName = $record->employee?->name ?? 'Employee';
                        $fileName = 'transactions-' . $employeeName . '.xlsx';
                        return Excel::download(new PayrollTransactionsExport($transactions, $employeeName), $fileName);
                    }),

                self::quickShowAction(),

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
                            $records->each(fn($record) => $record->forceDelete());
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
                DeleteBulkAction::make(),
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
                $rows = $record->transactions()
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
}
