<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\RelationManagers;
use App\Filament\Resources\StockIssueOrderResource\Pages\StockInvetoryQuickLinks;
use App\Models\Product;
use App\Models\StockIssueOrder;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Services\MultiProductsInventoryService;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockIssueOrderResource extends Resource
{
    protected static ?string $model = StockIssueOrder::class;

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
                        // ->relationship('store', 'name')
                        ->options(
                            Store::active()
                                ->withManagedStores()
                                ->get(['name', 'id'])->pluck('name', 'id')
                        )
                        ->default(getDefaultStore())
                        ->required()
                        ->label('Store'),



                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull(),

                    Textarea::make('cancel_reason')
                        ->label('Cancel Reason')
                        ->hidden(fn($get) => $get('cancelled') == 0),

                    Repeater::make('details')
                        ->relationship('details')->columnSpanFull()
                        ->schema([
                            Select::make('product_id')
                                ->required()->columnSpan(2)
                                ->label('Product')->searchable()
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
                                ->afterStateUpdated(fn(callable $set) => $set('unit_id', null)),

                            Select::make('unit_id')->label('Unit')
                                ->options(function (callable $get) {
                                    $product = \App\Models\Product::find($get('product_id'));
                                    if (! $product) return [];

                                    return $product->unitPrices->pluck('unit.name', 'unit_id')->toArray();
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $unitPrice = UnitPrice::where(
                                        'product_id',
                                        $get('product_id')
                                    )
                                        ->showInInvoices()
                                        ->where('unit_id', $state)->first();
                                    $set('price', $unitPrice->price);

                                    $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                    $set('package_size',  $unitPrice->package_size ?? 0);

                                    $service = new  MultiProductsInventoryService(null, $get('product_id'), $state);
                                    $remainingQty = $service->getInventoryForProduct($get('product_id'))[0]['remaining_qty'] ?? 0;
                                    $set('remaining_quantity', $remainingQty);
                                })->columnSpan(2)->required(),
                            TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                ->label(__('lang.package_size')),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0.1)
                                ->label('Quantity')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($get, $set, $state) {
                                    $service = new  MultiProductsInventoryService(null, $get('product_id'), $get('unit_id'));
                                    $remainingQty = $service->getInventoryForProduct($get('product_id'))[0]['remaining_qty'] ?? 0;
                                    $set('remaining_quantity', $remainingQty);
                                })
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, Closure $fail) use ($get) {
                                            $remainingQty = (float) $get('remaining_quantity');
                                            if ($value > $remainingQty) {
                                                $fail("Quantity cannot exceed remaining stock ({$remainingQty}).");
                                            }
                                        };
                                    },
                                ]),
                            TextInput::make('remaining_quantity')
                                ->numeric()
                                ->readOnly()
                                ->label('Remaining Qty')



                        ])
                        ->minItems(1)
                        ->label('Issued Items')
                        ->columns(7),
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
                TextColumn::make('createdBy.name')->label('Created By'),
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
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListStockIssueOrders::route('/'),
            'create' => Pages\CreateStockIssueOrder::route('/create'),
            'edit' => Pages\EditStockIssueOrder::route('/{record}/edit'),
            'view' => Pages\ViewStockIssueOrder::route('/{record}'),
            'quick-links' => StockInvetoryQuickLinks::route('/quick-links'),

        ];
    }


    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListStockIssueOrders::class,
            Pages\CreateStockIssueOrder::class,
            Pages\EditStockIssueOrder::class,
            Pages\ViewStockIssueOrder::class,
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
}
