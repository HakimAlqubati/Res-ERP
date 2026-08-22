<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Tables;


use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use App\Models\Category;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\ListStockInventories;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\CreateStockInventory;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\EditStockInventory;
use App\Models\UnitPrice;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas\StockInventoryForm;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Store;
use App\Exports\StockInventoriesExport;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;
use App\Services\MultiProductsInventoryService;
use App\Services\Stock\StockInventory\InventoryProductCacheService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockInventoryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->recordUrl(fn(StockInventory $record): string => StockInventoryResource::getUrl('edit', ['record' => $record]))

            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->sortable()->label('ID')->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('inventory_date')->sortable()->label('Date')->toggleable(),
                TextColumn::make('categories_names')->limit(40)
                    ->weight(FontWeight::Medium)->tooltip(fn($record): string => $record->categories_names)
                    ->wrap()->label('Categories')->toggleable(),
                TextColumn::make('details_count')->label('Products No')->alignCenter(true)->toggleable(),
                TextColumn::make('store.name')->sortable()->label('Store')->toggleable(),
                TextColumn::make('closing_stock_value')
                    ->label('Closing Stock Value')
                    ->sortable(false)
                    ->formatStateUsing(fn($state) => formatMoneyWithCurrency($state))
                    ->toggleable()
                    ->summarize(
                        Summarizer::make()
                            ->using(function (Table $table) {
                                $total = $table->getRecords()->sum('closing_stock_value');
                                return is_numeric($total) ? formatMoneyWithCurrency($total) : $total;
                            })
                    )
                    ->visible(fn($record)=> ( (isSuperAdmin() || isHakim()) )),
 
                TextColumn::make('responsibleUser.name')
                ->limit(15)
                ->tooltip(fn($state) => $state)
                ->sortable()->label('Responsible')->toggleable(),
                IconColumn::make('finalized')->sortable()->label('Finalized')->boolean()->alignCenter(true)->toggleable(),
                TextColumn::make('adjustment_date')
                    ->label('Finalized Date')
                    ->date('Y-m-d')
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        if (!$record->finalized) return null;
                        return \App\Models\StockAdjustmentDetail::where('source_id', $record->id)
                            ->where('source_type', get_class($record))
                            ->latest('adjustment_date')
                            ->value('adjustment_date') ?? $record->updated_at;
                    })
                    ->placeholder('-'),

                    TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('created_at'),

            ])->deferFilters(true)->filtersFormColumns(4)
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('store_id')
                    ->options(function () {
                        return Store::whereIn('id', function ($query) {
                            $query->select('store_id')
                                ->from('stock_inventories')
                                ->whereNotNull('store_id')
                                ->distinct();
                        })
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->label('Store')
                    ->searchable(),
                Filter::make('inventory_date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('inventory_date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('inventory_date', '<=', $date));
                    }),
            ], FiltersLayout::Modal)

            ->recordActions([
                EditAction::make()
                    ->label('Finalize')
                    ->button()
                    ->hidden(fn($record): bool => $record->finalized),
                // ActionGroup::make([
                //     ViewAction::make()
                //         ->visible(fn($record): bool => $record->finalized)
                //         ->button()
                //         ->icon('heroicon-o-eye')->color('success'),
                    \Filament\Actions\Action::make('value_details')
                        ->label('Stock Value')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->button()
                        ->visible(fn($record)=> (isSuperAdmin()
                        && $record->finalized
                        ))
                        ->hidden()
                        // ->visible(fn()=>isHakimOrAdel())
                        ->url(fn($record): string => StockInventoryResource::getUrl('value-details', ['record' => $record])),
                // ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(new StockInventoriesExport($records), 'stock_inventories.xlsx');
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn()=>isSuperAdmin())
                        ,
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => StockInventoryResource::canDeleteAny()),
                        RestoreBulkAction::make()
                        ->visible(fn(): bool => StockInventoryResource::canDeleteAny()),
                    ForceDeleteBulkAction::make()
                        ->visible(fn(): bool => StockInventoryResource::canForceDeleteAny()),
                ]),
            ]);
    }
}
