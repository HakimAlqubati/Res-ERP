<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeFinancialSummaryReportResource\Pages\ListEmployeeFinancialSummaryReports;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeFinancialSummaryReportResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $slug  = 'employee-financial-summary-reports';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $cluster = HRSalaryCluster::class;
    protected static ?int $navigationSort = 10;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('lang.employee_financial_summary_report') ?? 'Employee Financial Summary';
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.employee_financial_summary_report') ?? 'Employee Financial Summary';
    }

    public static function getPluralLabel(): string
    {
        return __('lang.employee_financial_summary_report') ?? 'Employee Financial Summary';
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->emptyStateHeading(__('lang.no_data') ?? 'No Data')
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch') ?? 'Branch')
                    ->placeholder(__('lang.all_branches') ?? 'All Branches')
                    ->options(Branch::active()->forBranchManager('id')->get()->pluck('name', 'id')->toArray())
                    ->searchable(),

            ], FiltersLayout::AboveContent)
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeFinancialSummaryReports::route('/'),
        ];
    }
}
