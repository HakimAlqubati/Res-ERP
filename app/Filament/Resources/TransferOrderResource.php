<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Services\FifoInventoryService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TransferOrderResource extends Resource
{
    protected static ?string $model = OrderTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Orders';
    protected static ?string $recordTitleAttribute = 'orders.id';

    protected static ?string $label = 'Transfers';
    protected static ?string $navigationLabel = 'Transfers list';
    public static function getPluralLabel(): string
    {
        return 'Transfers';
    }
    public static ?string $slug = 'transfers-list';
    protected static ?string $cluster = MainOrdersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function getNavigationLabel(): string
    {
        return __('lang.transfers_list');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Grid::make()->columns(3)->schema([
                        Select::make('branch_id')->required()
                            ->label(__('lang.branch'))
                            ->options(Branch::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')),
                        Select::make('status')->required()
                            ->label(__('lang.order_status'))
                            ->options([
                                Order::ORDERED => 'Ordered',
                                Order::READY_FOR_DELEVIRY => 'Ready for delivery',
                                Order::PROCESSING => 'processing',
                                Order::DELEVIRED => 'delevired',
                            ])->default(Order::ORDERED),
                        Select::make('stores')->multiple()->required()
                            ->label(__('lang.store'))
                            // ->disabledOn('edit')
                            ->options([
                                Store::active()
                                    // ->withManagedStores()
                                    ->get()->pluck('name', 'id')->toArray()
                            ])
                        // ->default(fn($record) => $record?->stores?->pluck('store_id')->toArray() ?? [])
                        // ->default(function ($record) {
                        //     dd($record);
                        // })
                        ,
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
                                        ->pluck('name', 'id');
                                })

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
                                ->columnSpan(2)->required(),
                            TextInput::make('purchase_invoice_id')->label(__('lang.purchase_invoice_id'))->readOnly()->visibleOn('view'),
                            TextInput::make('package_size')->label(__('lang.package_size'))->readOnly()->columnSpan(1),

                            TextInput::make('quantity')
                                ->label(__('lang.quantity'))
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                    $set('available_quantity', $state);

                                    $set('total_price', ((float) $state) * ((float)$get('price') ?? 0));
                                })
                                ->rules([
                                    fn($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $fifoService = new FifoInventoryService($get('product_id'), $get('unit_id'), $value);
                                        $result = $fifoService->allocateOrder();
                                        if (!$result['success']) {
                                            $fail($result['message']);
                                        }
                                    },
                                ])
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
            ->paginated(true)
            ->columns([
                TextColumn::make('id')->label(__('lang.order_id'))->toggleable(isToggledHiddenByDefault: false)
                    ->copyable()
                    ->copyMessage('Order id copied')
                    ->copyMessageDuration(1500)
                    ->sortable()
                    ->searchable()
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false
                    ),
                TextColumn::make('branch.name')->label(__('lang.branch')),
                TextColumn::make('customer.name')->label(__('lang.branch_manager'))->toggleable()
                    ->searchable(isIndividual: true)
                    ->tooltip(fn(Model $record): string => "By {$record->customer?->name}"),

                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                TextColumn::make('total_amount')->label(__('lang.total_amount'))->alignCenter(true)
                    ->hidden(fn(): bool => isStoreManager()),
                TextColumn::make('transfer_date')
                    ->label(__('lang.transfer_date'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->sortable(),
                // TextColumn::make('recorded'),
                // TextColumn::make('orderDetails'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // Filter::make('active')
                //     ->query(fn (Builder $query): Builder => $query->where('active', true)),

                SelectFilter::make('customer_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch_manager'))->relationship('customer', 'name'),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->options(Branch::active()->withAccess()->pluck('name', 'id')),
                Filter::make('created_at')
                    ->label(__('lang.created_at'))
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label(__('lang.from')),
                        Forms\Components\DatePicker::make('created_until')->label(__('lang.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrderDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransferOrders::route('/'),
            'view' => Pages\ViewOrderTransfer::route('/{record}'),
        ];
    }

    protected function getTableReorderColumn(): ?string
    {
        return 'sort';
    }

    protected function getTableRecordActionUsing(): ?Closure
    {
        return null;
    }

    public function isTableSearchable(): bool
    {
        return true;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($searchQuery = $this->getTableSearchQuery())) {
            $query->whereIn('id', OrderTransfer::search($searchQuery)->keys());
        }

        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $model): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()
            ->whereIn('branch_id', accessBranchesIds())
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereIn('branch_id', accessBranchesIds())
            ->count();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_order-transfer');
    }
    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view_order-transfer');
    }
}
