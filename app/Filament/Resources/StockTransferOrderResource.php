<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use App\Services\MultiProductsInventoryService;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\StockTransferOrderResource\Pages\ListStockTransferOrders;
use App\Filament\Resources\StockTransferOrderResource\Pages\CreateStockTransferOrder;
use App\Filament\Resources\StockTransferOrderResource\Pages\EditStockTransferOrder;
use App\Filament\Resources\StockTransferOrderResource\Pages\ViewStockTransferOrder;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Resources\StockTransferOrderResource\Pages;
use App\Models\Product;
use App\Models\StockTransferOrder;
use App\Models\Store;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class StockTransferOrderResource extends Resource
{
    protected static ?string $model          = StockTransferOrder::class;
    protected static ?string $slug           = 'stock-transfer-orders';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
                    Grid::make()->columnSpanFull()->schema([
                        Select::make('from_store_id')
                            ->label('From Store')
                            ->options(Store::active()->get(['name', 'id'])->pluck('name', 'id'))
                            ->required()->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $details = collect($get('details') ?? [])
                                    ->map(function ($item) use ($state) {
                                        if ($item['product_id'] && $item['unit_id'] && $state) {
                                            $item['remaining_quantity'] = MultiProductsInventoryService::getRemainingQty(
                                                $item['product_id'],
                                                $item['unit_id'],
                                                $state
                                            );
                                        }
                                        return $item;
                                    })->toArray();

                                $set('details', $details);
                            }),

                        Select::make('to_store_id')
                            ->label('To Store')
                            ->options(Store::active()->get(['name', 'id'])->pluck('name', 'id'))
                            ->required()->searchable(),

                        DatePicker::make('date')
                            ->required()->default(now()),

                        Select::make('status')
                            ->required()
                            ->options([
                                'created'  => 'Created',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])->disabled()->dehydrated()
                            ->default('created'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(4),

                    Grid::make()->columnSpanFull()->schema([
                        Select::make('product_selector')
                            ->label('Add Products')
                            ->multiple()
                            ->searchable()->columnSpanFull()
                            ->options(
                                Product::where('active', 1)
                                    ->get()
                                    ->mapWithKeys(fn($product) => [
                                        $product->id => "{$product->code} - {$product->name}",
                                    ])
                                    ->toArray()
                            )
                            ->visible(fn($record) => blank($record)) // يظهر فقط أثناء الإضافة
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $details = $get('details') ?? [];

                                // جمع الـ product_ids الموجودة حاليًا في details
                                $existingProductIds = collect($details)->pluck('product_id')->all();

                                // المنتجات التي يجب إضافتها
                                $newProductIds = array_diff($state, $existingProductIds);

                                foreach ($newProductIds as $productId) {
                                    $product = Product::find($productId);
                                    if (! $product) {
                                        continue;
                                    }

                                    $unitPrice    = $product->supplyOutUnitPrices->first();
                                    $availableQty = 1;

                                    if ($get('from_store_id')) {
                                        $availableQty = MultiProductsInventoryService::getRemainingQty(
                                            $productId,
                                            $unitPrice?->unit_id,
                                            $get('from_store_id'),
                                        );
                                    }

                                    $details[] = [
                                        'product_id'         => $productId,
                                        'unit_id'            => $unitPrice?->unit_id,
                                        'package_size'       => $unitPrice?->package_size ?? 1,
                                        'quantity'           => 1,
                                        'remaining_quantity' => $availableQty,
                                        'notes'              => '',
                                    ];
                                }

                                // المنتجات التي يجب حذفها من details
                                $remainingProductIds = $state;
                                $details             = collect($details)
                                    ->filter(fn($item) => in_array($item['product_id'], $remainingProductIds))
                                    ->values()
                                    ->toArray();

                                $set('details', $details);
                            }),

                        Repeater::make('details')->columnSpanFull()
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
                                                $product->id => "{$product->code} - {$product->name}",
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
                                                $product->id => "{$product->code} - {$product->name}",
                                            ])
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = Product::find($get('product_id'));
                                        if (! $product) {
                                            return [];
                                        }

                                        return $product->supplyOutUnitPrices
                                            ->pluck('unit.name', 'unit_id')?->toArray() ?? [];
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, $state, $get) {
                                        $productId   = $get('product_id');
                                        $fromStoreId = $get('../../from_store_id'); // صعود للمخزن

                                        $unitPrice = UnitPrice::where('product_id', $productId)
                                            ->where('unit_id', $state)
                                            ->first();

                                        $set('package_size', $unitPrice->package_size ?? 1);

                                        if ($productId && $state && $fromStoreId) {
                                            $remainingQty = MultiProductsInventoryService::getRemainingQty($productId, $state, $fromStoreId);
                                            $set('remaining_quantity', $remainingQty);
                                        }
                                    })->columnSpan(2)->required(),

                                TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                    ->label(__('lang.package_size')),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.1)
                                    ->label('Quantity'),
                                TextInput::make('remaining_quantity')
                                    ->label('Remaining Qty')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (callable $set, $get) {
                                        $productId = $get('product_id');
                                        $unitId    = $get('unit_id');
                                        $storeId   = $get('../../from_store_id'); // صعود للخارج للوصول لقيمة المخزن

                                        if ($productId && $unitId && $storeId) {
                                            $qty = MultiProductsInventoryService::getRemainingQty($productId, $unitId, $storeId);
                                            $set('remaining_quantity', $qty);
                                        }
                                    })
                                    ->reactive()
                                    ->columnSpan(1),

                                Textarea::make('notes')->label('Notes')->columnSpanFull(),

                            ])
                            ->minItems(1)
                            ->defaultItems(0)
                            ->columns(7)
                            ->columnSpanFull(),
                    ])->columns(4),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->striped()
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fromStore.name')->label('From')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('toStore.name')->label('To')->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('date')->date()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('status')->badge()->sortable()->searchable()->alignCenter(true)->toggleable()->color(fn(string $state): string => match ($state) {
                    StockTransferOrder::STATUS_CREATED                                                                                              => 'gray',
                    StockTransferOrder::STATUS_APPROVED                                                                                             => 'success',
                    StockTransferOrder::STATUS_REJECTED                                                                                             => 'danger',
                    default                                                                                                                         => 'secondary',
                }),
                TextColumn::make('created_at')->dateTime()->sortable()->searchable()->alignCenter(true)->toggleable(),
                TextColumn::make('details_count')->alignCenter(true)->toggleable(),
                TextColumn::make('creator.name')->alignCenter(false)->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->visible(fn($record): bool => $record->status === StockTransferOrder::STATUS_CREATED),

                Action::make('reject')->button()
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->schema([
                        Fieldset::make('Rejection Reason')
                            ->schema([
                                Textarea::make('rejected_reason')
                                    ->label('Reason')
                                    ->required()
                                    ->rows(4)->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn($record) => $record->status === StockTransferOrder::STATUS_CREATED)
                    ->action(function ($data, $record) {
                        try {
                            DB::beginTransaction();

                            $record->update([
                                'status'          => StockTransferOrder::STATUS_REJECTED,
                                'rejected_by'     => auth()->id(),
                                'rejected_reason' => $data['rejected_reason'],
                            ]);

                            DB::commit();
                            showSuccessNotifiMessage('Rejected successfully');
                        } catch (Throwable $e) {
                            DB::rollBack();
                            showWarningNotifiMessage('Reject failed', $e->getMessage());
                        }
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->color('success')->button()
                    ->icon('heroicon-o-check-circle')
                    // ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === StockTransferOrder::STATUS_CREATED)
                    ->action(function ($record) {
                        try {
                            DB::beginTransaction();
                            $record->update([
                                'status'      => StockTransferOrder::STATUS_APPROVED,
                                'approved_at' => now(),
                            ]);

                            DB::commit();
                            showSuccessNotifiMessage('Done');
                        } catch (Throwable $e) {
                            DB::rollBack();

                            showWarningNotifiMessage('Faild', $e->getMessage());
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
            'index'  => ListStockTransferOrders::route('/'),
            'create' => CreateStockTransferOrder::route('/create'),
            'edit'   => EditStockTransferOrder::route('/{record}/edit'),
            'view'   => ViewStockTransferOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return StockTransferOrder::query()
            
            // ->forBranchManager()
            ->count();
    }
}
