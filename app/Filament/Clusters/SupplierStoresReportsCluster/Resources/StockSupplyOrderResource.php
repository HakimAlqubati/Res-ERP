<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\RelationManagers;
use App\Models\Product;
use App\Models\StockSupplyOrder;
use App\Models\Store;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockSupplyOrderResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('')->schema([
                    DatePicker::make('order_date')
                        ->required()->default(now())
                        ->label('Order Date'),

                    Select::make('store_id')
                        ->default(getDefaultStore())
                        ->options(
                            Store::active()
                                ->withManagedStores()
                                ->get(['name', 'id'])->pluck('name', 'id')
                        )->required(),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull(),


                    Repeater::make('details')->columnSpanFull()
                        ->relationship('details')
                        ->schema([
                            Select::make('product_id')
                                ->required()->columnSpan(2)
                                ->label('Product')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        // ->unmanufacturingCategory()
                                        ->pluck('name', 'id');
                                })->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                    // ->unmanufacturingCategory()
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
                                    $set('price', $unitPrice->price);

                                    $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice->package_size ?? 0);
                                })->columnSpan(2)->required(),

                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),

                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->label('Quantity'),

                        ])
                        ->minItems(1)
                        ->label('Order Details')
                        ->columns(6),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('order_date')->sortable()->label('Order Date'),
                TextColumn::make('store.name')->label('Store'),
                TextColumn::make('item_count')->label('Products Count')->alignCenter(true),
                TextColumn::make('notes')->limit(50)->label('Notes'),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockSupplyOrders::route('/'),
            'create' => Pages\CreateStockSupplyOrder::route('/create'),
            'edit' => Pages\EditStockSupplyOrder::route('/{record}/edit'),
            'view' => Pages\ViewStockSupplyOrder::route('/{record}/view'),
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

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
