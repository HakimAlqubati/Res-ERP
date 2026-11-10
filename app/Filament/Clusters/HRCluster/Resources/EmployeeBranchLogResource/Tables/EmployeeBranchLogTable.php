<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeBranchLogResource\Tables;

use App\Filament\Resources\EmployeeResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeBranchLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->emptyStateHeading(__('lang.no_data'))
            ->defaultSort('start_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('lang.employee'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->url(fn (EmployeeBranchLog $record): ?string => $record->employee_id
                        ? EmployeeResource::getUrl('view', ['record' => $record->employee_id])
                        : null)
                    ->openUrlInNewTab(),

                TextColumn::make('previous_branch_name')
                    ->label(__('lang.previous_branch'))
                    ->getStateUsing(fn (EmployeeBranchLog $record) => $record->previous_branch?->name ?? '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('branch.name')
                    ->label(__('lang.transferred_to_branch'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('start_at')
                    ->label(__('lang.start_date'))
                    ->date(getDateFormat())
                    ->sortable(),

                TextColumn::make('end_at')
                    ->label(__('lang.end_date'))
                    ->date(getDateFormat())
                    ->placeholder(__('lang.ongoing_in_branch'))
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label(__('lang.transfer_status'))
                    ->getStateUsing(fn (EmployeeBranchLog $record) => $record->is_active ? __('lang.current_active_branch') : __('lang.past_branches'))
                    ->badge()
                    ->color(fn (string $state): string => $state === __('lang.current_active_branch') ? 'success' : 'gray'),

                TextColumn::make('createdBy.name')
                    ->label(__('lang.created_by'))
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->dateTime(getDateFormat() . ' H:i')
                    ->size('xs')
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label(__('lang.employee'))
                    ->options(fn () => Employee::whereIn('id', function ($q) {
                        $q->select('employee_id')
                            ->from('hr_employee_branch_logs')
                            ->groupBy('employee_id')
                            ->havingRaw('count(*) > 1');
                    })->forBranchManager()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->options(fn () => Branch::active()->forBranchManager('id')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('lang.transfer_status'))
                    ->options([
                        'active' => __('lang.current_active_branch'),
                        'past'   => __('lang.past_branches'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === 'active') {
                            return $query->active();
                        }
                        if (($data['value'] ?? null) === 'past') {
                            return $query->past();
                        }
                        return $query;
                    }),

                Filter::make('start_at')
                    ->label(__('lang.transfer_date'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('lang.from'))
                            ->native(false)
                            ->displayFormat(getDateFormat()),
                        DatePicker::make('to')
                            ->label(__('lang.to'))
                            ->native(false)
                            ->displayFormat(getDateFormat()),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_at', '<=', $date));
                    }),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([])
            ->toolbarActions([]);
    }
}
