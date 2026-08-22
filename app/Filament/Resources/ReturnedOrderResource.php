<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\Base\BaseReturnedOrderResource;
use App\Filament\Resources\ReturnedOrderResource\Pages\CreateReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\EditReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Pages\ListReturnedOrders;
use App\Filament\Resources\ReturnedOrderResource\Pages\ViewReturnedOrder;
use App\Filament\Resources\ReturnedOrderResource\Schema\ReturnedOrderForm;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\ReturnedOrder;
use App\Services\MultiProductsInventoryService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReturnedOrderResource extends BaseReturnedOrderResource
{
    protected static ?string $cluster = MainOrdersCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $model = ReturnedOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ReceiptRefund;

    public static function form(Schema $schema): Schema
    {
        return ReturnedOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()->defaultSort('id', 'desc')->deferFilters(false)
            ->columns([
                SoftDeleteColumn::make(),
                TextColumn::make('id')->label('#')->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('order.id')
                ->label('Order ID')
                ->sortable()->alignCenter(true)->toggleable(),
                TextColumn::make('branch.name')->label('Branch')->sortable()->toggleable(),
                TextColumn::make('store.name')->label('Store')->sortable()->toggleable(),
                TextColumn::make('returned_date')->label('Returned Date')->date()->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        \App\Models\ReturnedOrder::STATUS_CREATED => 'gray',
                        \App\Models\ReturnedOrder::STATUS_APPROVED => 'success',
                        \App\Models\ReturnedOrder::STATUS_REJECTED => 'danger',
                        default => 'primary',
                    })
                    ->toggleable()
                    ->alignCenter(true),
                TextColumn::make('creator.short_name')->label('Created By')->toggleable(),
                TextColumn::make('itemsCount')->label('Items')->toggleable()->alignCenter(true),
                // TextColumn::make('totalAmount')->label('Total Amount')->money('MYR')->toggleable()->alignCenter(true),
            ])
            ->filters([
                TrashedFilter::make(),
            ],FiltersLayout::Modal)
            ->filtersFormColumns(4)
            
                
            ->deferFilters(true)
            ->recordActions([
                ActionGroup::make([

                EditAction::make()->visible(fn ($record): bool => $record->status === ReturnedOrder::STATUS_CREATED),
                DeleteAction::make()->hidden(fn ($record) => $record->status === ReturnedOrder::STATUS_APPROVED),
                ForceDeleteAction::make()->hidden(fn ($record) => $record->status === ReturnedOrder::STATUS_APPROVED),
                RestoreAction::make(),
                Action::make('Approve')->button()
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => $record->status === ReturnedOrder::STATUS_CREATED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if (! $record->store_id) {
                            showWarningNotifiMessage('Fill the store');

                            return;
                        }
                        try {
                            DB::transaction(function () use ($record) {

                                $record->update([
                                    'status' => ReturnedOrder::STATUS_APPROVED,
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

                                        // البحث عن الحركة الأصلية (IN) الخاصة بالطلب
                                        $sourceTransaction = InventoryTransaction::where('transactionable_type', \App\Models\Order::class)
                                            ->where('transactionable_id', $record->original_order_id)
                                            ->where('product_id', $detail->product_id)
                                            ->where('unit_id', $detail->unit_id)
                                            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                                            ->first();

                                        // أولاً نُخرج الكمية من المخزن الخاص بالفرع (باعتباره مصدر المرتجع)
                                        $transaction = InventoryTransaction::moveOutFromStore([
                                            'product_id' => $detail->product_id,
                                            'quantity' => $detail->quantity,
                                            'unit_id' => $detail->unit_id,
                                            'store_id' => $record->branch?->store_id, // أو مررها حسب لوجيكك
                                            'price' => $detail->price,
                                            'package_size' => $detail->package_size,
                                            'transaction_date' => $record->returned_date,
                                            'movement_date' => $record->returned_date,
                                            'notes' => 'Auto-out from branch for returned order #'.$record->id,
                                            'transactionable' => $record,
                                            'source_transaction_id' => $sourceTransaction?->id,
                                        ]);
                                        if (! $transaction) {
                                            // فشل الصرف، ممكن تسجل لوج أو تتجاهل بناءً على منطقك
                                            Log::warning("Insufficient stock to move out for returned order #{$record->id}");
                                        }

                                        // ثم ندخل الكمية إلى مخزن المرتجع

                                        InventoryTransaction::moveToStore([
                                            'product_id' => $detail->product_id,
                                            'quantity' => $detail->quantity,
                                            'unit_id' => $detail->unit_id,
                                            'store_id' => $record->store_id,
                                            'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                            'price' => $detail->price,
                                            'package_size' => $detail->package_size,
                                            'transaction_date' => $record->returned_date,
                                            'movement_date' => $record->returned_date,
                                            'notes' => 'Return from branch #'.$record->branch->name,
                                            'transactionable' => $record,
                                        ]);
                                    }
                                }
                            });
                            showSuccessNotifiMessage('Returned order approved successfully.');
                            DB::commit();
                        } catch (Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to approve returned order: '.$e->getMessage());
                        }
                    }),
                Action::make('Reject')->button()
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn ($record) => $record->status === ReturnedOrder::STATUS_CREATED)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            DB::transaction(function () use ($record) {
                                $record->update([
                                    'status' => ReturnedOrder::STATUS_REJECTED,
                                    'approved_by' => auth()->id(),
                                ]);
                            });
                            showSuccessNotifiMessage('Returned order rejected.');
                            DB::commit();
                        } catch (Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Failed to reject returned order: '.$e->getMessage());
                        }
                    }),
                    
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => ListReturnedOrders::route('/'),
            'create' => CreateReturnedOrder::route('/create'),
            'edit' => EditReturnedOrder::route('/{record}/edit'),
            'view' => ViewReturnedOrder::route('/{record}'),
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

    public static function canDelete(Model $record): bool
    {
        if ($record->status === ReturnedOrder::STATUS_APPROVED) {
            return false;
        }

        return true;
    }

    public static function getOrderSearchQuery(string $search)
    {
        return Order::where('id', 'like', "%{$search}%")
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereHas('branch', fn ($q) => $q->where('type', '!=', Branch::TYPE_RESELLER))
            ->limit(5)
            ->pluck('id', 'id');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->whereHas('order')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query->forBranchManager();
    }

    public static function canViewAny(): bool
    {
        
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) {
            return true;
        }

        return false;
    }
}
