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
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas\StockInventoryForm;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Store;
use App\Services\MultiProductsInventoryService;
use App\Services\Stock\StockInventory\InventoryProductCacheService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker; 
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; 

class StockInventoryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('inventory_date')->sortable()->label('Date')->toggleable(),
                TextColumn::make('categories_names')->limit(40)
                    ->weight(FontWeight::Medium)->tooltip(fn($record): string => $record->categories_names)
                    ->wrap()->label('Categories')->toggleable(),
                TextColumn::make('details_count')->label('Products No')->alignCenter(true)->toggleable(),
                TextColumn::make('store.name')->sortable()->label('Store')->toggleable(),
                TextColumn::make('responsibleUser.name')->sortable()->label('Responsible')->toggleable(),
                IconColumn::make('finalized')->sortable()->label('Finalized')->boolean()->alignCenter(true)->toggleable(),

            ])
            ->filters([
                TrashedFilter::make(),
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
            ], FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make()
                    ->label('Finalize')
                    ->button()
                    ->hidden(fn($record): bool => $record->finalized),
                ViewAction::make()
                    ->visible(fn($record): bool => $record->finalized)
                    ->button()
                    ->icon('heroicon-o-eye')->color('success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
