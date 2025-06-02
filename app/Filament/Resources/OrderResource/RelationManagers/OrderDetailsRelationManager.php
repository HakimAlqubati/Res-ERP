<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Services\MultiProductsInventoryService;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Hamcrest\Type\IsNumeric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderDetails';

    protected static ?string $recordTitleAttribute = 'order_id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lang.order_details');
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('purchase_invoice_id'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table->striped()
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('lang.id'))->alignCenter(true)->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('ordered_product.name')->label(__('lang.ordered_product')),
                // Tables\Columns\TextColumn::make('product.name')->label(__('lang.product_approved_by_store')),
                Tables\Columns\TextColumn::make('product.code')->label(__('lang.product_code'))->alignCenter(true)->searchable(),
                Tables\Columns\TextColumn::make('product_id')->label(__('lang.product_id'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product.name')->label(__('lang.product')),
                // Tables\Columns\TextColumn::make('product.code')->label(__('lang.product_code')),
                Tables\Columns\TextColumn::make('unit_id')->label(__('lang.unit_id'))->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('package_size')->label(__('lang.package_size'))->alignCenter(true),
                Tables\Columns\TextColumn::make('unit.name')->label(__('lang.unit')),
                Tables\Columns\TextColumn::make('quantity')->label(__('lang.ordered_quantity_by_branch'))->alignCenter(true),
                // Tables\Columns\TextColumn::make('quantity')->label(__('lang.quantity'))->alignCenter(true),
                Tables\Columns\TextColumn::make('available_quantity')->label(__('lang.quantity_after_modification'))->alignCenter(true),
                Tables\Columns\TextColumn::make('remaining_quantity')->label(__('stock.remaining_quantity'))
                    ->alignCenter(true)
                    ->getStateUsing(function ($record) {
                        $product = $record->product;
                        $storeId = defaultManufacturingStore($product)->id ?? null;
                        if (!$storeId) {
                            return 0;
                        }
                        $service = new  MultiProductsInventoryService(
                            null,
                            $record->product_id,
                            $record->unit_id,
                            $storeId
                        );
                        $remainingQty = $service->getInventoryForProduct($record->product_id)[0]['remaining_qty'] ?? 0;

                        return $remainingQty;
                    }),
                Tables\Columns\TextColumn::make('price')->label(__('lang.unit_price'))
                    ->summarize(Sum::make()->query(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select('price');
                    }))->sortable()
                    ->alignCenter(true)
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    })
                    ->hidden(fn(): bool => isStoreManager()),
                Tables\Columns\TextColumn::make('total_unit_price')->label(__('lang.total'))->alignCenter(true)
                    ->summarize(Sum::make())->hidden(fn(): bool => isStoreManager())
                    ->formatStateUsing(function ($state) {
                        return formatMoneyWithCurrency($state);
                    }),
                // Tables\Columns\TextColumn::make('total_price_with_currency')->label(__('lang.total'))->alignCenter(true)
                //     ->hidden(fn(): bool => isStoreManager()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')->button()->form([
                    Fieldset::make()->schema([
                        TextInput::make('available_quantity')->label(__('lang.quantity'))
                            ->numeric()->minValue(0)
                            ->default(fn($record) => $record->available_quantity),
                    ])
                ])->hidden()
                    ->action(function ($record, $data) {
                        try {
                            $record->update($data);
                            showSuccessNotifiMessage('done');
                        } catch (\Exception $e) {
                            showWarningNotifiMessage('faild', $e->getMessage());
                            throw $e;
                        }
                    })
                // Tables\Actions\EditAction::make()->label(__('lang.change_or_add_purchase_supplier'))
                //     ->using(function (Model $record, array $data): Model {

                //         $product_qtyies = getProductQuantities($record['product_id'], $record['unit_id'], $record['id'], $data['purchase_invoice_id']);

                //         $product_price = getProductPriceByProductUnitPurchaseInvoiceId($record['product_id'], $record['unit_id'], $data['purchase_invoice_id']);
                //         if ($product_price > 0) {
                //             $data['price'] = $product_price;
                //             if ((count($product_qtyies) > 0 && $product_qtyies['remaning_qty'] >= 0)) {
                //                 $data['negative_inventory_quantity'] = false;
                //             } else {
                //                 $data['negative_inventory_quantity'] = true;
                //             }
                //             $record->update($data);
                //         }
                //         return $record;
                //     })
                //     ->before(function (
                //         EditAction $action,
                //         RelationManager $livewire,
                //         Model $record,
                //         array $data
                //     ) {
                //         $product_price = getProductPriceByProductUnitPurchaseInvoiceId($record['product_id'], $record['unit_id'], $data['purchase_invoice_id']);
                //         if ($product_price == 0) {
                //             Notification::make()
                //                 ->warning()
                //                 ->title(__('lang.there_is_no_purchase'))
                //                 ->body(__('lang.please_type_an_invoice_no_exist'))
                //                 ->persistent()

                //                 ->send();

                //             $action->halt();
                //         }
                //     })

                // ,
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public   function canCreate(): bool
    {
        return false;
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->orderDetails->count();
    }
}
