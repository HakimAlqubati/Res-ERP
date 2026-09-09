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
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListEwalletPaymentReports extends ListRecords
{
    protected static string $resource = EwalletPaymentReportResource::class;

    /**
     * Define tabs for payment type filtering.
     */
    public function getTabs(): array
    {
        return [
            // 'all' => Tab::make(__('All'))
            //     ->modifyQueryUsing(fn(Builder $query) => $query)
            //     ->icon('heroicon-o-circle-stack')
            //     ->badge(EwalletPaymentReport::query()->count())
            //     ->badgeColor('gray'),

            'ewallet' => Tab::make(__('eWallet'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_type', EwalletPaymentReport::TYPE_EWALLET))
                ->icon('heroicon-o-device-phone-mobile')
                ->badge(EwalletPaymentReport::query()->where('payment_type', EwalletPaymentReport::TYPE_EWALLET)->count())
                ->badgeColor('info'),

            'bank' => Tab::make(__('Bank Transfer'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_type', EwalletPaymentReport::TYPE_BANK))
                ->icon('heroicon-o-building-library')
                ->badge(EwalletPaymentReport::query()->where('payment_type', EwalletPaymentReport::TYPE_BANK)->count())
                ->badgeColor('success'),

            'cash' => Tab::make(__('Cash'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_type', EwalletPaymentReport::TYPE_CASH))
                ->icon('heroicon-o-banknotes')
                ->badge(EwalletPaymentReport::query()->where('payment_type', EwalletPaymentReport::TYPE_CASH)->count())
                ->badgeColor('warning'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'ewallet';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate')
                ->icon('heroicon-o-plus')
                ->color('info')
                ->modalDescription('Generate a new payment report for eWallet, Bank, or Cash employees.')
                ->modalHeading('Generate Payment Report')
                // ->slideOver(true)
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->modalIcon(Heroicon::ChartBarSquare)
                ->modalWidth(Width::SevenExtraLarge)
                ->schema([
              Grid::make()->columnSpanFull()->columns(3)->schema([
                  ToggleButtons::make('payment_type')
                        ->label('Payment Type')
                        ->options([
                            EwalletPaymentReport::TYPE_EWALLET => 'eWallet',
                            EwalletPaymentReport::TYPE_BANK => 'Bank',
                            EwalletPaymentReport::TYPE_CASH => 'Cash',
                        ])
                        ->icons([
                            EwalletPaymentReport::TYPE_EWALLET => 'heroicon-o-device-phone-mobile',
                            EwalletPaymentReport::TYPE_BANK => 'heroicon-o-building-library',
                            EwalletPaymentReport::TYPE_CASH => 'heroicon-o-banknotes',
                        ])
                        ->colors([
                            EwalletPaymentReport::TYPE_EWALLET => 'info',
                            EwalletPaymentReport::TYPE_BANK => 'success',
                            EwalletPaymentReport::TYPE_CASH => 'warning',
                        ])
                        ->default(fn () => match ($this->activeTab) {
                            'bank' => EwalletPaymentReport::TYPE_BANK,
                            'cash' => EwalletPaymentReport::TYPE_CASH,
                            default => EwalletPaymentReport::TYPE_EWALLET,
                        })
                        ->inline()
                        ->required()
                        ->columnSpanFull(),
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
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="flex flex-col gap-2 p-4 rounded-xl bg-danger-50 text-danger-900 dark:bg-danger-500/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20 dark:ring-danger-500/20">
                                <strong class="font-semibold text-base" style="color: red;">Important Reminder Before Generating the Payment Report</strong>
                                <p class="text-sm">
                                    Please ensure you have marked all staff members who were paid manually or left mid-month as <strong>"Paid"</strong> in the Payroll before running this report.
                                </p>
                                <p class="text-sm">
                                    Once marked, the system will automatically filter them out. This prevents duplicate payouts when the final batch file is generated.
                                </p>
                            </div>
                        '))
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $year = $data['year'];
                    $paymentType = $data['payment_type'];

                    // Determine the payment method code based on selected type
                    $paymentMethodCode = match ($paymentType) {
                        EwalletPaymentReport::TYPE_BANK => EmployeePaymentMethod::CODE_BANK,
                        EwalletPaymentReport::TYPE_CASH => EmployeePaymentMethod::CODE_CASH,
                        default => EmployeePaymentMethod::CODE_EWALLET,
                    };

                    $paymentTypeLabel = match ($paymentType) {
                        EwalletPaymentReport::TYPE_BANK => 'Bank',
                        EwalletPaymentReport::TYPE_CASH => 'Cash',
                        default => 'eWallet',
                    };

                    // 1. Get the latest approved PayrollRun per branch for this month/year
                    $latestRunIds = PayrollRun::query()
                        ->where('status', PayrollRun::STATUS_APPROVED)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->whereNotNull('approved_at')
                        ->select('id','branch_id')
                        // ->selectRaw('MAX(id) as id, branch_id')
                        // ->groupBy('branch_id')
                        ->pluck('id');
                        
                    if ($latestRunIds->isEmpty()) {
                        Notification::make()
                            ->title("No approved payroll runs found for this month.")
                            ->warning()
                            ->send();
                        return;
                    }

                    // 2. Get payrolls from the latest runs, filtered by payment method, excluding already paid
                    $payrolls = Payroll::with(['employee', 'branch', 'employee.branch'])
                        ->where('status', Payroll::STATUS_APPROVED)
                        ->whereIn('payroll_run_id', $latestRunIds)
                        ->where('is_paid', false)
                        ->whereHas('employee.paymentMethod', function ($q) use ($paymentMethodCode) {
                            $q->where('code', $paymentMethodCode);
                        })
                        ->get();

                    if ($payrolls->isEmpty()) {
                        Notification::make()
                            ->title("No unpaid {$paymentTypeLabel} payrolls found.")
                            ->body("All {$paymentTypeLabel} payrolls from the latest approved runs have already been paid.")
                            ->warning()
                            ->send();
                        return;
                    }

                    $groupedPayrolls = $payrolls->groupBy('employee_id');

                    DB::transaction(function () use ($month, $year, $paymentType, $payrolls, $groupedPayrolls) {
                        $totalAmount = $payrolls->sum('net_salary');
                        $employeesCount = $groupedPayrolls->count();

                        $report = EwalletPaymentReport::create([
                            'month' => $month,
                            'year' => $year,
                            'total_amount' => $totalAmount,
                            'employees_count' => $employeesCount,
                            'status' => 'pending',
                            'payment_type' => $paymentType,
                            'created_by' => auth()->id(),
                        ]);

                        $items = [];
                        $monthName = \Carbon\Carbon::create()->month($month)->format('F');

                        foreach ($groupedPayrolls as $employeeId => $empPayrolls) {
                            $firstPayroll = $empPayrolls->first();
                            $employee = $firstPayroll->employee;

                            $branchNames = $empPayrolls
                                ->map(fn ($p) => $p->branch?->name ?? $p->employee?->branch?->name)
                                ->filter()
                                ->unique()
                                ->implode(' / ');

                            $branchName = !empty($branchNames) ? $branchNames : 'Unknown Branch';
                            $rewardDescription = "Salary - {$monthName} {$year} - {$branchName}";
                            $rewardName = $employee?->payment_details['full_name'] ?? $employee?->name ?? '';
                            $accountNumber = $paymentType === EwalletPaymentReport::TYPE_CASH
                                ? ($employee?->employee_no ?? null)
                                : ($employee?->payment_details['account_number'] ?? null);
                            $netSalary = $empPayrolls->sum('net_salary');

                            $items[] = [
                                'hr_ewallet_payment_report_id' => $report->id,
                                'payroll_id' => $firstPayroll->id,
                                'employee_id' => $employeeId,
                                'account_number' => $accountNumber,
                                'net_salary' => $netSalary,
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
                        ->body("Type: {$paymentTypeLabel} | Included branches: {$branchNames} | Employees: {$groupedPayrolls->count()}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
