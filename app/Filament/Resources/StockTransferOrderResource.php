<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Resources\StockTransferOrderResource\Pages;
use App\Filament\Resources\StockTransferOrderResource\RelationManagers;
use App\Models\Product;
use App\Models\StockTransferOrder;
use App\Models\Store;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class StockTransferOrderResource extends Resource
{
    protected static ?string $model = StockTransferOrder::class;
    protected static ?string $slug = 'stock-transfer-orders';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->schema([
                        Select::make('from_store_id')
                            ->label('From Store')
                            ->options(Store::active()->get(['name', 'id'])->pluck('name', 'id'))
                            ->required(),

                        Select::make('to_store_id')
                            ->label('To Store')
                            ->options(Store::active()->get(['name', 'id'])->pluck('name', 'id'))
                            ->required(),

                        DatePicker::make('date')
                            ->required()->default(now()),

                        Select::make('status')
                            ->required()
                            ->options([
                                'created' => 'Created',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('created'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(4),

                    Grid::make()->schema([
                        Repeater::make('details')
                            ->label('Transfer Details')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->required()
                                    ->columnSpan(2)
                                    ->label('Product')
                                    ->options(function () {
                                        return Product::where('active', 1)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}"
                                            ]);
                                    })
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Product::where('active', 1)
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'like', "%{$search}%")
                                                    ->orWhere('code', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($product) => [
                                                $product->id => "{$product->code} - {$product->name}"
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = \App\Models\Product::find($get('product_id'));
                                        if (! $product) return [];
                                        return $product->supplyOutUnitPrices
                                            ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (\Filament\Forms\Set $set, $state, $get) {
                                        $unitPrice = UnitPrice::where(
                                            'product_id',
                                            $get('product_id')
                                        )
                                            ->where('unit_id', $state)->first();


                                        $set('package_size',  $unitPrice->package_size ?? 0);
                                    })->columnSpan(2)->required(),

                                TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                    ->label(__('lang.package_size')),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.1)
                                    ->label('Quantity'),

                                Textarea::make('notes')->label('Notes')->columnSpanFull(),

                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(6)
                            ->columnSpanFull(),
                    ])->columns(4),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->striped()
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('fromStore.name')->label('From')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('toStore.name')->label('To')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('date')->date()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('status')->badge()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('details_count')->alignCenter(true)->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')->button()
                    ->icon('heroicon-o-check-circle')
                    // ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === StockTransferOrder::STATUS_CREATED)
                    ->action(function ($record) {
                        try {
                            DB::beginTransaction();
                            $record->update([
                                'status' => StockTransferOrder::STATUS_APPROVED,
                                'approved_at' => now(),
                            ]);

                            DB::commit();
                            showSuccessNotifiMessage('Done');
                        } catch (\Throwable $e) {
                            DB::rollBack();

                            showWarningNotifiMessage('Faild', $e->getMessage());
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
            'index' => Pages\ListStockTransferOrders::route('/'),
            'create' => Pages\CreateStockTransferOrder::route('/create'),
            'edit' => Pages\EditStockTransferOrder::route('/{record}/edit'),
        ];
    }
}