<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

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
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';

    public   function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('employee')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->recordTitleAttribute('employee')
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_no')->alignCenter()->label('Employee No'),
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


            ])
            ->filters([
                //
            ])

            ->actions([
                Action::make('salarySlipPdf')->button()
                    ->label('Salary Slip PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(fn(Payroll $record) => $this->downloadSalarySlip($record)),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // ✅ Export transactions (no route)
                Action::make('exportTransactions')->button()
                    ->label('Print Transactions')
                    // ->icon('heroicon-o-print')
                    ->color('success')
                    // ->tooltip('Export all salary transactions for this payroll as CSV')
                    // ->url(fn(\App\Models\Payroll $record) => route('salary.report', [
                    //     'employee' => $record->employee_id,
                    //     // أي مفاتيح إضافية راح تروح كـ query string تلقائياً:
                    //     'run'   => $record->payroll_run_id,
                    //     'year'  => $record->year,
                    //     'month' => $record->month,
                    // ]))
                    ->url(fn(Payroll $record) => route('transactions.print', [
                        'payroll_id' => $record->id,
                    ]))
                    ->openUrlInNewTab()
                // ->action(function (Payroll $record) {

                // }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    /**
     * يولّد ملف PDF لقسيمة راتب موظف معيّن ويعيد Response للتحميل.
     */
    protected function downloadSalarySlip(Payroll $record)
    {
        // 1) جهّز البيانات عبر خدمة الـ SalarySlip
        /** @var SalarySlipService $service */
        $service = app(SalarySlipService::class);

        $payload = $service->build(
            employeeId: $record->employee_id,
            year: (int) $record->year,
            month: (int) $record->month
        );

        // 2) ابنِ الـ HTML من نفس القالب القديم
        $html = view('export.reports.hr.salaries.salary-slip', $payload)->render();

        // 3) حوّل إلى PDF (barryvdh/laravel-dompdf)
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('A4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payload['employee']?->name ?? 'Employee');
        $filename = sprintf('SalarySlip-%s-%04d-%02d.pdf', $safeName, (int) $record->year, (int) $record->month);

        // 4) رجّع Response تحميل مباشر (بدون راوت)
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
