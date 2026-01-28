<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

use Illuminate\Support\Str;

use App\Models\Payroll;
use App\Models\SalaryTransaction;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';



    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordTitleAttribute('employee')
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_no')
                    ->alignCenter()->label('Employee No')->default('-'),
                Tables\Columns\TextColumn::make('employee.name'),
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
                            ->sum('amount');
                        return formatMoneyWithCurrency($amount);
                    }),

                TextColumn::make('net_salary')
                    ->label(__('Net Salary'))->alignCenter()
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->alignCenter()->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->dateTime(),

            ])->recordActions([
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


            ])->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
