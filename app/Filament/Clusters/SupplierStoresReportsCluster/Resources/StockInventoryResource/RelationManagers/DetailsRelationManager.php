<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockAdjustmentReason;
use App\Models\StockIssueOrder;
use App\Models\StockIssueOrderDetail;
use App\Models\StockSupplyOrder;
use App\Models\StockSupplyOrderDetail;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = '';
    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('unit.name'),
                Tables\Columns\TextColumn::make('package_size')->alignCenter(true),
                Tables\Columns\TextColumn::make('system_quantity')->alignCenter(true),
                Tables\Columns\TextColumn::make('physical_quantity')->alignCenter(true),
                Tables\Columns\TextColumn::make('difference')->alignCenter(true),
                IconColumn::make('is_adjustmented')->boolean()->alignCenter(true)->label(__('stock.is_adjustmented')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkAction::make('createStockAdjustment')
                    ->form(function (Collection $records) {

                        $defaultValues = $records
                            ->filter(fn($record) => !$record->is_adjustmented)
                            ->map(fn($record) => [
                                'product_id' => $record->product_id,
                                'unit_id' => $record->unit_id,
                                'quantity' => $record->difference,
                                'package_size' => $record->package_size
                            ])
                            ->toArray();

                        return [
                            Grid::make()->columns(2)->schema([
                                // Forms\Components\Select::make('adjustment_type')
                                //     ->label('Adjustment Type')
                                //     ->options([
                                //         StockAdjustment::ADJUSTMENT_TYPE_INCREASE => 'Increase',
                                //         StockAdjustment::ADJUSTMENT_TYPE_DECREASE => 'Decrease',
                                //     ])->default($defaultAdjustmentType)
                                //     ->required()->hidden(),
                                Forms\Components\Select::make('reason_id')
                                    ->label('Reason')->default(StockAdjustmentReason::getFirstId())
                                    ->options(StockAdjustmentReason::active()->pluck('name', 'id'))->searchable()
                                    ->required(),
                                Forms\Components\Select::make('store_id')->label(__('lang.store'))
                                    ->searchable()
                                    ->disabledOn('edit')
                                    ->default(getDefaultStore())
                                    ->options(
                                        Store::where('active', 1)->get(['id', 'name'])->pluck('name', 'id')
                                    )
                                    ->searchable(),

                            ]),
                            Repeater::make('stock_adjustment_details')
                                // ->relationship('details')
                                ->schema([
                                    Grid::make()->columns(4)->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->required()
                                            ->options($records->pluck('product.name', 'product_id')->toArray()),
                                        Forms\Components\Select::make('unit_id')
                                            ->label('Unit')
                                            ->required()
                                            ->options($records->pluck('unit.name', 'unit_id')->toArray()),
                                        Forms\Components\TextInput::make('quantity')
                                            ->required(),
                                        Forms\Components\TextInput::make('package_size')
                                            ->required(),

                                    ]),
                                    Textarea::make('notes')->columnSpanFull()->helperText('Type Reason ...')->required(),
                                ])->addable(false)->minItems(1)
                                ->defaultItems(1)->addActionLabel('Add Item')
                                ->default($defaultValues)
                                ->columns(4)
                        ];
                    })
                    ->action(function (Collection $records, $data) {
                        DB::beginTransaction();
                        try {

                            foreach ($data['stock_adjustment_details'] as $detail) {
                                $defaultAdjustmentType = 0;
                                if (isset($detail['quantity']) && is_numeric($detail['quantity'])) {
                                    if ($detail['quantity'] <  0) {
                                        $defaultAdjustmentType = StockAdjustment::ADJUSTMENT_TYPE_DECREASE;
                                    } elseif ($detail['quantity'] > 0) {
                                        $defaultAdjustmentType = StockAdjustment::ADJUSTMENT_TYPE_INCREASE;
                                    }
                                }

                                $stockAdjustment = StockAdjustmentDetail::create([
                                    'product_id' => $detail['product_id'],
                                    'unit_id' => $detail['unit_id'],
                                    'quantity' => abs($detail['quantity']),
                                    'package_size' => $detail['package_size'],
                                    'notes' => $detail['notes'],

                                    'store_id' => $data['store_id'], // Adjust this based on your relationship
                                    'reason_id' => $data['reason_id'], // You can set a reason if needed 
                                    'adjustment_type' => $defaultAdjustmentType,
                                    'created_by' => auth()->id(),
                                    'adjustment_date' => now(),
                                ]);
                                // Update is_adjustmented field for selected records
                                $records->each(function ($record) {
                                    $record->update(['is_adjustmented' => true]);
                                });


                                if ($detail['quantity'] > 0) {
                                    // Create a StockSupplyOrder
                                    $order = StockSupplyOrder::create([
                                        'order_date' => now(),
                                        'store_id' => $data['store_id'],
                                        'notes' => $stockAdjustment->notes,
                                        'cancelled' => false,
                                        'created_by' => $stockAdjustment->created_by,
                                    ]);


                                    // Create StockSupplyOrderDetail for each detail
                                    StockSupplyOrderDetail::create([
                                        'stock_supply_order_id' => $order->id,
                                        'product_id' => $detail['product_id'],
                                        'unit_id' => $detail['unit_id'],
                                        'quantity' => abs($detail['quantity']), // Convert to positive if negative
                                        'package_size' => $detail['package_size'],
                                    ]);
                                } elseif ($detail['quantity'] < 0) {
                                    // Create a StockIssueOrder
                                    $order = StockIssueOrder::create([
                                        'order_date' => now(),
                                        'store_id' => $data['store_id'],
                                        'notes' => $stockAdjustment->notes,
                                        'cancelled' => false,
                                        'created_by' => $stockAdjustment->created_by,
                                    ]);

                                    // Create StockIssueOrderDetail for each detail
                                    StockIssueOrderDetail::create([
                                        'stock_issue_order_id' => $order->id,
                                        'product_id' => $detail['product_id'],
                                        'unit_id' => $detail['unit_id'],
                                        'quantity' => abs($detail['quantity']), // Assuming quantity is used
                                        'package_size' => $detail['package_size'],
                                    ]);
                                }
                            }
                            showSuccessNotifiMessage('done', 'Stock adjustment created successfully.');
                            DB::commit();
                        } catch (\Throwable $th) {
                            //throw $th;
                            DB::rollBack();
                            showWarningNotifiMessage('Faild', $th->getMessage());
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
