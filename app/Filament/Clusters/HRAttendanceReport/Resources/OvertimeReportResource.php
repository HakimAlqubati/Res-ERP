<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\HRAttendanceReport\Resources\OvertimeReportResource\Pages\ListOvertimeReports;
use App\Filament\Clusters\HRAttendanceReport;
use App\Models\EmployeeOvertime;
use App\Models\Employee;
use App\Models\Branch;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OvertimeReportResource extends Resource
{
    protected static ?string $model = EmployeeOvertime::class;
    protected static ?string $slug  = 'overtime-reports';
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Clock;

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?int $navigationSort = 5;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('lang.overtime_report');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.overtime_report');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.overtime_report');
    }

    public static function table(Table $table): Table
    {
        $currentMonthData = getEndOfMonthDate(Carbon::now()->year, Carbon::now()->month);

        return $table->deferFilters(false)
            ->emptyStateHeading(__('lang.no_data'))
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->placeholder(__('lang.choose'))
                    ->options(Branch::active()->forBranchManager('id')->get()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('employee_id')
                    ->placeholder(__('lang.choose'))
                    ->label(__('lang.employee'))
                    ->options(function () {
                        return Employee::query()
                            ->select('id', 'name')
                            ->where('active', 1)
                            ->forBranchManager()
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn($e) => [$e->id => "{$e->name} - {$e->id}"]);
                    })
                    ->getSearchResultsUsing(function ($search = null) {
                        return Employee::query()
                            ->select('id', 'name', 'employee_no')
                            ->when($search, fn($q) => $q->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%")
                                    ->orWhere('employee_no', 'like', "%{$search}%");
                            }))
                            ->limit(5)
                            ->get()
                            ->mapWithKeys(fn($e) => [$e->id => "{$e->name} - {$e->id}"]);
                    })
                    ->searchable(),

                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('lang.start_date'))
                            ->default($currentMonthData['start_month']),
                        DatePicker::make('date_to')
                            ->label(__('lang.end_date'))
                            ->default($currentMonthData['end_month']),
                    ]),

                SelectFilter::make('status')
                    ->label(__('lang.status'))
                    ->options([
                        EmployeeOvertime::STATUS_PENDING  => __('lang.pending'),
                        EmployeeOvertime::STATUS_APPROVED => __('lang.approved'),
                        EmployeeOvertime::STATUS_REJECTED => __('lang.rejected'),
                    ]),

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
            'index' => ListOvertimeReports::route('/'),
        ];
    }
}
