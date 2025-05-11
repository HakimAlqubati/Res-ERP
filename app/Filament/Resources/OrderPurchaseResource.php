<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\OrderPurchaseResource\Pages\CreateOrderPurchase;
use App\Filament\Resources\OrderPurchaseResource\Pages\EditOrderPurchase;
use App\Filament\Resources\OrderPurchaseResource\Pages\ListOrderPurchase;
use App\Filament\Resources\OrderPurchaseResource\Pages\ViewOrderPurchase;
use App\Filament\Resources\OrderResource\RelationManagers\OrderDetailsRelationManager;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderPurchased;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitPrice;
use App\Tables\Columns\count_items_order;
use App\Tables\Columns\TotalOrder;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderPurchaseResource extends Resource
{
    protected static ?string $model = OrderPurchased::class;
    protected static ?string $slug = 'purchased-orders';
    // protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $cluster = MainOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Order Purchased';
    // protected static bool $shouldRegisterNavigation = false;
    public static function getPluralLabel(): ?string
    {
        return __('lang.purchased_orders');
    }

    // public static function getLabel(): ?string
    // {
    //     return __('lang.purchased_orders');
    // }
    // protected static function getNavigationLabel(): string
    // {
    //     return __('lang.purchased_orders');
    // }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Grid::make()->columns(4)->schema([
                        Select::make('branch_id')->required()
                            ->label(__('lang.branch'))->columnSpan(2)
                            ->options(Branch::withAccess()
                            ->active()->get(['id', 'name'])->pluck('name', 'id')),
                        Select::make('supplier_id')->label(__('lang.supplier'))->columnSpan(2)->required()
                            ->getSearchResultsUsing(fn(string $search): array => Supplier::where('name', 'like', "%{$search}%")->limit(10)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn($value): ?string => Supplier::find($value)?->name)
                            ->searchable()
                            ->options(Supplier::limit(5)->get(['id', 'name'])->pluck('name', 'id')),
                    ]),
                    // Repeater for Order Details
                    Repeater::make('orderDetails')->columnSpanFull()->hiddenOn(['view', 'edit'])
                        ->label(__('lang.order_details'))->columns(9)
                        ->relationship() // Relationship with the OrderDetails model
                        ->schema([
                            Select::make('product_id')
                                ->label(__('lang.product'))
                                ->searchable()
                                // ->disabledOn('edit')
                                ->options(function () {
                                    return Product::where('active', 1)
                                        ->unmanufacturingCategory()
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ]);
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Product::where('active', 1)
                                        ->where(function ($query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })->unmanufacturingCategory()
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($product) => [
                                            $product->id => "{$product->code} - {$product->name}"
                                        ])
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->code . ' - ' . Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(fn(callable $set) => $set('unit_id', null))
                                ->searchable()->columnSpan(function ($record) {

                                    if ($record) {
                                        return 2;
                                    } else {
                                        return 3;
                                    }
                                })->required(),
                            Select::make('unit_id')
                                ->label(__('lang.unit'))
                                // ->disabledOn('edit')
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
                            TextInput::make('purchase_invoice_id')->label(__('lang.purchase_invoice_id'))->readOnly()->visibleOn('view'),
                            TextInput::make('package_size')->label(__('lang.package_size'))->readOnly()->columnSpan(1),
                            Hidden::make('available_quantity')
                                ->default(1),
                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $set('available_quantity', $state);

                                    $set('total_price', ((float) $state) * ((float)$get('price') ?? 0));
                                })

                                ->required()->default(1),

                            TextInput::make('price')
                                ->label(__('lang.price'))->readOnly()
                                ->numeric()
                                ->required()->columnSpan(1),
                            TextInput::make('total_price')
                                ->label(__('lang.total_price'))
                                ->numeric()
                                ->readOnly()->columnSpan(1),
                        ])

                        ->createItemButtonLabel(__('lang.add_detail')) // Customize button label
                        ->required(),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                TextColumn::make('id')->label(__('lang.order_id'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()->alignCenter(true)
                    ->color('primary')
                    ->weight(FontWeight::Bold)
                    ->copyMessage(__('lang.order_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('customer.name')->label(__('lang.branch_manager'))->toggleable()
                    ->searchable(isIndividual: true)->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn(Model $record): string => "By {$record->customer->name}"),
                TextColumn::make('branch.name')->label(__('lang.branch')),
                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                TextColumn::make('total_amount')->label(__('lang.total_amount'))->alignCenter(true)
                    ->numeric(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable()->default('-'),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

            ])
            ->defaultSort('id', 'desc')
            ->actions([
                // ViewAction::make(),
                // EditAction::make(),
                // DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListOrderPurchase::route('/'),
            'create' => CreateOrderPurchase::route('/create'),
            'edit' => EditOrderPurchase::route('/{record}/edit'),
            'view' => ViewOrderPurchase::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_purchased', 1)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_purchased', 1)->count();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_order-purchased');
    }
    public static function canCreate(): bool
    {
        return auth()->user()->can('create_order-purchased');
    }
    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view_order-purchased');
    }

}
