<?php
namespace App\Filament\Resources\Base;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\ReturnedOrderResource\Pages;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ReturnedOrder;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class ReturnedOrderResource extends Resource
{
    protected static ?string $model = ReturnedOrder::class;
    abstract protected static function getOrderSearchQuery(string $search);

    protected static ?string $navigationIcon                      = 'heroicon-o-rectangle-stack';
    // protected static ?string $cluster                             = MainOrdersCluster::class;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort                         = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Returned Order Info')
                    ->schema([
                        Select::make('original_order_id')
                            ->label('Original Order')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->required()->live()
                            ->getSearchResultsUsing(fn(string $search) => static::getOrderSearchQuery($search))
                            ->afterStateUpdated(function ($state, $set) {
                                $order = \App\Models\Order::find($state);
                                if ($order && $order->branch_id) {
                                    $set('branch_id', $order->branch_id);
                                }

                                if ($order) {
                                    $details = $order->orderDetails->map(function ($detail) {
                                        return [
                                            'product_id'   => $detail->product_id,
                                            'unit_id'      => $detail->unit_id,
                                            'quantity'     => $detail->available_quantity,
                                            'price'        => $detail->price,
                                            'package_size' => $detail->package_size ?? 1,
                                            'notes'        => 'Auto-filled from order #' . $detail->order_id,
                                        ];
                                    })->toArray();

                                    $set('details', $details);
                                }
                            }),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->required()
                            ->reactive()
                            ->relationship('branch', 'name')->disabled()->dehydrated(),
                        Select::make('store_id')
                            ->label('Store')
                            ->required()
                            ->options(Store::active()->get(['id', 'name'])->pluck('name', 'id')),
                        DatePicker::make('returned_date')
                            ->label('Returned Date')->default(now())
                            ->required(),

                        Select::make('status')
                            ->label('Status')->disabledOn('create')
                            ->options(ReturnedOrder::getStatusOptions())
                            ->default(ReturnedOrder::STATUS_CREATED),

                        Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approver', 'name')
                            ->searchable()->hiddenOn('create'),

                        Textarea::make('reason')
                            ->label('Return Reason')->columnSpanFull()
                            ->rows(3),
                    ])->columns(5),

                Fieldset::make('Returned Products Details')
                    ->schema([
                        Repeater::make('details')
                            ->relationship()
                            ->label('Returned Items')
                            ->columns(6)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->searchable()
                                    ->options(function () {
                                        return Product::active()
                                            ->orderBy('id', 'asc')
                                            ->get(['id', 'code', 'name', 'active'])

                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ]);
                                    })
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Product::active()
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'like', "%{$search}%")
                                                    ->orWhere('code', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('unit_id')->columnSpan(2)
                                    ->label('Unit')
                                    ->relationship('unit', 'name')
                                    ->searchable()
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()->live(onBlur: true)
                                    ->required()
                                    ->rules(function (callable $get) {
                                        $orderId   = $get('../../original_order_id');
                                        $productId = $get('product_id');
                                        $unitId    = $get('unit_id');

                                        if (! $orderId || ! $productId || ! $unitId) {
                                            return [];
                                        }

                                        $order = \App\Models\Order::with('orderDetails')->find($orderId);
                                        if (! $order) {
                                            return [];
                                        }

                                        $detail = $order->orderDetails->firstWhere(function ($d) use ($productId, $unitId) {
                                            return $d->product_id == $productId && $d->unit_id == $unitId;
                                        });

                                        return $detail
                                        ? ['max:' . $detail->available_quantity]
                                        : [];
                                    }),

                                Hidden::make('price'),

                                TextInput::make('package_size')
                                    ->label('Package Size')
                                    ->numeric()->readOnly()
                                    ->default(1)
                                    ->required(),

                                Textarea::make('notes')
                                    ->label('Notes')->columnSpanFull()
                                    ->rows(2),
                            ])
                            ->defaultItems(1)
                            ->createItemButtonLabel('Add Product')
                            ->columnSpanFull()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->alignCenter(true)->toggleable(),
                TextColumn::make('order.id')->label('Original Order ID')->sortable()->alignCenter(true)->toggleable(),
                TextColumn::make('branch.name')->label('Branch')->sortable()->toggleable(),
                TextColumn::make('store.name')->label('Store')->sortable()->toggleable(),
                TextColumn::make('returned_date')->label('Returned Date')->date()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->toggleable()->alignCenter(true),
                TextColumn::make('creator.name')->label('Created By')->toggleable(),
                TextColumn::make('itemsCount')->label('Items Count')->toggleable()->alignCenter(true),
                // TextColumn::make('totalAmount')->label('Total Amount')->money('MYR')->toggleable()->alignCenter(true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn($record): bool => $record->status === ReturnedOrder::STATUS_CREATED),
                Tables\Actions\Action::make('Approve')->button()
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->status === ReturnedOrder::STATUS_CREATED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if (! $record->store_id) {
                            showWarningNotifiMessage('Fill the store');
                            return;
                        }
                        try {
                            DB::transaction(function () use ($record) {

                                $record->update([
                                    'status'      => ReturnedOrder::STATUS_APPROVED,
                                    'approved_by' => auth()->id(),
                                ]);
                                foreach ($record->details as $detail) {

                                    if ($record->branch->hasStore()) {
                                        // التحقق من الكمية المتوفرة في مخزن الفرع (المصدر)
                                        $availableQty = \App\Services\MultiProductsInventoryService::getRemainingQty(
                                            $detail->product_id,
                                            $detail->unit_id,
                                            $record->branch->store_id,
                                        );
                                        if ($detail->quantity > $availableQty) {
                                            // أوقف العملية برمتها وأظهر إشعار
                                            throw new \Exception("Insufficient stock in branch store ({$record->branch->name}) for product ID: {$detail->product_id}");
                                        }

                                        // أولاً نُخرج الكمية من المخزن الخاص بالفرع (باعتباره مصدر المرتجع)
                                        $transaction = InventoryTransaction::moveOutFromStore([
                                            'product_id'       => $detail->product_id,
                                            'quantity'         => $detail->quantity,
                                            'unit_id'          => $detail->unit_id,
                                            'store_id'         => $record->branch?->store_id, // أو مررها حسب لوجيكك
                                            'price'            => $detail->price,
                                            'package_size'     => $detail->package_size,
                                            'transaction_date' => $record->returned_date,
                                            'movement_date'    => $record->returned_date,
                                            'notes'            => 'Auto-out from branch for returned order #' . $record->id,
                                            'transactionable'  => $record,
                                        ]);
                                        if (! $transaction) {
                                            // فشل الصرف، ممكن تسجل لوج أو تتجاهل بناءً على منطقك
                                            Log::warning("Insufficient stock to move out for returned order #{$record->id}");
                                        }

                                        // ثم ندخل الكمية إلى مخزن المرتجع

                                        InventoryTransaction::moveToStore([
                                            'product_id'       => $detail->product_id,
                                            'quantity'         => $detail->quantity,
                                            'unit_id'          => $detail->unit_id,
                                            'store_id'         => $record->store_id,
                                            'movement_type'    => InventoryTransaction::MOVEMENT_IN,
                                            'price'            => $detail->price,
                                            'package_size'     => $detail->package_size,
                                            'transaction_date' => $record->returned_date,
                                            'movement_date'    => $record->returned_date,
                                            'notes'            => 'Return from branch #' . $record->branch->name,
                                            'transactionable'  => $record,
                                        ]);
                                    }
                                }
                            });
                            showSuccessNotifiMessage('Returned order approved successfully.');
                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to approve returned order: ' . $e->getMessage());
                        }
                    }),
                Tables\Actions\Action::make('Reject')->button()
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn($record) => $record->status === ReturnedOrder::STATUS_CREATED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            DB::transaction(function () use ($record) {
                                $record->update([
                                    'status'      => ReturnedOrder::STATUS_REJECTED,
                                    'approved_by' => auth()->id(),
                                ]);
                            });
                            showSuccessNotifiMessage('Returned order rejected.');
                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to reject returned order: ' . $e->getMessage());
                        }
                    }),
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
            'index'  => Pages\ListReturnedOrders::route('/'),
            'create' => Pages\CreateReturnedOrder::route('/create'),
            'edit'   => Pages\EditReturnedOrder::route('/{record}/edit'),
            'view'   => Pages\ViewReturnedOrder::route('/{record}'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::count();
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ListReturnedOrders::class,
            Pages\CreateReturnedOrder::class,
            Pages\EditReturnedOrder::class,
        ]);
    }
    public static function canEdit(Model $record): bool
    {
        if ($record->status === ReturnedOrder::STATUS_CREATED) {
            return true;
        }
        return false;
    }
}