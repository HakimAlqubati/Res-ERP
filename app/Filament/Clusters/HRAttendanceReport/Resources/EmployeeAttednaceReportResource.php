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
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeAttednaceReportResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $slug = 'employee-attendance-reports';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Attendance by employee';
    public static function getModelLabel(): string
    {
        return isStuff() ? 'My records' : 'Attendance by employee';
    }
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);

        return $table->deferFilters(false)
            ->emptyStateHeading('No data')
            ->columns([])
            ->filters([
                SelectFilter::make('employee_id')->label('Employee')->options(
                    function () {
                        return Employee::where('active', 1)
                            ->get()
                            ->mapWithKeys(function ($employee) {
                                return [$employee->id => $employee->name . ' - ' . $employee->id];
                            });
                    }
                )

                    ->hidden(fn() => isStuff() || isMaintenanceManager())
                    ->searchable(),
                

                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $endNextMonthData = getEndOfMonthDate(Carbon::parse($state)->year, Carbon::parse($state)->month);
                                $set('end_date', $endNextMonthData['end_month']);
                            })
                            ->label('Start Date')
                            ->default($currentMonthData['start_month']), // Use function for dynamic default value

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default($currentMonthData['end_month']), // Use function for dynamic default value
                    ]),
                Filter::make('show_extra_fields')
                    ->label('Show Extra')
                    ->schema([
                        Toggle::make('show_day')
                            ->inline(false)
                            ->label('Show Day')
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