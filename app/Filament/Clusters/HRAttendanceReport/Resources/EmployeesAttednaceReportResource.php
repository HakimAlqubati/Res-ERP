<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeesAttednaceReportResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ChartBarSquare;
    protected static ?string $slug = 'employees-attendance-report';
    protected static ?string $cluster = HRAttendanceReport::class;

    public static function getModelLabel(): string
    {
        return __('lang.attendance_by_branch');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.attendance_by_branch');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.attendance_by_branch');
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('lang.please_select_branch'))->deferFilters(false)
            ->filters([
                SelectFilter::make('branch_id')->label(__('lang.choose_a_branch'))->options(
                    Branch::selectable()
                        ->forBranchManager('id')
                        ->select('id', 'name')
                        ->get()
                        ->pluck('name', 'id')

                )->searchable()->placeholder(__('lang.please_select_branch')),
                Filter::make('filter_date')->label('')->schema([
                    DatePicker::make('date')
                        ->label(__('lang.date'))->default(date('Y-m-d')),
                ]),

            ], FiltersLayout::AboveContent);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeesAttednaceReport::route('/'),
        ];
    }


    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
