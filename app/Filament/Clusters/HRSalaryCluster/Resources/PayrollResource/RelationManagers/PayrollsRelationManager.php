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
                // Action::make('salarySlipPdf')
                //     ->label('Salary Slip PDF')
                //     ->icon('heroicon-o-document-arrow-down')
                //     ->color('primary')
                //     ->url(fn(Payroll $record) => route('payrolls.salary-slip', $record))
                //     ->openUrlInNewTab(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // ✅ Export transactions (no route)

                
                Action::make('printSalarySlip')
                    ->label('Print Salary Slip')->button()
                    ->color('primary')
                    ->icon(Heroicon::Printer)
                    ->url(fn(Payroll $record) => route('salarySlip.print', [
                        'payroll_id' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('exportTransactions')->button()
                    ->label('Print Transactions')
                    ->icon(Heroicon::Printer)
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

        $data = [
            'title' => 'تجربة PDF',
            'content' => 'هذا نص تجريبي للتأكد من أن مكتبة laravel-mpdf تعمل بشكل صحيح 🎉',
            'date' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.test', $data);

        // تنزيل ملف
        return $pdf->download('test.pdf');

        // 1) جهّز البيانات عبر خدمة الـ SalarySlip
        /** @var SalarySlipService $service */
        $service = app(SalarySlipService::class);

        $payload = $service->build(
            employeeId: $record->employee_id,
            year: (int) $record->year,
            month: (int) $record->month
        );


        // 2) ابنِ الـ HTML من نفس القالب القديم

        $pdf = PDF::loadView('export.reports.hr.salaries.salary-slip', $payload, [], [
            'format'        => 'A4',
            'default_font'  => 'dejavusans',
        ]);
        // فعّل العلامة المائية من داخل mPDF بدل CSS
        $mpdf = $pdf->getMpdf(); // متاحة في الحزمة
        $mpdf->SetWatermarkImage(public_path('storage/logo/default.png'), 0.06); // الشفافية/الحجم
        // $mpdf->showWatermarkImage = true;

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payload['employee']?->name ?? 'Employee');
        $filename = sprintf('SalarySlip-%s-%04d-%02d.pdf', $safeName, (int)$record->year, (int)$record->month);

        return $pdf->download($filename);
    }
}
