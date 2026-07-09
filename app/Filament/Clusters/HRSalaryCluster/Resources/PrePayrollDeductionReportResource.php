<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\PrePayrollDeductionReportResource\Pages\ListPrePayrollDeductionReports;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use App\Modules\HR\Payroll\DTOs\PrePayrollDeductionFilterDTO;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrePayrollDeductionReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

    protected static ?string $pluralLabel = 'Pre-Payroll Deductions Report';

    protected static ?string $pluralModelLabel = 'Pre-Payroll Deductions Report';

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
                                PrePayrollDeductionFilterDTO::GROUP_BY_EMPLOYEE => __('lang.employee'),
                                PrePayrollDeductionFilterDTO::GROUP_BY_BRANCH   => __('lang.branch'),
                            ])
                            ->default(PrePayrollDeductionFilterDTO::GROUP_BY_EMPLOYEE)
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set, string $state) {
                                $set('employee_id', null);
                                $set('branch_id', null);
                            }),

                        Select::make('branch_id')
                            ->label(__('Branch'))
                            ->options(fn () => Branch::where('active', 1)
                                ->forBranchManager('id')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->placeholder(__('lang.select_branch'))
                            ->visible(fn (callable $get) => $get('group_by') === PrePayrollDeductionFilterDTO::GROUP_BY_BRANCH),

                        Select::make('employee_id')
                            ->label(__('lang.employee'))
                            ->options(fn () => Employee::where('active', 1)
                                ->limit(5)
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => "{$e->name} - {$e->id}"])
                                ->all())
                            ->getSearchResultsUsing(fn (string $search) => Employee::where('active', 1)
                                ->where(fn ($q) => $q
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%"))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => "{$e->name} - {$e->id}"])
                                ->all())
                            ->getOptionLabelUsing(fn ($value) => ($e = Employee::find($value))
                                ? "{$e->name} - {$e->id}"
                                : null)
                            ->searchable()
                            ->placeholder(__('lang.select_employee'))
                            ->hidden(fn (callable $get) => $get('group_by') === PrePayrollDeductionFilterDTO::GROUP_BY_BRANCH),
                    ])
                    ->query(fn (Builder $query) => $query)
                    ->columns(3),

                Filter::make('period')
                    ->form([
                        Select::make('year')
                            ->label(__('Year'))
                            ->options(static::yearOptions())
                            ->default(now()->year)
                            ->selectablePlaceholder(false),

                        Select::make('month')
                            ->label(__('Month'))
                            ->options(static::monthOptions())
                            ->default(now()->month)
                            ->selectablePlaceholder(false),
                    ])
                    ->query(fn (Builder $query) => $query),
            ], FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrePayrollDeductionReports::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private static function yearOptions(): array
    {
        $current = (int) now()->year;

        return array_combine(
            range($current - 2, $current + 1),
            range($current - 2, $current + 1),
        );
    }

    private static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $m) => [
                $m => Carbon::create(2000, $m)->translatedFormat('F'),
            ])
            ->all();
    }
}
