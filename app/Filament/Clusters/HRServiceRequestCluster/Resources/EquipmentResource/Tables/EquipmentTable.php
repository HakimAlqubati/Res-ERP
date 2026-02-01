<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Tables;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\EquipmentResource\Actions\EquipmentActions;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EquipmentTable
{
    /**
     * Configure the table
     */
    public static function configure(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns(static::getColumns())
            ->filters(static::getFilters(), layout: FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->recordUrl(fn(Equipment $record): string => EquipmentResource::getUrl('view', ['record' => $record]))
            ->recordActions(static::getRecordActions())
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
            SpatieMediaLibraryImageColumn::make('attachments')->label('')
                ->width(10)
                ->circular()->alignCenter(true)->getStateUsing(function () {
                    return null;
                })->limit(2),

            TextColumn::make('name')->toggleable()
                ->searchable()
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'success' => Equipment::STATUS_ACTIVE,
                    'warning' => Equipment::STATUS_UNDER_MAINTENANCE,
                    'danger'  => Equipment::STATUS_RETIRED,
                ])->alignCenter(true)->toggleable(),

            TextColumn::make('asset_tag')
                ->searchable()->toggleable()
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('qr_code')
                ->searchable()->toggleable()->hidden(),

            TextColumn::make('make')->toggleable()
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('model')->toggleable()
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('serial_number')->toggleable()
                ->searchable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('branch.name')->toggleable()
                ->label('Branch')
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('branchArea.name')->toggleable()
                ->label('Branch Area')
                ->sortable()->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('purchase_price')->toggleable()
                ->money('USD')
                ->sortable()->toggleable(isToggledHiddenByDefault: true),

            ImageColumn::make('profile_picture')->toggleable()
                ->label('Profile Picture')
                ->rounded()->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')->toggleable()
                ->label('Created At')
                ->dateTime()
                ->sortable()->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('operation_start_date')
                ->label('Operation Start')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('warranty_end_date')
                ->label('Warranty End')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('next_service_date')
                ->label('Next Service')
                ->date()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            IconColumn::make('has_purchase_cost_synced')
                ->label(__('Purchase Cost'))
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->alignCenter()
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
                ->options(Equipment::STATUS_LABELS),

            SelectFilter::make('type_id')
                ->label(__('Type'))
                ->searchable()
                ->options(fn() => EquipmentType::active()->pluck('name', 'id')),

            SelectFilter::make('branch_id')
                ->label(__('Branch'))
                ->searchable()
                ->options(fn() => Branch::selectable()->active()->pluck('name', 'id')),

            SelectFilter::make('branch_area_id')
                ->label(__('Branch Area'))
                ->searchable()
                ->options(fn() => BranchArea::pluck('name', 'id')),

            Filter::make('warranty_expired')
                ->label(__('Warranty Expired'))
                ->toggle()
                ->query(fn($query) => $query->whereDate('warranty_end_date', '<', now())),

            Filter::make('service_due')
                ->label(__('Service Due'))
                ->toggle()
                ->query(fn($query) => $query->whereDate('next_service_date', '<=', now())),

            Filter::make('has_costs')
                ->label(__('Has Costs'))
                ->toggle()
                ->query(fn($query) => $query->whereHas('costs')),

            Filter::make('purchase_date')
                ->label(__('Purchase Date'))
                ->form([
                    DatePicker::make('from')->label(__('From')),
                    DatePicker::make('to')->label(__('To')),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'], fn($q) => $q->whereDate('purchase_date', '>=', $data['from']))
                        ->when($data['to'], fn($q) => $q->whereDate('purchase_date', '<=', $data['to']));
                }),
        ];
    }

    /**
     * Get record actions - إجراءات السجل
     */
    public static function getRecordActions(): array
    {
        return [
            EditAction::make(),
            Action::make('qrCodePrint')
                ->label('Print')
                ->button()->icon('heroicon-o-qr-code')
                ->url(fn($record): string => route('testQRCode', ['id' => $record->id]), true),
            EquipmentActions::getSyncPurchaseCostAction(),
        ];
    }
}
