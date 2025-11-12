<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products\Tables;

use App\Models\InventoryTransaction;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                // TextColumn::make('default_store')
                //     ->label('Default Store')
                //     ->alignCenter(true)
                //     ->getStateUsing(function (Model $record) {
                //         // dd('sdf');
                //         $store = defaultManufacturingStore($record);
                //         return $store->name ?? '-';
                //         return $record->defaultManufacturingStore->name ?? '-';
                //     }) 
                //     ,
                TextColumn::make('formatted_unit_prices')
                    ->label('Unit Prices')->toggleable(isToggledHiddenByDefault: false)
                    ->limit(50)->tooltip(fn($state) => $state)
                // ->alignCenter(true)
                ,
                TextColumn::make('description')->searchable()
                    ->searchable(isIndividual: false, isGlobal: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.description')),
                IconColumn::make('is_manufacturing')->boolean()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('lang.is_manufacturing')),
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
            ])
            ->recordActions([
                Action::make('testPos')
                    ->schema([
                        Select::make('store_id')->columnSpanFull()->options(Store::query()->pluck('name', 'id')),
                        TextInput::make('qty')->columnSpanFull(),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, $data) {
                        foreach ($record->productItems as $item) {
                            $neededQty = $item->quantity * (1 + $item->qty_waste_percentage / 100) * 2;
                            $pSize = $item->package_size ?? UnitPrice::where('product_id', $item->product_id)->where('unit_id', $item->unit_id)?->first()?->package_size;
                            
                             InventoryTransaction::create([
                                'product_id' => $item->product_id,
                                'store_id'   => $data['store_id'],
                                'unit_id'    => $item->unit_id,
                                'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                                'quantity'   => $neededQty,
                                'package_size' => $pSize,
                                'price'      => 0, // optional
                                'notes'      => "Consumed in POS sale of {$record->name}",
                            ]);
                        }
                    }),
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
