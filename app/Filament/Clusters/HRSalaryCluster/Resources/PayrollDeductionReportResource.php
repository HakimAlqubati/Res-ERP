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
                SelectFilter::make('employee_id')->label('Employee')->options(
                    function () {
                        return Employee::where('active', 1)
                            ->get()
                            ->mapWithKeys(function ($employee) {
                                return [$employee->id => $employee->name . ' - ' . $employee->id];
                            })->all();
                    }
                )->searchable(),

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
                    ->placeholder('Yes')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query) => $query,
                        false: fn (Builder $query) => $query,
                        blank: fn (Builder $query) => $query,
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
