<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\Base\BaseReturnedOrderResource;
use App\Models\Branch;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Services\MultiProductsInventoryService;
use Exception;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ReturnedOrderResource\Pages\ListReturnedOrders;
use App\Filament\Resources\ReturnedOrderResource\Pages\CreateReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\EditReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\ViewReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Schema\ReturnedOrderForm;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ReturnedOrder;
use App\Models\Store;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnedOrderResource extends BaseReturnedOrderResource
{

    protected static ?string $cluster                             = MainOrdersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;

    protected static ?string $model = ReturnedOrder::class;

    protected static string | \BackedEnum | null $navigationIcon                      = Heroicon::ReceiptRefund;

    public static function form(Schema $schema): Schema
    {
        return ReturnedOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')->deferFilters(false)
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
            ->recordActions([
                EditAction::make()->visible(fn($record): bool => $record->status === ReturnedOrder::STATUS_CREATED),
                Action::make('Approve')->button()
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
                                        $availableQty = MultiProductsInventoryService::getRemainingQty(
                                            $detail->product_id,
                                            $detail->unit_id,
                                            $record->branch->store_id,
                                        );
                                        if ($detail->quantity > $availableQty) {
                                            // أوقف العملية برمتها وأظهر إشعار
                                            throw new Exception("Insufficient stock in branch store ({$record->branch->name}) for product ID: {$detail->product_id}");
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
                        } catch (Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to approve returned order: ' . $e->getMessage());
                        }
                    }),
                Action::make('Reject')->button()
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
                        } catch (Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to reject returned order: ' . $e->getMessage());
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index'  => ListReturnedOrders::route('/'),
            'create' => CreateReturnedOrder::route('/create'),
            'edit'   => EditReturnedOrder::route('/{record}/edit'),
            'view'   => ViewReturnedOrder::route('/{record}'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return self::getModel()::forBranchManager()->count();
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListReturnedOrders::class,
            CreateReturnedOrder::class,
            EditReturnedOrder::class,
        ]);
    }
    public static function canEdit(Model $record): bool
    {
        if ($record->status === ReturnedOrder::STATUS_CREATED) {
            return true;
        }
        return false;
    }

    public static function getOrderSearchQuery(string $search)
    {
        return Order::where('id', 'like', "%{$search}%")
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereHas('branch', fn($q) => $q->where('type', '!=', Branch::TYPE_RESELLER))
            ->limit(5)
            ->pluck('id', 'id');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->whereHas('order');

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query->forBranchManager();
    }
}
