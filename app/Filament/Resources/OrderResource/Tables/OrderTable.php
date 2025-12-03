<?php

namespace App\Filament\Resources\OrderResource\Tables;

use App\Exports\OrdersExport2;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Order;
use App\Services\Orders\OrderCostAnalysisService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->striped()
            ->extremePaginationLinks()

            ->columns([
                SoftDeleteColumn::make(),
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
                // TextColumn::make('store.name')->label(__('lang.store')),
                // TextColumn::make('store_names')->label(__('lang.store'))->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label(__('lang.order_status'))
                    ->colors([
                        'primary',
                        'secondary' => static fn($state): bool => $state === Order::PENDING_APPROVAL,
                        'warning' => static fn($state): bool => $state === Order::READY_FOR_DELEVIRY,
                        'success' => static fn($state): bool => $state === Order::DELEVIRED,
                        'danger' => static fn($state): bool => $state === Order::PROCESSING,
                    ])
                    ->iconPosition('after')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('item_count')->label(__('lang.item_counts'))->alignCenter(true)->sortable(),
                TextColumn::make(
                    'total_amount'
                )->label(__('lang.total_amount'))->alignCenter(true)
                    ->numeric()
                    ->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->using(function (Table $table) {
                                $total  = $table->getRecords()->sum(fn($record) => $record->total_amount);
                                if (is_numeric($total)) {
                                    return formatMoneyWithCurrency($total);
                                }
                                return $total;
                            })
                    ),
                TextColumn::make('created_at')
                    ->formatStateUsing(function ($state) {
                        return date('Y-m-d', strtotime($state)) . ' ' . date('H:i:s', strtotime($state));
                    })
                    ->label(__('lang.created_at'))
                    ->toggleable(isToggledHiddenByDefault: false)
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
                    ->schema([
                        DatePicker::make('created_from')->label(__('lang.from')),
                        DatePicker::make('created_until')->label(__('lang.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transfer_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transfer_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),

            ], FiltersLayout::Modal)->filtersFormColumns(3)
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                    ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                    ->schema([
                        Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                    ])
                    ->action(function ($record, $data) {
                        $result = $record->cancelOrder($data['cancel_reason']);

                        if ($result['status'] === 'success') {
                            Notification::make()
                                ->title('Success')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })->hidden(fn(): bool => isSuperVisor() || isStoreManager()),
                Action::make('Move')
                    ->button()->requiresConfirmation()
                    ->label(function ($record) {
                        return $record->getNextStatusLabel();
                    })
                    ->icon('heroicon-o-chevron-double-left')

                    ->schema(function () {
                        return [
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('status')->label('From') // current status

                                    ->default(function ($record) {
                                        return $record->status;
                                    })
                                    ->disabled(),
                                // Input for next status with placeholder showing allowed statuses
                                TextInput::make('next_status')
                                    ->label('To')
                                    ->default(function ($record) {
                                        return implode(', ', array_keys($record->getNextStatuses()));
                                    })->disabled(),

                            ]),

                        ];
                    })

                    ->databaseTransaction()
                    ->action(function ($record) {

                        DB::beginTransaction();
                        try {
                            $currentStatus = $record->STATUS;
                            $nextStatus = implode(', ', array_keys($record->getNextStatuses()));
                            $record->update(['status' => $nextStatus]);
                            showSuccessNotifiMessage('done', "Done Moved to {$nextStatus}");
                            DB::commit();
                        } catch (Throwable $th) {
                            //throw $th;

                            showWarningNotifiMessage('Error', $th->getMessage());
                            DB::rollBack();
                        }
                        // Add a log entry for the "moved" action
                    })
                    ->disabled(function ($record) {
                        if ($record->status == Order::DELEVIRED) {
                            return true;
                        }
                        return false;
                    })
                    // ->visible(fn(): bool => isSuperAdmin())
                    ->hidden(),



                ActionGroup::make([
                    self::showCostDetailsAction(),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),


                ]),


                // Tables\Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                BulkAction::make('exportOrdersWithDetails')
                    ->label('Export Orders + Details')
                    ->action(function ($records) {
                        return Excel::download(
                            new OrdersExport2($records),
                            'orders_with_details.xlsx'
                        );
                    })
                // ExportBulkAction::make()
            ]);
    }
    /**
     * يعيد الإجراء (Action) لعرض تفاصيل تكلفة الطلب (سعر البيع مقابل سعر التكلفة).
     */
    public static function showCostDetailsAction(): Action
    {
        return Action::make('showCostDetails')
            ->label('Cost Details')
            ->icon(Heroicon::CurrencyDollar)->color(Color::Blue)
            ->modalIcon(Heroicon::Eye)->modalSubmitAction(false) // لا يوجد زر حفظ
            ->modalCancelActionLabel('Close')
            ->fillForm(function (Order $record, OrderCostAnalysisService $service): array {
                // استخدام الخدمة لحساب القيم
                $analysis = $service->getOrderValues($record->id);

                return [
                    'order_id' => $analysis['order_id'],
                    'order_status' => $analysis['status'] ?? 'N/A',
                    'branch_store' => $analysis['branch_store_id'] ?? 'N/A',
                    'value_from_order_details' => formatMoneyWithCurrency($analysis['total_amount_from_order_details'] ?? 0),
                    'value_from_inventory_transactions' => formatMoneyWithCurrency($analysis['total_cost_from_inventory_transactions'] ?? 0),
                    'notes' => $analysis['message'] . ' | ' . ($analysis['notes'] ?? ''),
                ];
            })
            ->schema([

                Fieldset::make('Financial Details')->columns(2)->schema([
                    TextInput::make('value_from_order_details')
                        ->label('Total Order Value')
                        ->disabled(),
                    TextInput::make('value_from_inventory_transactions')
                        ->label('Total Inventory Value')
                        ->disabled(),
                ]),
            ])
            // إظهار الزر فقط إذا كان الطلب جاهزاً للتحليل (تم شحنه/تسليمه)
            ->hidden(fn(Order $record): bool => !in_array($record->status, [Order::READY_FOR_DELEVIRY, Order::DELEVIRED]));
    }
}
