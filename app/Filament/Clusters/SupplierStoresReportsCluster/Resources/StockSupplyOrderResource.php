<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages\ListStockSupplyOrders;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages\CreateStockSupplyOrder;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages\EditStockSupplyOrder;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages\ViewStockSupplyOrder;
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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class StockSupplyOrderResource extends Resource
{
    protected static ?string $model = StockSupplyOrder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 8;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->label('')->columnSpanFull()->schema([
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
                                ->required()
                                ->columnSpan(2)
                                ->label('Product')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ]);
                                })
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Product::where('active', 1)
                                        ->where(function ($query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(function ($set, $state) {
                                    $set('unit_id', null);
                                    $product = Product::find($state);
                                    $set('waste_stock_percentage', $product?->waste_stock_percentage);
                                }),

                            Select::make('unit_id')->label('Unit')
                                ->options(function (callable $get) {
                                    $product = Product::find($get('product_id'));
                                    if (! $product) return [];

                                    return $product->supplyUnitPrices
                                    ->pluck('unit.name','unit_id')?->toArray() ?? [];
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )
                                        
                                        ->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);

                                    $set('total_price', ((float) $unitPrice?->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice?->package_size ?? 0);
                                })->columnSpan(2)->required(),

                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),

                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0.0001)
                                ->label('Quantity'),
                            TextInput::make('waste_stock_percentage')
                                ->label('Waste %')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->default(function (callable $get) {
                                    $productId = $get('product_id');
                                    return Product::find($productId)?->waste_stock_percentage ?? 0;
                                })
                                ->columnSpan(1),

                        ])
                        ->minItems(1)
                        ->label('Order Details')
                        ->columns(7),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('id')->copyable()
                    ->toggleable()->searchable(isIndividual: true)->alignCenter(true)
                    ->color('primary')
                    ->weight(FontWeight::Bold),
                TextColumn::make('order_date')->sortable()->label('Order Date')
                    ->toggleable(),
                TextColumn::make('store.name')->label('Store')->toggleable()->searchable(),
                TextColumn::make('item_count')->label('Products Count')->alignCenter(true)->toggleable(),
                TextColumn::make('notes')->limit(50)->label('Notes'),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true)->boolean()->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created at')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('creator.name')
                    ->label('Created By')->toggleable(isToggledHiddenByDefault: false)->sortable(),
                IconColumn::make('has_outbound_transactions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Untouched')->boolean()->alignCenter(),
            ])
            ->filters([
                SelectFilter::make("store_id")->placeholder('Select Store')
                    ->label(__('lang.store'))->searchable()
                    ->options(
                        Store::active()->get()->pluck('name', 'id')->toArray()
                    ),
            ], FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([


                    EditAction::make(),
                    ViewAction::make(),
                    Action::make('cancel')
                        ->label('Cancel')->button()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3)->columnSpanFull(),
                        ])
                        ->action(function (array $data, StockSupplyOrder $record) {
                            try {
                                DB::beginTransaction();

                                $result = $record->handleCancellation($record, $data['reason']);

                                DB::commit();

                                if (! $result['status']) {
                                    showWarningNotifiMessage('Failed', $result['message']);
                                }
                                if ($result['status'])
                                    showSuccessNotifiMessage('Success', $result['message']);
                            } catch (Throwable $e) {
                                DB::rollBack();
                                showWarningNotifiMessage('Error', $e->getMessage());
                                report($e);
                            }
                        })
                        ->visible(fn(StockSupplyOrder $record) => ! $record->cancelled)
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
            'index' => ListStockSupplyOrders::route('/'),
            'create' => CreateStockSupplyOrder::route('/create'),
            'edit' => EditStockSupplyOrder::route('/{record}/edit'),
            'view' => ViewStockSupplyOrder::route('/{record}/view'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListStockSupplyOrders::class,
            CreateStockSupplyOrder::class,
            EditStockSupplyOrder::class,
            ViewStockSupplyOrder::class,
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

    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Blue;
    }
}