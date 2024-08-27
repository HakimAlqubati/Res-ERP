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
use App\Models\Product;
use App\Models\UnitPrice;
use App\Tables\Columns\count_items_order;
use App\Tables\Columns\TotalOrder;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderPurchaseResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $slug = 'purchased-orders';
    // protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $cluster = MainOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
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
                DatePicker::make('order_date')
                    ->required()
                    ->placeholder('Select date')
                    ->default(date('Y-m-d'))
                    ->format('Y-m-d')
                // ->disabledOn('edit')
                    ->columnSpanFull()
                ,
                Select::make('branch_id')->label(__('lang.branch'))
                    ->searchable()
                    ->required()
                    ->options(
                        Branch::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')
                    )
                    ->disabledOn('edit')
                    ->searchable()
                    ->hidden(function () {
                        return !in_array(getCurrentRole(), [1, 3]);
                    })
                    ->columnSpanFull()
                ,
                Textarea::make('notes')->label(__('lang.notes'))
                    ->placeholder('Enter notes')
                    ->columnSpanFull()

                ,

                Repeater::make('orderDetails')
                    ->createItemButtonLabel(__('lang.add_item'))
                    ->columns(5)
                    ->defaultItems(1)
                    ->hiddenOn([
                        // Pages\EditPurchaseInvoice::class,
                        ViewOrderPurchase::class,
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->relationship('orderDetails')
                    ->label(__('lang.order_details'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('lang.product'))
                            ->searchable()
                        // ->disabledOn('edit')
                            ->options(function () {
                                return Product::pluck('name', 'id');
                            })
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn(callable $set) => $set('unit_id', null))
                            ->searchable(),
                        Select::make('unit_id')
                            ->label(__('lang.unit'))
                            ->required()
                        // ->disabledOn('edit')
                            ->options(
                                function (callable $get) {

                                    $unitPrices = UnitPrice::where('product_id', $get('product_id'))->get()->toArray();

                                    if ($unitPrices) {
                                        return array_column($unitPrices, 'unit_name', 'unit_id');
                                    }

                                    return [];
                                }
                            )
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, $state, $get) {
                                $unitPrice = UnitPrice::where(
                                    'product_id',
                                    $get('product_id')
                                )->where('unit_id', $state)->first()->price;
                                $set('price', $unitPrice);

                                $set('total_price', ((float) $unitPrice) * ((float) $get('quantity')));
                            }),
                        Hidden::make('available_quantity')->default(1),
                        TextInput::make('quantity')
                            ->label(__('lang.quantity'))
                            ->type('text')
                            ->default(1)
                        // ->disabledOn('edit')
                        // ->mask(
                        //     fn (TextInput\Mask $mask) => $mask
                        //         ->numeric()
                        //         ->decimalPlaces(2)
                        //         ->thousandsSeparator(',')
                        // )
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function (Closure $set, $state, $get) {
                                $set('total_price', ((float) $state) * ((float) $get('price')));
                                $set('available_quantity', $state);
                            }),
                        TextInput::make('price')
                            ->label(__('lang.price'))
                            ->type('text')
                            ->default(1)
                            ->integer()
                            ->required()
                        // ->disabledOn('edit')
                        // ->mask(
                        //     fn (TextInput\Mask $mask) => $mask
                        //         ->numeric()
                        //         ->decimalPlaces(2)
                        //         ->thousandsSeparator(',')
                        // )
                            ->reactive()

                            ->afterStateUpdated(function (Closure $set, $state, $get) {
                                $set('total_price', ((float) $state) * ((float) $get('quantity')));
                            }),
                        TextInput::make('total_price')->default(1)
                            ->type('text')
                            ->extraInputAttributes(['readonly' => true]),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('lang.order_id'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()
                    ->copyMessage(__('lang.order_id_copied'))
                    ->copyMessageDuration(1500)
                    ->sortable()->searchable()
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('customer.name')->label(__('lang.branch_manager'))->toggleable()
                    ->searchable(isIndividual: true)
                    ->tooltip(fn(Model $record): string => "By {$record->customer->name}"),
                TextColumn::make('branch.name')->label(__('lang.branch')),
                TextColumn::make('order_date')->label(__('lang.order_date')),

                TextColumn::make('item_counts')->label(__('lang.item_counts')),
                TextColumn::make('total_amount')->label(__('lang.total_amount')),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                // ViewAction::make(),
                // EditAction::make(),
                // DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
            ]);
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

    public static function canDeleteAny(): bool
    {
        return static::can('deleteAny');
    }

    public static function getEloquentQuery(): Builder
    {
        $currentRole = getCurrentRole();

        $query = parent::getEloquentQuery();
        if ($currentRole == 7) {
            $query->where('branch_id', auth()->user()->branch->id);
        }
        $query = $query->where('is_purchased', 1)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        return $query;
    }
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();
        $currentRole = getCurrentRole();

        if ($currentRole == 7) {
            $query->where('branch_id', auth()->user()->branch->id);
        }
        return $query->where('is_purchased', 1)->count();
    }
}
