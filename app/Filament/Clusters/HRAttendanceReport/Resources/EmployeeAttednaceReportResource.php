<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages\ListEmployeeAttednaceReports;
use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeAttednaceReportResource extends Resource
{
    protected static ?string $model          = Attendance::class;
    protected static ?string $slug           = 'employee-attendance-reports';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::ChartBarSquare;

    protected static ?string $cluster = HRAttendanceReport::class;
    public static function getModelLabel(): string
    {
        return isStuff() ? __('lang.my_attendance') : __('lang.attendance_by_employee');
    }

    public static function getNavigationLabel(): string
    {
        return isStuff() ? __('lang.my_attendance') : __('lang.attendance_by_employee');
    }

    public static function getPluralLabel(): string
    {
        return isStuff() ? __('lang.my_attendance') : __('lang.attendance_by_employee');
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;

    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);

        return $table->deferFilters(false)
            ->emptyStateHeading(__('lang.no_data'))

            ->filters([
                SelectFilter::make('employee_id')
                    ->placeholder(__('lang.choose'))
                    ->label(__('lang.employee'))
                    ->options(function ($search = null) {
                        return Employee::query()
                            ->select('id', 'name')
                            ->where('active', 1)
                            ->forBranchManager()
                            // ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                    })
                    ->getSearchResultsUsing(function ($search = null) {
                        return Employee::query()
                            ->select('id', 'name')
                            ->where('active', 1)
                            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                    })

                    ->hidden(fn() => isStuff() || isMaintenanceManager())
                    ->searchable(),

                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $endNextMonthData = getEndOfMonthDate(Carbon::parse($state)->year, Carbon::parse($state)->month);
                                $set('end_date', $endNextMonthData['end_month']);
                            })
                            ->label(__('lang.start_date'))
                            ->default($currentMonthData['start_month']), // Use function for dynamic default value

                        DatePicker::make('end_date')
                            ->label(__('lang.end_date'))
                            ->default($currentMonthData['end_month']), // Use function for dynamic default value
                    ]),
                Filter::make('show_extra_fields')
                    ->label(__('lang.show_extra'))
                    ->schema([
                        Toggle::make('show_day')
                            ->inline(false)
                            ->label(__('lang.show_day')),
                    ]),

            ], FiltersLayout::AboveContent)
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeAttednaceReports::route('/'),
        ];
    }
}
