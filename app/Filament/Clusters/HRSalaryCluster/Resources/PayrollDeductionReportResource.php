<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollDeductionReportResource\Pages\ListPayrollDeductionReports;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

class PayrollDeductionReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::DocumentCheck;

    protected static ?string $cluster = HRSalaryCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
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
                        \Filament\Forms\Components\Select::make('group_by')
                            ->label(__('Group By'))
                            ->options([
                                \App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO::GROUP_BY_EMPLOYEE => __('Employee'),
                                \App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO::GROUP_BY_BRANCH => __('Branch'),
                            ])
                            ->default(\App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO::GROUP_BY_EMPLOYEE)
                            ->selectablePlaceholder(false)
                            ->live(),

                        \Filament\Forms\Components\Select::make('branch_id')
                            ->label(__('Branch'))
                            ->options(function () {
                                return Branch::where('active', 1)
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder('Select Branch')
                            ->visible(fn(callable $get) => $get('group_by') === \App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO::GROUP_BY_BRANCH),

                        \Filament\Forms\Components\Select::make('employee_id')
                            ->label(__('Employee'))
                            ->options(function () {
                                return Employee::where('active', 1)
                                    ->get()
                                    ->mapWithKeys(function ($employee) {
                                        return [$employee->id => $employee->name . ' - ' . $employee->id];
                                    })->all();
                            })
                            ->searchable()
                            ->placeholder('Select Employee')
                            ->hidden(fn(callable $get) => $get('group_by') === \App\Modules\HR\Payroll\DTOs\DeductionReportFilterDTO::GROUP_BY_BRANCH),
                    ])
                    ->query(function (Builder $query) {
                        return $query;
                    })
                    ->columns(3),

                SelectFilter::make('deduction_type')
                    ->multiple()
                    ->label(__('Deduction Type'))
                    ->options(function () {
                        return \App\Models\SalaryTransaction::query()
                            ->where(function ($q) {
                                $q->where('operation', \App\Models\SalaryTransaction::OPERATION_SUB)
                                    ->orWhere('type', \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION);
                            })
                            ->where('status', \App\Models\SalaryTransaction::STATUS_APPROVED)
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

                \Filament\Tables\Filters\TernaryFilter::make('include_employer_contribution')
                    ->label('Include Employer Contribution')
                    ->selectablePlaceholder(false)
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->default(true)
                    ->queries(
                        true: fn(Builder $query) => $query,
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    )
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
}
