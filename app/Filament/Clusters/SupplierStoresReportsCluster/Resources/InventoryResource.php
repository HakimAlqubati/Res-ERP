<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Tables\Columns\TextColumn;
use App\Models\Branch;
use App\Models\Store;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages\ListInventories;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\RelationManagers;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Imports\InventoryTransactionsImport;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Unit;
use App\Services\MultiProductsInventoryService;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class InventoryResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::RectangleStack;

    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 150,400])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Action::make('import_inventory')->hidden()
                    ->label('Import Inventory Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->required()
                            ->disk('public')
                            ->directory('inventory_imports'),
                    ])
                    ->color('success')
                    ->action(function (array $data) {
                        $path = 'public/' . $data['file'];
                        $import = new InventoryTransactionsImport();

                        try {
                            Excel::import($import, $path);
                            Notification::make()
                                ->title('Import Successful')
                                ->success()
                                ->body('Inventory records were imported successfully.')
                                ->send();
                        } catch (Throwable $e) {
                            Log::error('Inventory import failed', ['error' => $e->getMessage()]);
                            Notification::make()
                                ->title('Import Failed')
                                ->danger()
                                ->body('Failed to import inventory: ' . $e->getMessage())
                                ->send();
                        }
                    }),

                static::makeStockInNonManufacturingAction()
                // ->visible(fn()=>isHakimOrAdel())
                ->visible(fn()=>isSuperAdmin())
                ,

                static::getZeroDisabledProductsAction(),
            ])
            ->columns([

                SoftDeleteColumn::make()->toggleable(),
                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('id')->sortable()->searchable()
                    ->label('ID')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.code')
                    ->label('Product Code')->toggleable(),
                TextColumn::make('product.name')
                    ->label('Product')->toggleable(),
                TextColumn::make('store.name')
                    ->label('Store')->toggleable(),
                TextColumn::make('movement_type_title')->alignCenter(true)
                    ->label('Movement Type')
                    ->sortable()->toggleable(),

                TextColumn::make('quantity')
                    ->label('Qty')->alignCenter(true)
                    // ->formatStateUsing(fn($state) => formatQunantity($state))
                    ->sortable(),
                TextColumn::make('remaining_quantity')
                    ->label('Remaining Qty')->sortable()
                    ->formatStateUsing(fn($state) => formatQunantity($state))
                    // ->description('The remaining quantity of the product at the time this transaction was recorded')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('unit.name')
                    ->label('Unit'),

                TextColumn::make('package_size')->alignCenter(true)
                    ->label('Package Size'),
                TextColumn::make('price')
                    ->label('Price')->sortable()
                    ->summarize(Sum::make())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_price')
                    ->label('Total Price')->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->using(function (Table $table) {
                                $total  = $table->getRecords()->sum(fn($record) => $record->total_price);
                                if (is_numeric($total)) {
                                    return formatMoneyWithCurrency($total);
                                }
                                return $total;
                            })
                    )->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('movement_date')
                    ->label('Movement Date')->date('Y-m-d')
                    ->sortable(),




                TextColumn::make('notes')
                    ->label('Notes')->limit(50)->tooltip(fn($state) => $state),
                TextColumn::make('transactionable_id')
                    ->label('Transaction ID')->searchable(isIndividual: true)
                    ->sortable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('formatted_transactionable_type')
                    ->label('Transaction Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('sourceTransaction.formatted_transactionable_type')
                    ->label('Source Transaction Type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('sourceTransaction.transactionable_id')
                    ->label('Source ID')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('sourceTransaction.price')
                    ->label('Source Price')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                TextColumn::make('created_at'),


            ])
            ->filters([
                // Filter::make('product')
                //     ->label('Product')
                //     ->query(fn(Builder $query, array $data) => $query->whereHas('product', fn($q) => $q->where('name', 'like', "%{$data['value']}%")))
                //     ->form([
                //         Forms\Components\TextInput::make('value')->label('Product Name'),
                //     ]),

                SelectFilter::make('id')
                    ->label('ID')
                    ->searchable()
                    ->options(function () {
                        return InventoryTransaction::query()
                            ->orderBy('id', 'asc') // ترتيب تصاعدي
                            ->limit(10)
                            ->pluck('id', 'id')
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search): array {
                        return InventoryTransaction::query()
                            ->when(is_numeric($search), function ($query) use ($search) {
                                // أولًا جلب ID مطابق تمامًا
                                $query->where('id', $search);
                            }, function ($query) use ($search) {
                                // ثم تطابق جزئي فقط إن لم يكن رقماً دقيقاً
                                $query->where('id', 'like', "%$search%");
                            })
                            ->orWhere(function ($query) use ($search) {
                                // في حالة كان رقمًا جزئيًا لكن لا توجد نتيجة دقيقة
                                $query->where('id', 'like', "%$search%");
                            })
                            ->orderBy('id', 'asc')
                            ->limit(10)
                            ->pluck('id', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn($value) => "ID: $value")
                    // ->hidden()
                    ,

                SelectFilter::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        InventoryTransaction::MOVEMENT_IN => 'In',
                        InventoryTransaction::MOVEMENT_OUT => 'Out',
                    ]),
                SelectFilter::make('product.category_id')
                    ->label('Category')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))
                    ->multiple()
                    ->searchable()
                    ->options(fn() => Product::where('active', 1)
                        ->get()
                        ->mapWithKeys(fn($product) => [
                            $product->id => "{$product->code} - {$product->name}"
                        ])
                        ->toArray())
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
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        optional(Product::find($value))->code . ' - ' . optional(Product::find($value))->name
                    ),
                SelectFilter::make('store_id')->options(fn() => Store::active()
                    ->get(['id', 'name'])
                    ->pluck('name', 'id')

                    ->toArray())->searchable()
                    ->label(__('lang.store')),

                SelectFilter::make('transactionable_type')
                    ->label('Transaction Type')
                    ->options([
                        'App\Models\Order' => 'Order',
                        'App\Models\PurchaseInvoice' => 'Purchase Invoice',
                        'App\Models\StockAdjustmentDetail' => 'Stock Adjustment Detail',
                        'App\Models\StockIssueOrder' => 'Stock Issue Order',
                        'App\Models\StockOutReversal' => 'Stock Out Reversal',
                        'App\Models\ResellerSaleItem' => 'Reseller Sale Item',
                        'App\Models\PosSale' => 'Pos Sale',
                        'App\Models\GoodsReceivedNote' => 'Goods Received Note',
                    ])
                    ->searchable(),

                Filter::make('transactionable_id_filter')
                    ->form([
                        Forms\Components\TextInput::make('transactionable_id')
                            ->label('Transaction ID')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['transactionable_id'],
                            fn(Builder $query, $id): Builder => $query->where('transactionable_id', $id)
                        );
                    }),

                Filter::make('movement_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('From Date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('Until Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('movement_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('movement_date', '<=', $date),
                            );
                    })
                    ->label(__('Movement Date')),

                TrashedFilter::make(),

            ], FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->deferFilters(true)
            ->recordActions([
 
                Action::make('editTransaction')
                    ->label('Edit Package Size')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn()=>isHakimOrAdel() && 1>2)
                    ->action(function ($record, $data) {
                        $newPackageSize = $data['package_size'];
                        
                        $record->update([
                            'package_size' => $newPackageSize,
                            'temp_qty' => $data['temp_qty'],
                        ]);

                        // Check if it's from a StockAdjustmentDetail
                        if ($record->formatted_transactionable_type === 'StockAdjustmentDetail' || $record->transactionable_type === \App\Models\StockAdjustmentDetail::class) {
                            $adjDetail = \App\Models\StockAdjustmentDetail::find($record->transactionable_id);
                            
                            if ($adjDetail) {
                                $adjDetail->update(['package_size' => $newPackageSize]);

                                // Check if the adjustment is from a StockInventory
                                if (str_ends_with($adjDetail->source_type ?? '', 'StockInventory')) {
                                    \App\Models\StockInventoryDetail::where('stock_inventory_id', $adjDetail->source_id)
                                        ->where('product_id', $adjDetail->product_id)
                                        ->where('unit_id', $adjDetail->unit_id)
                                        ->update(['package_size' => $newPackageSize]);
                                }
                            }
                        }

                        Notification::make()
                            ->title('Package Size Updated')
                            ->success()
                            ->body('Package size updated successfully.')
                            ->send();
                    })
                     ->schema([
                        TextInput::make('package_size')
                            ->label('Package Size')
                            ->required()
                            ->numeric()->default(fn($record): float => $record->package_size ?? 0)
                            ->minValue(0),
                            TextInput::make('temp_qty')
                            ->label('Temp qty')
                            ->default(fn($record): float => $record->temp_qty ?? 0)->numeric()
                    ]),
                ActionGroup::make([

                    Action::make('editQuantity')
                      
                    ->visible(fn()=>isHakimOrAdel())
                        ->schema([
                            TextInput::make('quantity')
                                ->required()
                                ->numeric()->default(fn($record): float => $record->quantity)
                                // ->minValue(0.1)
                                ,
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'quantity' => $data['quantity'],
                            ]);

                            Notification::make()
                                ->title('Quantity Updated')
                                ->success()
                                ->body('Quantity updated successfully.')
                                ->send();
                        })
                        ->label('Edit Quantity')
                        ->color('warning')
                        ->icon('heroicon-m-pencil-square'),


                   

                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Stock-In action for non-manufacturing products
    // -------------------------------------------------------------------------

    /**
     * Returns a header Action that opens a modal asking the user to pick a store
     * linked to a non-manufacturing branch (i.e. any branch type that is NOT
     * central_kitchen), then bulk-creates MOVEMENT_IN transactions for every
     * active non-manufacturing product that has at least one unit price.
     */
    public static function makeStockInNonManufacturingAction(): Action
    {
        return Action::make('stock_in_non_manufacturing')
            ->label('Stock In – Non-Manufacturing')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->schema([
                Select::make('store_id')
                    ->label('Store')
                    ->required()
                    ->searchable()
                    ->options(static::getNonManufacturingStoreOptions()),
                TextInput::make('quantity')
                    ->label('Quantity per Product')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(100),
            ])
            ->action(function (array $data): void {
                static::createStockInForNonManufacturingProducts((int) $data['store_id'], (int) $data['quantity']);
            });
    }

    /**
     * Returns store options whose branches ARE of type central_kitchen (manufacturing).
     *
     * @return array<int, string>
     */
    public static function getNonManufacturingStoreOptions(): array
    {
        $manufacturingStoreIds = Branch::query()
            ->where('type', Branch::TYPE_CENTRAL_KITCHEN)
            ->whereNotNull('store_id')
            ->pluck('store_id')
            ->unique()
            ->values();

            if(isHakim()){
                $manufacturingStoreIds[] = 1;
            }
        return Store::active()
            ->whereIn('id', $manufacturingStoreIds)
            ->get(['id', 'name'])
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Creates MOVEMENT_IN inventory transactions for every active non-manufacturing
     * product that has at least one unit price, targeting the given store.
     *
     * @param int $storeId    The destination store ID.
     * @param int $quantity   The quantity to assign to each transaction.
     * @return void
     */
    public static function createStockInForNonManufacturingProducts(int $storeId, int $quantity = 100): void
    {
        $products = Product::query()
            ->active()
            ->unmanufacturingCategory()
            ->where('type', '!=', Product::TYPE_FINISHED_POS)   // exclude POS products
            ->with(['unitPrices' => fn ($q) => $q->forSupply()->orderBy('package_size', 'asc')])
            ->get();

        $createdCount = 0;

        DB::transaction(function () use ($products, $storeId, $quantity, &$createdCount) {
            foreach ($products as $product) {
                /** @var \App\Models\UnitPrice|null $unitPrice */
                $unitPrice = $product->unitPrices->first();

                if (! $unitPrice) {
                    continue;
                }

                InventoryTransaction::create([
                    'product_id'       => $product->id,
                    'movement_type'    => InventoryTransaction::MOVEMENT_IN,
                    'quantity'         => $quantity,
                    'unit_id'          => $unitPrice->unit_id,
                    'package_size'     => $unitPrice->package_size ?? 1,
                    'store_id'         => $storeId,
                    'price'            => $unitPrice->price ?? 0,
                    'movement_date'    => now(),
                    'transaction_date' => now(),
                    'notes'            => 'Initial stock-in – raw materials for manufacturing',
                ]);

                $createdCount++;
            }
        });

        Notification::make()
            ->title('Stock In Completed')
            ->body("{$createdCount} stock-in transactions created for non-manufacturing products.")
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventories::route('/'),
            // 'create' => Pages\CreateInventory::route('/create'),
            // 'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isFinanceManager() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin() || isHakimOrAdel()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin() || isHakimOrAdel()) {
            return true;
        }
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->with(['sourceTransaction']);
        return $query;
    }

    public static function getDefaultDisabledProductsItems(?int $storeId = 1): array
    {
        $targetCodes = ['09080', '10026', '10012', '19016', '9080'];
        Product::whereIn('code', $targetCodes)->update(['active' => 0]);

        $products = Product::whereIn('code', $targetCodes)
            ->with(['allUnitPrices.unit', 'supplyOutUnitPrices.unit', 'units'])
            ->get();

        $items = [];
        foreach ($products as $product) {
            // Get the smallest unit with package size 1 (or smallest package size)
            $unitPrice = $product->allUnitPrices->firstWhere('package_size', 1)
                ?? $product->supplyOutUnitPrices->firstWhere('package_size', 1)
                ?? $product->allUnitPrices->sortBy('package_size')->first()
                ?? $product->supplyOutUnitPrices->sortBy('package_size')->first();

            $unitId = $unitPrice?->unit_id ?? $product->main_unit_id;
            $unitName = $unitPrice?->unit?->name ?? Unit::find($unitId)?->name ?? 'الوحدة الأساسية';
            $packageSize = $unitPrice?->package_size ?? 1;
            $price = $unitPrice?->price ?? $product->basic_price ?? 0;

            $quantity = 0;
            if ($storeId && $unitId) {
                try {
                    $remaining = MultiProductsInventoryService::getRemainingQty(
                        (int) $product->id,
                        (int) $unitId,
                        (int) $storeId
                    );
                    $quantity = max(0, $remaining);
                } catch (\Throwable $e) {
                    $quantity = 0;
                }
            }

            $items[] = [
                'product_id'      => $product->id,
                'product_display' => "({$product->code}) {$product->name}",
                'unit_id'         => $unitId,
                'unit_name'       => $unitName,
                'package_size'    => $packageSize,
                'quantity'        => $quantity,
                'price'           => $price,
                'notes'           => 'تصفير المنتجات المعطلة',
            ];
        }

        return $items;
    }

    public static function getZeroDisabledProductsAction(): Action
    {
        return Action::make('zero_disabled_products')
            ->label('تصفير المنتجات المعطلة')
            ->icon('heroicon-o-minus-circle')
            ->visible(fn()=>isHakimOrAdel())
            ->color('danger')
            ->button()
            ->modalHeading('تصفير المنتجات المعطلة')
            ->modalDescription('إنشاء حركات مخزنية للمنتجات المعطلة (09080, 10026, 10012, 19016)')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel('تنفيذ وتأكيد الحركات')
            ->fillForm(function () {
                $storeId = 1;

                return [
                    'store_id'      => $storeId,
                    'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                    'movement_date' => now()->format('Y-m-d H:i:s'),
                    'notes'         => 'تصفير المنتجات المعطلة',
                    'items'         => self::getDefaultDisabledProductsItems($storeId),
                ];
            })
            ->schema([
                Grid::make(3)->schema([
                    Select::make('store_id')
                        ->label('المستودع')
                        ->options(fn () => Store::active()->pluck('name', 'id')->toArray())
                        ->default(1)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (! $state) {
                                return;
                            }
                            $currentItems = $get('items') ?? [];
                            if (empty($currentItems)) {
                                $currentItems = self::getDefaultDisabledProductsItems((int) $state);
                            } else {
                                foreach ($currentItems as &$item) {
                                    if (! empty($item['product_id']) && ! empty($item['unit_id'])) {
                                        try {
                                            $rem = MultiProductsInventoryService::getRemainingQty(
                                                (int) $item['product_id'],
                                                (int) $item['unit_id'],
                                                (int) $state
                                            );
                                            $item['quantity'] = max(0, $rem);
                                        } catch (\Throwable $e) {
                                            // Keep current quantity
                                        }
                                    }
                                }
                            }
                            $set('items', $currentItems);
                        }),

                    Select::make('movement_type')
                        ->label('نوع الحركة')
                        ->options([
                            InventoryTransaction::MOVEMENT_OUT => 'صرف / تصفير (Out)',
                            InventoryTransaction::MOVEMENT_IN  => 'توريد / إدخال (In)',
                        ])
                        ->default(InventoryTransaction::MOVEMENT_OUT)
                        ->required(),

                    DateTimePicker::make('movement_date')
                        ->label('تاريخ الحركة')
                        ->default(now())
                        ->required(),
                ]),

                TextInput::make('notes')
                    ->label('الملاحظات العامة')
                    ->default('تصفير المنتجات المعطلة')
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->label('المنتجات (09080, 10026, 10012, 19016)')
                    ->schema([
                        Hidden::make('product_id'),
                        TextInput::make('product_display')
                            ->label('المنتج')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->options(function ($get) {
                                $productId = $get('product_id');
                                if (! $productId) {
                                    return [];
                                }
                                $product = Product::with(['allUnitPrices.unit'])->find($productId);
                                if (! $product) {
                                    return [];
                                }
                                $opts = [];
                                foreach ($product->allUnitPrices as $up) {
                                    $opts[$up->unit_id] = ($up->unit?->name ?? 'Unit') . " (حجم: {$up->package_size})";
                                }
                                if (empty($opts) && $product->main_unit_id) {
                                    $u = Unit::find($product->main_unit_id);
                                    if ($u) {
                                        $opts[$u->id] = $u->name . ' (حجم: 1)';
                                    }
                                }
                                return $opts;
                            })
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('package_size')
                            ->label('حجم التعبئة')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('price')
                            ->label('السعر')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),

                        TextInput::make('notes')
                            ->label('ملاحظات البند')
                            ->default('تصفير المنتجات المعطلة')
                            ->columnSpanFull(),
                    ])
                    ->columns(6)
                    ->addable(false)
                    ->deletable(true)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data) {
                $storeId = $data['store_id'];
                $movementType = $data['movement_type'] ?? InventoryTransaction::MOVEMENT_OUT;
                $movementDate = $data['movement_date'] ?? now();
                $mainNotes = $data['notes'] ?? 'تصفير المنتجات المعطلة';
                $items = $data['items'] ?? [];

                $createdCount = 0;

                DB::transaction(function () use ($items, $storeId, $movementType, $movementDate, $mainNotes, &$createdCount) {
                    foreach ($items as $item) {
                        $quantity = (float) ($item['quantity'] ?? 0);
                        if ($quantity <= 0) {
                            continue;
                        }

                        InventoryTransaction::create([
                            'product_id'       => $item['product_id'],
                            'store_id'         => $storeId,
                            'unit_id'          => $item['unit_id'],
                            'quantity'         => $quantity,
                            'package_size'     => $item['package_size'] ?? 1,
                            'price'            => $item['price'] ?? 0,
                            'movement_type'    => $movementType,
                            'movement_date'    => $movementDate,
                            'transaction_date' => $movementDate,
                            'notes'            => ! empty($item['notes']) ? $item['notes'] : $mainNotes,
                        ]);

                        $createdCount++;
                    }

                    $targetCodes = ['09080', '10026', '10012', '19016', '9080'];
                    Product::whereIn('code', $targetCodes)->update(['active' => 0]);
                });

                if ($createdCount > 0) {
                    Notification::make()
                        ->title("✅ تم إنشاء {$createdCount} حركة مخزنية بنجاح.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('⚠️ لم يتم إنشاء حركات مخزنية. يرجى إدخال كمية أكبر من صفر.')
                        ->warning()
                        ->send();
                }
            });
    }
}
