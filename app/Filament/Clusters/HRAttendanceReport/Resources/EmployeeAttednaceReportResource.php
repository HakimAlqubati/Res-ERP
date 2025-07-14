<?php
namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeAttednaceReportResource extends Resource
{
    protected static ?string $model          = Attendance::class;
    protected static ?string $slug           = 'employee-attendance-reports';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label   = 'Attendance by employee';
    public static function getModelLabel(): string
    {
        return isStuff() ? 'My records' : 'Attendance by employee';
    }
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);

        return $table
            ->emptyStateHeading('No data')
            ->columns([])
            ->filters([
                SelectFilter::make('employee_id')->label('Employee')
                ->options(function ($search = null) {
                    return Employee::query()
                        ->where('active', 1)
                        // ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                })
                ->getSearchResultsUsing(function ($search = null) {
                    return Employee::query()
                        ->where('active', 1)
                        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn($employee) => [$employee->id => "{$employee->name} - {$employee->id}"]);
                })

                    ->hidden(fn() => isStuff() || isMaintenanceManager())
                    ->searchable(),

                Filter::make('date_range')
                    ->form([
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
                    ->form([
                        Toggle::make('show_day')
                            ->inline(false)
                            ->label('Show Day'),
                    ]),

            ], FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListEmployeeAttednaceReports::route('/'),
        ];
    }
}