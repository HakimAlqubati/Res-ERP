<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Models\User;
use App\Services\FifoInventoryService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource implements HasShieldPermissions
{
    protected static ?string $cluster = MainOrdersCluster::class;
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'publish',
        ];
    }

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // protected static ?string $navigationGroup = 'Orders';
    protected static ?string $recordTitleAttribute = 'id';
    public static function getNavigationLabel(): string
    {
        return __('lang.orders');
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
                        Select::make('store_id')->required()
                            ->label(__('lang.store'))->disabledOn('edit')
                            ->options([
                                Store::active()->get()->pluck('name', 'id')->toArray()
                            ])->default(Store::defaultStore()?->id),
                    ]),
                    // Repeater for Order Details
                    Repeater::make('orderDetails')->columnSpanFull()
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
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('active', 1)
                                    ->unmanufacturingCategory()
                                    ->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::unmanufacturingCategory()->find($value)?->name)
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
                        ->saveRelationshipsUsing(function ($state, $get, $livewire) {
                            $record = $livewire->form->getRecord();

                            if (setting('calculating_orders_price_method') == 'fifo') {

                                $allocatedRows = [];
                                foreach ($state as $key => $allocation) {


                                    $fifoService = new FifoInventoryService($allocation['product_id'], $allocation['unit_id'], $allocation['quantity']);
                                    $result = $fifoService->allocateOrder();

                                    if ($result['success']) {

                                        foreach ($result['result'] as $value) {

                                            $allocatedRows = [
                                                'purchase_invoice_id' => $value['purchase_invoice_id'],
                                                'quantity' => $value['allocated_qty'],
                                                'available_quantity' => $value['allocated_qty'],
                                                'price' => $value['unit_price'],
                                                'package_size' => $value['package_size'],
                                                'unit_id' => $value['unit_id'],
                                                'product_id' =>  $allocation['product_id'],

                                            ];
                                            if (isset($allocatedRows['product_id'])) {
                                                $record->orderDetails()->create($allocatedRows);
                                            }
                                        }
                                    }
                                }
                            } else {
                                foreach ($state as $item) {
                                    $record->orderDetails()->create($item);
                                }
                            }
                        })
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
                BadgeColumn::make('status')
                    ->label(__('lang.order_status'))
                    ->colors([
                        'primary',
                        'secondary' => static fn($state): bool => $state === Order::PENDING_APPROVAL,
                        'warning' => static fn($state): bool => $state === Order::READY_FOR_DELEVIRY,
                        'success' => static fn($state): bool => $state === Order::DELEVIRED,
                        'danger' => static fn($state): bool => $state === Order::PROCESSING,
                    ])
                    ->iconPosition('after')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true),
                TextColumn::make('total_amount')->label(__('lang.total_amount'))->alignCenter(true),
                TextColumn::make('created_at')
                    ->label(__('lang.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                // TextColumn::make('recorded'),
                // TextColumn::make('orderDetails'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // Filter::make('active')
                //     ->query(fn (Builder $query): Builder => $query->where('active', true)),
                SelectFilter::make('status')
                    ->label(__('lang.order_status'))
                    ->multiple()
                    ->searchable()
                    ->options([
                        'ordered' => 'Ordered',
                        'processing' => 'Processing',
                        'ready_for_delivery' => 'Ready for deleviry',
                        'delevired' => 'Delevired',
                        'pending_approval' => 'Pending approval',
                    ]),
                SelectFilter::make('customer_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch_manager'))->relationship('customer', 'name'),
                SelectFilter::make('branch_id')
                    ->searchable()
                    ->multiple()
                    ->label(__('lang.branch'))->relationship('branch', 'name'),
                // Filter::make('active')->label(__('lang.active')),
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
                Tables\Filters\TrashedFilter::make(),

            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                    ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                    ->form([
                        Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                    ])
                    ->action(function ($record, $data) {
                        $result = $record->cancelOrder($data['cancel_reason']);

                        if ($result['status'] === 'success') {
                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\OrderDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {

        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'order-report-custom' => Pages\OrderReportCustom::route('/order-report-custom'),

        ];
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListOrders::class,
            Pages\CreateOrder::class,
            Pages\ViewOrder::class,
            Pages\EditOrder::class,
        ]);
    }


    protected function getTableReorderColumn(): ?string
    {
        return 'sort';
    }

    protected function getTableRecordActionUsing(): ?Closure
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_purchased', 0)->count();
    }

    public function isTableSearchable(): bool
    {
        return true;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($searchQuery = $this->getTableSearchQuery())) {
            $query->whereIn('id', Order::search($searchQuery)->keys());
        }

        return $query;
    }
    public static function canCreate(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_purchased', 0)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
