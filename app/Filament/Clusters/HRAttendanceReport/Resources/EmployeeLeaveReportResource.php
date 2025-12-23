<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeApplicationV2;
use App\Models\LeaveType;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeLeaveReportResource extends Resource
{
    protected static ?string $model = EmployeeApplicationV2::class;
    protected static ?string $slug = 'employee-leave-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = HRAttendanceReport::class;

    public static function getModelLabel(): string
    {
        return __('lang.leave_report');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lang.leave_report');
    }

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading(__('lang.no_data'))
            ->striped()
            ->columns([
                TextColumn::make('id')
                    ->label(__('lang.id'))
                    ->sortable()
                    ->searchable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employee.employee_no')
                    ->label(__('lang.employee_number'))
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('employee.name')
                    ->label(__('lang.name'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(20),

                TextColumn::make('employee.branch.name')
                    ->label(__('lang.branch'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('leave_type_name')
                    ->label(__('lang.leave_type'))
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('detail_days_count')
                    ->label(__('lang.count_days'))
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('detail_from_date')
                    ->label(__('lang.from_date'))
                    ->date()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('detail_to_date')
                    ->label(__('lang.to_date'))
                    ->date()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('application_date')
                    ->label(__('lang.request_date'))
                    ->date()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(true)
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(fn() => Branch::where('active', 1)->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->whereHas(
                                'employee',
                                fn(Builder $q) => $q->where('branch_id', $value)
                            )
                        );
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('employee_id')
                    ->label(__('lang.employee'))
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('leave_type')
                    ->label(__('lang.leave_type'))
                    ->options(fn() => LeaveType::pluck('name', 'id'))
                    // ->query(function (Builder $query, array $data): Builder {
                    //     return $query->when(
                    //         $data['value'],
                    //         fn(Builder $query, $value): Builder => $query->whereJsonContains('details->leave_type_id', (int) $value)
                    //     );
                    // })
                    ->searchable(),

                SelectFilter::make('status')
                    ->label(__('lang.status'))
                    ->options([
                        EmployeeApplicationV2::STATUS_APPROVED => __('lang.approved'),
                        EmployeeApplicationV2::STATUS_PENDING => __('lang.pending'),
                        EmployeeApplicationV2::STATUS_REJECTED => __('lang.rejected'),
                    ]),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->defaultSort('application_date', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeLeaveReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('application_type_id', EmployeeApplicationV2::APPLICATION_TYPE_LEAVE_REQUEST)
            ->where('status', EmployeeApplicationV2::STATUS_APPROVED)
            ->with(['employee', 'employee.branch']);
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
