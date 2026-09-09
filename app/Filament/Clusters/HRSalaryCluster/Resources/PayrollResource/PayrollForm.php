<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;

use App\Enums\HR\Payroll\SalaryAllocationRule;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Fieldset;
use App\Models\EmployeeBranchLog;
use App\Modules\HR\EmployeeApplications\Checker\MonthlyPendingApplicationChecker;
use Carbon\Carbon;

class PayrollForm
{
    /**
     * Get the form schema for PayrollResource.
     */
    public static function getSchema(): array
    {
        // dd(getMonthOptionsBasedOnSettings());
        return [
            Fieldset::make()->columnSpanFull()->label('Set Branch, Month and generation date')->columns(3)->schema([
                TextInput::make('note_that')->label('Note that!')->columnSpan(3)->hiddenOn('view')
                    ->disabled()
                    ->suffixIcon('heroicon-o-exclamation-triangle')
                    ->suffixIconColor('warning')
                    ->default('Staff who have not had their work periods added, will not appear on the payroll.'),
                Placeholder::make('salary_warning')
                    ->label('Warning')
                    ->columnSpan(3)
                    ->visible(fn (Get $get) => filled($get('branch_id')) && !empty(self::getZeroSalaryEmployees($get)))
                    ->content(function (Get $get) {
                        $names = self::getZeroSalaryEmployees($get);
                        if (empty($names)) {
                            return null;
                        }
                        $namesList = implode('', array_map(fn($name) => '<li>' . e($name) . '</li>', $names));
                         return new \Illuminate\Support\HtmlString("
                            <style>
                                .payroll-zero-salary-warning { border: 1px solid #fecaca; }
                                .payroll-zero-salary-warning h3 { color: #991b1b; }
                                .payroll-zero-salary-warning ul { color: #b91c1c; }
                                .dark .payroll-zero-salary-warning { border-color: rgba(127, 29, 29, 0.5); }
                                .dark .payroll-zero-salary-warning h3 { color: #fca5a5; }
                                .dark .payroll-zero-salary-warning ul { color: #f87171; }
                            </style>
                            <div class='p-4 rounded-lg payroll-zero-salary-warning'>
                                <h3 class='text-sm font-semibold'>
                                    Warning: The following staff have zero or null salary:
                                </h3>
                                <ul class='list-disc list-inside mt-2 text-sm font-medium space-y-1'>
                                    {$namesList}
                                </ul>
                            </div>
                        ");
                    }),
                Placeholder::make('pending_applications_warning')
                    ->label('Pending Requests Warning')
                    ->columnSpan(3)
                    ->visible(fn (Get $get) => filled($get('branch_id')) && self::hasPendingApplications($get))
                    ->content(function (Get $get) {
                        $summary = self::getPendingApplicationsSummary($get);
                        if (!$summary || empty($summary['breakdown'])) {
                            return null;
                        }
                        
                        $breakdownList = implode('', array_map(fn($item) => '<li>' . e($item['type']) . ': ' . e($item['count']) . '</li>', $summary['breakdown']));
                        
                        return new \Illuminate\Support\HtmlString("
                            <style>
                                .payroll-pending-warning { border: 1px solid #fecaca; }
                                .payroll-pending-warning h3 { color: #991b1b; }
                                .payroll-pending-warning ul { color: #b91c1c; }
                                .dark .payroll-pending-warning { border-color: rgba(127, 29, 29, 0.5); }
                                .dark .payroll-pending-warning h3 { color: #fca5a5; }
                                .dark .payroll-pending-warning ul { color: #f87171; }
                            </style>
                            <div class='p-4 rounded-lg payroll-pending-warning mt-4'>
                                <h3 class='text-sm font-semibold'>
                                    Warning: Cannot create payroll. Please approve or reject all pending requests for this period first:
                                </h3>
                                <ul class='list-disc list-inside mt-2 text-sm font-medium space-y-1'>
                                    {$breakdownList}
                                </ul>
                            </div>
                        ");
                    }),
                Select::make('branch_id')->label('Choose branch')
                    ->disabledOn('view')->searchable()
                    ->options(Branch::query()
                         ->active()
                        ->forBranchManager('id')
                        ->select('id', 'name')
                        ->get()
                        ->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->helperText('Please, choose a branch'),
                Select::make('name')->label('Month')->hiddenOn('view')
                    ->required()
                    ->options(fn() => getMonthOptionsBasedOnSettings())
                    ->live()
                    ->default(now()->format('F'))
                    ->rule(function (callable $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $branchId = $get('branch_id');
                            if (! $branchId) {
                                return;
                            }

                            if (! str_contains($value, ' ')) {
                                $fail(__('Invalid month format. Please select a valid month.'));
                                return;
                            }

                            [$monthName, $year] = explode(' ', $value);
                            $monthNumber = \Carbon\Carbon::parse("1 $value")->month;

                            $allEmployees = $get('all_employees');
                            $employeeIds = $get('employee_ids') ?? [];

                            $query = \App\Models\Payroll::query()
                                ->withTrashed()
                                ->where('branch_id', $branchId)
                                ->where('year', (int) $year)
                                ->where('month', (int) $monthNumber);

                            if (! $allEmployees && !empty($employeeIds)) {
                                $query->whereIn('employee_id', $employeeIds);
                            }

                            $existing = $query->with('employee:id,name,branch_id')->get();
                            
                            $existing = $existing->filter(function ($payroll) use ($branchId, $year, $monthNumber) {
                                if (!$payroll->employee) return false;
                                
                                $date = \Carbon\Carbon::create((int) $year, (int) $monthNumber, 1);
                                $startDate = $date->copy()->startOfMonth();
                                $endDate = $date->copy()->endOfMonth();
                                
                                return self::isEmployeeOwnedByBranchForPayroll($payroll->employee, (int) $branchId, $startDate, $endDate);
                            });
                             if ($existing->isNotEmpty()) {
                                $names = $existing->pluck('employee.name')->filter()->unique()->implode(', ');
                                $trashed = $existing->whereNotNull('deleted_at')->isNotEmpty();
                                if ($trashed) {
                                    $fail(__("Payroll already exists in the trash for: $names in this month. Please restore or permanently delete them first."));
                                } else {
                                    $fail(__("Payroll already exists for: $names in this month."));
                                }
                            }
                        };
                    }),
                TextInput::make('name')->label('Title')->hiddenOn('create')->disabled(),
                DatePicker::make('pay_date')->required()
                    ->label('Generation date')
                    ->default(date('Y-m-d')),
            ]),

            Fieldset::make()->columnSpanFull()->label('Staff Selection')->columns(1)->hiddenOn('view')
                ->visible(fn(Get $get) => filled($get('branch_id')))
                ->schema([
                    Toggle::make('all_employees')
                        ->label('All Staff')
                        ->default(true)
                        ->live(),
                    CheckboxList::make('employee_ids')
                        ->label('Select Staff')
                        ->bulkToggleable()

                        ->searchable()
                        ->columns(4)
                        ->visible(fn(Get $get) => !$get('all_employees'))
                        ->options(function (Get $get) {
                            $branchId = $get('branch_id');
                            if (!$branchId) return [];

                            $monthValue = $get('name');
                            if (!$monthValue) {
                                // Fallback to current month if not selected
                                $monthNumber = now()->month;
                                $year = now()->year;
                            } else {
                                if (! str_contains($monthValue, ' ')) {
                                    return [];
                                }

                                [$monthName, $year] = explode(' ', $monthValue);
                                $monthNumber = \Carbon\Carbon::parse("1 $monthValue")->month;
                                $year = (int) $year;
                            }

                            $date = Carbon::create((int) $year, (int) $monthNumber, 1);
                            $startDate = $date->copy()->startOfMonth();
                            $endDate = $date->copy()->endOfMonth();

                            $idsInLog = EmployeeBranchLog::getEmployeesForBranchInRange($branchId, $startDate, $endDate);

                            return Employee::query()
                                ->eligibleForPayroll($year, $monthNumber)
                                ->whereIn('id', $idsInLog)
                                ->get()
                                ->filter(fn(Employee $employee) => self::isEmployeeOwnedByBranchForPayroll($employee, (int) $branchId, $startDate, $endDate))
                                ->pluck('name', 'id');
                        })
                        ->columnSpanFull()
                        ->helperText('Choose the employees to include in this payroll run.'),
                ]),

            Textarea::make('notes')->label('Notes')->columnSpanFull(),
        ];
    }

    private static function isEmployeeOwnedByBranchForPayroll(Employee $employee, int $branchId, Carbon $periodStart, Carbon $periodEnd): bool
    {
        if ($employee->getEffectiveSalaryAllocationRule() !== SalaryAllocationRule::PROPORTIONAL) {
            return true;
        }

        $ownerSegment = EmployeeBranchLog::getSalarySegments(
            $employee,
            $periodStart,
            $periodEnd,
            null,
            SalaryAllocationRule::LAST_BRANCH,
        )->first();

        return (int) ($ownerSegment['branch_id'] ?? 0) === $branchId;
    }

    private static function getPendingApplicationsSummary(Get $get): ?array
    {
        $branchId = $get('branch_id');
        $monthValue = $get('name');
        
        if (!$branchId || !$monthValue) {
            return null;
        }

        if (!str_contains($monthValue, ' ')) {
            return null;
        }

        try {
            [$monthName, $year] = explode(' ', $monthValue);
            $monthNumber = Carbon::parse("1 $monthValue")->month;
            $year = (int) $year;

            $filters = [
                'year' => $year,
                'month' => $monthNumber,
                'branch_id' => $branchId,
            ];

            $allEmployees = $get('all_employees');
            if (!$allEmployees) {
                $employeeIds = $get('employee_ids');
                if (!empty($employeeIds)) {
                    $filters['employee_ids'] = $employeeIds;
                }
            }

            /** @var MonthlyPendingApplicationChecker $checker */
            $checker = app(MonthlyPendingApplicationChecker::class);
            
            return $checker->getDashboardSummary($filters);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function hasPendingApplications(Get $get): bool
    {
        $summary = self::getPendingApplicationsSummary($get);
        return $summary['has_pending'] ?? false;
    }

    private static function getZeroSalaryEmployees(Get $get): array
    {
        $branchId = $get('branch_id');
        if (!$branchId) {
            return [];
        }

        $directEmployeeIds = Employee::query()
            ->where('branch_id', $branchId)
            ->active()
            ->pluck('id')
            ->toArray();

        $logEmployeeIds = [];
        try {
            $monthValue = $get('name');
            if ($monthValue) {
                $parts = explode(' ', $monthValue);
                if (count($parts) === 2) {
                    [$monthName, $year] = $parts;
                    $monthNumber = \Carbon\Carbon::parse("1 $monthValue")->month;
                    $year = (int) $year;

                    $date = Carbon::create((int) $year, (int) $monthNumber, 1);
                    $startDate = $date->copy()->startOfMonth();
                    $endDate = $date->copy()->endOfMonth();
                    $logEmployeeIds = EmployeeBranchLog::getEmployeesForBranchInRange($branchId, $startDate, $endDate);
                }
            }
        } catch (\Throwable $e) {
        }

        $allIds = array_unique(array_merge($directEmployeeIds, $logEmployeeIds));

        if (empty($allIds)) {
            return [];
        }

        return Employee::query()
            ->whereIn('id', $allIds)
            ->where(function ($query) {
                $query->whereNull('salary')
                    ->orWhere('salary', 0);
            })
            ->active()
            ->pluck('name')
            ->toArray();
    }
}
