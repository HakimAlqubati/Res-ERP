<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Models\Product;
use App\Models\StockInventory;
use App\Models\Store;
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
use Filament\Pages\Page;
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

    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return 'Stock Takes';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Stock Takes';
    }

    protected static ?string $pluralLabel = 'Stock Take';

    public static function getModelLabel(): string
    {
        return 'Stock Take';
    }
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
                            ->default(getDefaultStore())
                            ->options(
                                Store::active()
                                    ->withManagedStores()
                                    ->get(['name', 'id'])->pluck('name', 'id')
                            ),

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

                            Select::make('unit_id')->label('Unit')
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

                                    $inventoryService = new InventoryService($get('product_id'), $state, $get('store_id'));
                                    $remaningQty = $inventoryService->getInventoryReport()[0]['remaining_qty'] ?? 0;
                                    $set('system_quantity', $remaningQty);
                                    $difference =  static::getDifference($inventoryService, $get('physical_quantity'));
                                    $set('difference', $difference);
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),


                            TextInput::make('physical_quantity')->default(0)
                                ->numeric()->live()
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    $inventoryService = new InventoryService($get('product_id'), $get('unit_id'), $get('store_id'));
                                    $difference =  static::getDifference($inventoryService, $state);
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
                        ])->addActionLabel('Add Item')
                        ->columns(8),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->searchable(),
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

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListStockInventories::class,
            Pages\CreateStockInventory::class,
            Pages\EditStockInventory::class,
        ]);
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

    public static function getDifference($inventoryService, $physicalQty)
    {
        $remaningQty = $inventoryService->getInventoryReport()[0]['remaining_qty'] ?? 0;
        $difference = round($physicalQty - $remaningQty, 2);
        return $difference;
    }
}
