<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Unit;
use App\Models\UnitPrice;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockInventoryResource extends Resource
{
    protected static ?string $model = StockInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 9;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('')->schema([
                    Grid::make()->columns(3)->schema([
                        DatePicker::make('inventory_date')
                            ->required()->default(now())
                            ->label('Inventory Date'),

                        Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()->default(getDefaultStore())
                            ->label('Store'),

                        Select::make('responsible_user_id')->searchable()->default(auth()->id())
                            ->relationship('responsibleUser', 'name')
                            ->required()
                            ->label('Responsible'),
                    ]),

                    Repeater::make('details')->hiddenOn('edit')
                        ->relationship('details')
                        ->label('Inventory Details')->columnSpanFull()
                        ->schema([
                            Select::make('product_id')
                                ->required()->columnSpan(2)
                                ->label('Product')->searchable()
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->pluck('name', 'id');
                                })
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                    ->unmanufacturingCategory()
                                    ->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('unit_id', null)),

                            Select::make('unit_id')
                                ->options(
                                    function (callable $get) {

                                        $unitPrices = UnitPrice::where('product_id', $get('product_id'))->get()->toArray();

                                        if ($unitPrices)
                                            return array_column($unitPrices, 'unit_name', 'unit_id');
                                        return [];
                                    }
                                )
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);

                                    $inventoryService = new InventoryService($get('product_id'), $state, $get('store_id'));
                                    // Get report for a specific product and unit
                                    $remaningQty = $inventoryService->getInventoryReport()[0]['remaining_qty'] ?? 0;
                                    $set('system_quantity', $remaningQty);
                                    $difference = round($remaningQty - $get('physical_quantity'), 2);
                                    $set('difference', $difference);
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),


                            TextInput::make('physical_quantity')->default(0)
                                ->numeric()->live()
                                ->afterStateUpdated(function($set,$state,$get){
                                    $inventoryService = new InventoryService($get('product_id'), $get('unit_id'), $get('store_id'));
                                    // Get report for a specific product and unit
                                    $remaningQty = $inventoryService->getInventoryReport()[0]['remaining_qty'] ?? 0;
                                    $set('system_quantity', $remaningQty);
                                    $difference = round($remaningQty - $state, 2);
                                    $set('difference', $difference);
                                })
                                ->label('Physical Qty')
                                ->required(),

                            TextInput::make('system_quantity')->readOnly()
                                ->numeric()
                                ->label('System Qty')
                                ->required(),
                            TextInput::make('difference')->readOnly()
                                ->numeric(),
                        ])
                        ->columns(8),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('inventory_date')->sortable()->label('Date'),
                TextColumn::make('store.name')->sortable()->label('Store'),
                TextColumn::make('responsibleUser.name')->sortable()->label('Responsible'),
                TextColumn::make('details_count')->label('Products No')->alignCenter(true),
                IconColumn::make('finalized')->sortable()->label('Finalized')->boolean()->alignCenter(true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockInventories::route('/'),
            'create' => Pages\CreateStockInventory::route('/create'),
            'edit' => Pages\EditStockInventory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
