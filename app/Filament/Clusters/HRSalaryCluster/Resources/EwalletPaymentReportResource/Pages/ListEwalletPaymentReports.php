<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EwalletPaymentReportResource;
use App\Models\EwalletPaymentReport;
use App\Models\EwalletPaymentReportItem;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\EmployeePaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
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
                ->modalDescription('Generate a new eWallet payment report.')
                ->modalHeading('Generate eWallet Payment Report')
                // ->slideOver(true)
                ->color('info')
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->modalIcon(Heroicon::ChartBarSquare)
                ->modalWidth(Width::SevenExtraLarge)
                ->schema([
              Grid::make()->columnSpanFull()->columns(2)->schema([
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
                      Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->required(),

                        
                        ]), 
                        Placeholder::make('important_reminder')
                        ->label(new \Illuminate\Support\HtmlString('<span style="color: red;">Important Reminder Before Generating the eWallet Payment Report:</span>'))
                        ->content(new \Illuminate\Support\HtmlString('
                            <div style="font-size: 1.05rem; color: red;">
                                <p style="margin-bottom: 1rem;">Please ensure you have marked all staff members who were paid manually or left mid-month as "<b>Paid</b>" in the Payroll before running this report.</p>
                                <p style="margin-left: 2.5rem;">Once marked, the system will automatically filter them out. This prevents<br>duplicate payouts when the final batch file is generated.</p>
                            </div>
                        '))
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $year = $data['year'];

                    // 1. Get the latest approved PayrollRun per branch for this month/year
                    $latestRunIds = PayrollRun::query()
                        ->where('status', PayrollRun::STATUS_APPROVED)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->whereNotNull('approved_at')
                        ->selectRaw('MAX(id) as id, branch_id')
                        ->groupBy('branch_id')
                        ->pluck('id');

                    if ($latestRunIds->isEmpty()) {
                        Notification::make()
                            ->title("No approved payroll runs found for this month.")
                            ->warning()
                            ->send();
                        return;
                    }

                    // 2. Get payrolls from the latest runs, eWallet only, excluding already paid
                    $payrolls = Payroll::with(['employee', 'branch', 'employee.branch'])
                        ->where('status', Payroll::STATUS_APPROVED)
                        ->whereIn('payroll_run_id', $latestRunIds)
                        ->where('is_paid', false)
                        ->whereHas('employee.paymentMethod', function ($q) {
                            $q->where('code', EmployeePaymentMethod::CODE_EWALLET);
                        })
                        ->get();

                    if ($payrolls->isEmpty()) {
                        Notification::make()
                            ->title("No unpaid eWallet payrolls found.")
                            ->body("All eWallet payrolls from the latest approved runs have already been paid.")
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

                    // Show success with included branches info
                    $branchNames = $payrolls
                        ->map(fn ($p) => $p->branch?->name ?? $p->employee?->branch?->name ?? 'Unknown')
                        ->unique()
                        ->values()
                        ->implode(', ');

                    Notification::make()
                        ->title("Report generated successfully.")
                        ->body("Included branches: {$branchNames} | Employees: {$payrolls->count()}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
