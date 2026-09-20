<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRSalaryCluster\Resources;

use App\Filament\Clusters\HRSalaryCluster;
use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeStatementReportResource\Pages\ListEmployeeStatementReports;
use App\Models\Employee;
use App\Models\FakeModelHRReports\EmployeeAttendanceReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeStatementReportResource extends Resource
{
    protected static ?string $model = EmployeeAttendanceReport::class;

    protected static ?string $slug = 'employee-statement-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $cluster = HRSalaryCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralLabel = 'Employee Financial Statement';

    protected static ?string $pluralModelLabel = 'Employee Financial Statement';

    public static function getModelLabel(): string
    {
        return __('lang.employee_statement_report') ?: 'Employee Financial Statement';
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.employee_statement_report') ?: 'Employee Financial Statement';
    }

    public static function getPluralLabel(): string
    {
        return __('lang.employee_statement_report') ?: 'Employee Statement Report';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->deferFilters(false)
            ->filters([
                Filter::make('statement_filter')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('employee_id')
                            ->label(__('lang.employee'))
                            ->options(fn () => Employee::where('active', 1)
                                ->limit(10)
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
                            ->live(),

                        DatePicker::make('from_date')
                            ->label(__('From Date'))
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->format('d-m-Y')
                            ->default(now()->startOfMonth()->format('d-m-Y'))
                            ->live(),

                        DatePicker::make('to_date')
                            ->label(__('To Date'))
                            ->native(false)
                            ->displayFormat('d-m-Y')
                            ->format('d-m-Y')
                            ->default(now()->endOfMonth()->format('d-m-Y'))
                            ->live(),
                    ])
                    ->query(fn (Builder $query) => $query)
                    ->columns(3),
            ], FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeStatementReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
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
        return static::canViewAny();
    }
}
