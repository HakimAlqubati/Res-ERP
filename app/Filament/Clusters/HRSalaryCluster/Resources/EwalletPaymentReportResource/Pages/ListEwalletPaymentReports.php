<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource;
use App\Models\EwalletPaymentReport;
use App\Models\EwalletPaymentReportItem;
use App\Models\Payroll;
use App\Models\EmployeePaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListEwalletPaymentReports extends ListRecords
{
    protected static string $resource = EwalletPaymentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->required(),
                    Select::make('year')
                        ->label('Year')
                        ->options(function () {
                            $years = [];
                            $currentYear = date('Y');
                            for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                $years[$i] = $i;
                            }
                            return $years;
                        })
                        ->default(date('Y'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $year = $data['year'];

                    $exists = EwalletPaymentReport::where('month', $month)
                        ->where('year', $year)
                        ->exists();

                    // if ($exists) {
                    //     Notification::make()
                    //         ->title("A report for this month and year already exists.")
                    //         ->danger()
                    //         ->send();
                    //     return;
                    // }

                    $payrolls = Payroll::with(['employee', 'branch', 'employee.branch'])
                        ->where('status', Payroll::STATUS_APPROVED)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->whereHas('employee.paymentMethod', function ($q) {
                            $q->where('code', EmployeePaymentMethod::CODE_EWALLET);
                        })
                        ->get();

                    if ($payrolls->isEmpty()) {
                        Notification::make()
                            ->title("No approved payroll found for this month.")
                            ->warning()
                            ->send();
                        return;
                    }

                    DB::transaction(function () use ($month, $year, $payrolls) {
                        $totalAmount = $payrolls->sum('net_salary');
                        $employeesCount = $payrolls->count();

                        $report = EwalletPaymentReport::create([
                            'month' => $month,
                            'year' => $year,
                            'total_amount' => $totalAmount,
                            'employees_count' => $employeesCount,
                            'status' => 'pending',
                            'created_by' => auth()->id(),
                        ]);

                        $items = [];
                        $monthName = \Carbon\Carbon::create()->month($month)->format('F');

                        foreach ($payrolls as $payroll) {
                            $branchName = $payroll->branch?->name ?? $payroll->employee?->branch?->name ?? 'Unknown Branch';
                            $rewardDescription = "Salary - {$monthName} {$year} - {$branchName}";
                            $rewardName = $payroll->employee?->payment_details['full_name'] ?? $payroll->employee?->name ?? '';
                            $accountNumber = $payroll->employee?->payment_details['account_number'] ?? null;

                            $items[] = [
                                'hr_ewallet_payment_report_id' => $report->id,
                                'payroll_id' => $payroll->id,
                                'employee_id' => $payroll->employee_id,
                                'account_number' => $accountNumber,
                                'net_salary' => $payroll->net_salary,
                                'reward_name' => $rewardName,
                                'reward_description' => $rewardDescription,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        EwalletPaymentReportItem::insert($items);
                    });

                    Notification::make()
                        ->title("Report generated successfully.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
