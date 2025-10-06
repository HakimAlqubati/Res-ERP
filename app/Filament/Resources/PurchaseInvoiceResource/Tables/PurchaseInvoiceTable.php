<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Tables;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Exception;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Models\InventoryTransaction;
use App\Models\PaymentMethod;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->color('primary')
                    ->weight(FontWeight::Bold)->alignCenter(true)
                    ->searchable(isIndividual: true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice_no')
                    ->color('primary')->copyable()
                    ->weight(FontWeight::Bold)->alignCenter(true)
                    ->searchable()->sortable()->toggleable(),
                TextColumn::make('supplier.name')->label('Supplier')->toggleable()->default('-')->wrap(),
                TextColumn::make('store.name')->label('Store')->toggleable(),
                TextColumn::make('date')->sortable()->toggleable(),
                TextColumn::make('description')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('details_count')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_amount')
                    ->label(__('lang.total_amount'))
                    ->alignCenter(true)
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
                    )
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('has_attachment')->alignCenter(true)->label(__('lang.has_attachment'))
                    ->boolean()->toggleable()
                // ->trueIcon('heroicon-o-badge-check')
                // ->falseIcon('heroicon-o-x-circle')
                ,
                IconColumn::make('has_grn')->alignCenter(true)->label(__('lang.has_grn'))->boolean()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('grn.grn_number')
                    ->label('GRN Number')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('has_inventory_transaction')
                    ->label('Inventory Updated')
                    ->boolean()->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('creator_name')
                    ->label('Creator')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date')
                    ->label('Date')->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_outbound_transactions')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Untouched')->boolean()->alignCenter(),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true)->boolean()->alignCenter(),

            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(),

                SelectFilter::make('id')
                    ->label('ID')
                    ->multiple() // 2. تمت إضافة إمكانية الاختيار المتعدد
                    ->searchable() // 3. تمت إضافة إمكانية البحث
                    ->getSearchResultsUsing(function (string $search): array {
                        // هذه الدالة تبحث برقم الفاتورة أو بال ID الرقمي
                        return PurchaseInvoice::where('invoice_no', 'like', "%{$search}%")
                            ->orWhere('id', $search)
                            ->limit(50)
                            ->pluck('id', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        // هذه الدالة تعرض رقم الفاتورة بعد اختيارها
                        return PurchaseInvoice::find($value)?->id;
                    }),
                SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::active()->get()->pluck('name', 'id')),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(Supplier::get()->pluck('name', 'id'))->searchable(),
                SelectFilter::make('store_id')
                    ->label('Store')->multiple()
                    ->options(function () {
                        return \App\Models\Store::query()
                            ->active()
                            ->withManagedStores()   // يبقى كما هو عندك لو عند المستخدم قيود إدارة
                            ->hasPurchases()        // السكوب الجديد
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })  ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['to'], fn($q, $date) => $q->whereDate('date', '<=', $date));
                    })
                    ->label('Date Between')
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['from'] && $data['to']) {
                            return "From {$data['from']} to {$data['to']}";
                        }
                        if ($data['from']) {
                            return "From {$data['from']}";
                        }
                        if ($data['to']) {
                            return "Until {$data['to']}";
                        }
                        return null;
                    }),

            ], FiltersLayout::Modal)->filtersFormColumns(3)
            // ->deferFilters(false)
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Action::make('create_inventory')
                        ->label('Create Inventory')
                        ->icon('heroicon-o-plus-circle')->button()
                        ->color('success')
                        ->visible(fn($record) => !$record->has_inventory_transaction)
                        ->action(function ($record) {
                            DB::beginTransaction();
                            try {
                                foreach ($record->details as $detail) {
                                    InventoryTransaction::moveToStore([
                                        'product_id' => $detail->product_id,
                                        'movement_type' => InventoryTransaction::MOVEMENT_IN,
                                        'quantity' => $detail->quantity,
                                        'unit_id' => $detail->unit_id,
                                        'package_size' => $detail->package_size,
                                        'store_id' => $record->store_id,
                                        'price' => $detail->price,
                                        'transaction_date' => $record->date,
                                        'movement_date' => $record->date,
                                        'notes' => 'Purchase invoice with id #' . $record->id . ' ' . $record->store->name ?? '',
                                        'transactionable' => $record,
                                    ]);
                                }
                                DB::commit();
                                showSuccessNotifiMessage('Done');
                            } catch (Exception $e) {
                                DB::rollBack();
                                showWarningNotifiMessage($e->getMessage());
                            }
                        })->hidden(),
                    EditAction::make()
                        ->icon('heroicon-s-pencil'),
                    Action::make('download')
                        ->label(__('lang.download_attachment'))
                        ->action(function ($record) {
                            if (strlen($record['attachment']) > 0) {
                                if (env('APP_ENV') == 'local') {
                                    $file_link = url('storage/' . $record['attachment']);
                                } else if (env('APP_ENV') == 'production') {
                                    $file_link = url('New-Res-System/public/storage/' . $record['attachment']);
                                }
                                return redirect(url($file_link));
                            }
                        })->hidden(fn($record) => !(strlen($record['attachment']) > 0))
                        // ->icon('heroicon-o-download')
                        ->color('green'),
                    Action::make('cancel')
                        ->label('Cancel')->hidden(fn($record): bool => $record->cancelled)
                        ->icon('heroicon-o-backspace')->button()->color(Color::Red)
                        ->schema([
                            Textarea::make('cancel_reason')->required()->label('Cancel Reason')
                        ])
                        ->action(function ($record, $data) {
                            try {
                                $result = $record->handleCancellation($record, $data['cancel_reason']);

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
                            } catch (Throwable $th) {
                                throw $th;
                            }
                        })->hidden(fn(): bool => isSuperVisor())
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make()
            ]);
    }
}
