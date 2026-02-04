<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products\Tables;

use App\Models\InventoryTransaction;
use App\Models\PosSale;
use App\Filament\Clusters\POSIntegration\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn(Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label(__('lang.id'))
                    ->copyable()
                    ->copyMessage(__('lang.product_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(isIndividual: false, isGlobal: true),
                TextColumn::make('code')
                    ->label(__('lang.code'))->copyable()->sortable()
                    ->searchable(isIndividual: false, isGlobal: true),

                TextColumn::make('name')
                    ->label(__('lang.name'))
                    ->toggleable()

                    ->searchable(isIndividual: false, isGlobal: true)
                    ->tooltip(fn(Model $record): string => "By {$record->name}"),


                TextColumn::make('waste_stock_percentage')
                    ->label('Waste %')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true),
                TextColumn::make('minimum_stock_qty')
                    ->label('Min. Qty')->sortable()
                    ->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('formatted_unit_prices')
                    ->label('Unit Prices')->toggleable(isToggledHiddenByDefault: false)
                    ->limit(50)->tooltip(fn($state) => $state),
                TextColumn::make('product_items_count')
                    ->label(__('lang.product_items_count'))
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('description')->searchable()
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.description')),

                TextColumn::make('category.name')->searchable()->label(__('lang.category'))->alignCenter(true)
                    ->searchable(isIndividual: false, isGlobal: false)->toggleable(),
                CheckboxColumn::make('active')->label('Active?')
                    ->sortable()->label(__('lang.active'))->toggleable()->alignCenter(true)
                    ->updateStateUsing(function (Product $record, $state) {
                        try {
                            $record->update(['active' => $state]);
                        } catch (Exception $e) {
                            showWarningNotifiMessage('Faild', $e->getMessage());
                        }
                    }),
                TextColumn::make('product_items_count')->label('Items No')
                    ->toggleable(isToggledHiddenByDefault: true)->default('-')->alignCenter(true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('category_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.category'))->relationship('category', 'name'),
            ])
            ->recordActions([

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
