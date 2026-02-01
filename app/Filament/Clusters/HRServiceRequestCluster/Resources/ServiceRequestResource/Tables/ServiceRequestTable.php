<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Tables;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Actions\ServiceRequestActions;
use App\Models\Branch;
use App\Models\Equipment;
use App\Models\ServiceRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceRequestTable
{
    /**
     * Configure the table
     */
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->columns(static::getColumns())
            ->filters(static::getFilters(), layout: FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordUrl(fn(ServiceRequest $record): string => ServiceRequestResource::getUrl('view', ['record' => $record]))
            ->recordActions(ServiceRequestActions::getRecordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Get table columns - أعمدة الجدول
     */
    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->sortable()
                ->searchable(isIndividual: false)
                ->sortable()
                ->alignCenter(),

            TextColumn::make('equipment.name')
                ->label('Equipment')
                ->sortable()
                ->searchable()
                ->alignCenter(),

            TextColumn::make('description')
                ->searchable(isIndividual: true)
                ->sortable()
                ->color(Color::Blue)
                ->limit(50)
                ->wrap()
                ->tooltip(fn($state) => $state)
                ->description('Click')
                ->searchable(),

            TextColumn::make('status')
                ->badge()
                ->sortable()
                ->searchable()
                ->icon('heroicon-m-check-badge')
                ->searchable(isIndividual: false)
                ->colors([
                    'primary' => ServiceRequest::STATUS_NEW,
                    'warning' => ServiceRequest::STATUS_PENDING,
                    'info'    => ServiceRequest::STATUS_IN_PROGRESS,
                    'success' => ServiceRequest::STATUS_CLOSED,
                ]),

            TextColumn::make('urgency')
                ->badge()
                ->searchable()
                ->sortable()
                ->icon('heroicon-m-check-badge')
                ->colors([
                    'danger'  => ServiceRequest::URGENCY_HIGH,
                    'warning' => ServiceRequest::URGENCY_MEDIUM,
                    'success' => ServiceRequest::URGENCY_LOW,
                ])
                ->toggleable(isToggledHiddenByDefault: false),

            ImageColumn::make('first_photo_url')
                ->label('Photo')
                ->width(50)
                ->height(50)
                ->disabledClick(true)
                ->toggleable(isToggledHiddenByDefault: false)
                ->getStateUsing(fn($record) => $record->photos()->first()?->image_path),

            TextColumn::make('impact')
                ->badge()
                ->icon('heroicon-m-check-badge')
                ->searchable()
                ->sortable()
                ->colors([
                    'danger'  => ServiceRequest::IMPACT_HIGH,
                    'warning' => ServiceRequest::IMPACT_MEDIUM,
                    'success' => ServiceRequest::IMPACT_LOW,
                ])
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('branch.name')
                ->label('Branch')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('branchArea.name')
                ->label('Branch Area')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('createdBy.name')
                ->label('Created By')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('assignedTo.name')
                ->label('Assigned To')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->label('Created At')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('total_cost')
                ->label(__('Total Cost'))
                ->getStateUsing(fn($record) => $record->costs()->sum('amount'))
                ->money('MYR')
                ->sortable(query: function ($query, $direction) {
                    return $query->withSum('costs', 'amount')
                        ->orderBy('costs_sum_amount', $direction);
                })
                ->toggleable(isToggledHiddenByDefault: false),
        ];
    }

    /**
     * Get table filters - فلاتر الجدول
     */
    public static function getFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('Status'))
                ->options(ServiceRequest::STATUS_LABELS),

            SelectFilter::make('urgency')
                ->label(__('Urgency'))
                ->options(ServiceRequest::URGENCY_LABELS),

            SelectFilter::make('impact')
                ->label(__('Impact'))
                ->options(ServiceRequest::IMPACT_LABELS),

            SelectFilter::make('branch_id')
                ->label(__('Branch'))
                ->searchable()
                ->options(fn() => Branch::active()->pluck('name', 'id')),

            SelectFilter::make('equipment_id')
                ->label(__('Equipment'))
                ->searchable()
                ->options(fn() => Equipment::query()->pluck('name', 'id')),

            Filter::make('has_costs')
                ->label(__('Has Costs'))
                ->toggle()
                ->query(fn($query) => $query->whereHas('costs')),

            Filter::make('created_at')
                ->label(__('Created Date'))
                ->form([
                    DatePicker::make('from')->label(__('From')),
                    DatePicker::make('to')->label(__('To')),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['to'], fn($q) => $q->whereDate('created_at', '<=', $data['to']));
                }),
        ];
    }
}
