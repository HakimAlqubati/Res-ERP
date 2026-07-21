<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollDeductionReportResource\Pages\ListPayrollDeductionReports;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use App\Models\SalaryTransaction;
use App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollDeductionReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralLabel = 'Deductions Report';

    protected static ?string $pluralModelLabel = 'Deductions Report';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->deferFilters(false)
            ->filters([
                Filter::make('grouping_filter')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('group_by')
                            ->label(__('Group By'))
                            ->options([
                                DeductionReportFilterDTO::GROUP_BY_EMPLOYEE => __('lang.employee'),
                                DeductionReportFilterDTO::GROUP_BY_BRANCH => __('lang.branch'),
                            ])
                            ->default(DeductionReportFilterDTO::GROUP_BY_EMPLOYEE)
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state === DeductionReportFilterDTO::GROUP_BY_BRANCH) {
                                    $set('employee_id', null);
                                } else {
                                    $set('branch_id', null);
                                }
                            }),

                        Select::make('branch_id')
                            ->label(__('Branch'))
                            ->options(function () {
                                return Branch::where('active', 1)
                                    ->forBranchManager('id')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder('Select Branch')
                            ->visible(fn (callable $get) => $get('group_by') === DeductionReportFilterDTO::GROUP_BY_BRANCH),

                        Select::make('employee_id')
                            ->label(__('lang.employee'))
                            ->options(function () {
                                return Employee::where('active', 1)
                                    ->limit(5)
                                    ->get()
                                    ->mapWithKeys(function ($employee) {
                                        return [$employee->id => $employee->name.' - '.$employee->id];
                                    })->all();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return Employee::where('active', 1)
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                            ->orWhere('id', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($employee) {
                                        return [$employee->id => $employee->name.' - '.$employee->id];
                                    })->all();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $employee = Employee::find($value);

                                return $employee ? $employee->name.' - '.$employee->id : null;
                            })
                            ->searchable()
                            ->placeholder(__('lang.select_employee'))
                            ->hidden(fn (callable $get) => $get('group_by') === DeductionReportFilterDTO::GROUP_BY_BRANCH),
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    })
                    ->columns(3),

                SelectFilter::make('deduction_type')
                    ->multiple()
                    ->label(__('Deduction Type'))
                    ->options(function () {
                        return SalaryTransaction::query()
                            ->where(function ($q) {
                                $q->where('operation', SalaryTransaction::OPERATION_SUB)
                                    ->orWhere('type', SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION);
                            })
                            ->where('status', SalaryTransaction::STATUS_APPROVED)
                            ->select('type', 'sub_type', 'description')
                            ->distinct()
                            ->get()
                            ->mapWithKeys(function ($tx) {
                                $name = $tx->description ?: ucfirst(str_replace('_', ' ', $tx->sub_type ?? $tx->type));

                                return [$name => $name];
                            })
                            ->filter()
                            ->unique()
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder(__('All')),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now()->endOfMonth()),
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    }),

                TernaryFilter::make('include_employer_contribution')
                    ->label('Include Employer Contribution')
                    ->selectablePlaceholder(false)
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query,
                        false: fn (Builder $query) => $query,
                        blank: fn (Builder $query) => $query,
                    ),
            ], FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollDeductionReports::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
           if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
