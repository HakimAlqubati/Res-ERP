<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\RelationManagers;

use App\Models\Payroll;
use App\Models\SalaryTransaction;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Filament\Forms; 
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
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
                Tables\Columns\TextColumn::make('employee.employee_no')->label('Employee No'),
                Tables\Columns\TextColumn::make('employee.name'),
                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base')
                    ->numeric()->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_allowances')
                    ->label('Allowances')
                    ->numeric()->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_amount')
                    ->label('Overtime')
                    ->numeric()->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_deductions')
                    ->label('Deductions')
                    ->numeric()->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('Gross')->alignCenter()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->label('Net')->alignCenter()
                    ->numeric()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('salarySlipPdf')
                    ->label('Salary Slip PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(fn(Payroll $record) => $this->downloadSalarySlip($record)),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // ✅ Export transactions (no route)
                Tables\Actions\Action::make('exportTransactions')
                    ->label('Print Transactions')
                    // ->icon('heroicon-o-print')
                    ->color('success')
                    // ->tooltip('Export all salary transactions for this payroll as CSV')
                    ->url(fn(\App\Models\Payroll $record) => route('salary.report', [
                        'employee' => $record->employee_id,
                        // أي مفاتيح إضافية راح تروح كـ query string تلقائياً:
                        'run'   => $record->payroll_run_id,
                        'year'  => $record->year,
                        'month' => $record->month,
                    ]))->openUrlInNewTab()
                // ->action(function (Payroll $record) {

                // }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
