<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Throwable;
use Filament\Tables\Columns\TextColumn;
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
use Dom\Text;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
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
            ->paginated([10, 25, 50, 150])
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
            ])
            ->columns([

                SoftDeleteColumn::make(),
                TextColumn::make('id')->sortable()
                    ->label('ID')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.code')
                    ->label('Product Code'),
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('store.name')
                    ->label('Store'),
                TextColumn::make('movement_type_title')->alignCenter(true)
                    ->label('Movement Type')
                    ->sortable(),

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
                    ->hidden(),

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
                // Tables\Actions\EditAction::make(),

                ActionGroup::make([

                    Action::make('editQuantity')
                        ->visible(fn(): bool => auth()->user()->email === 'admin@admin.com')
                        ->schema([
                            TextInput::make('quantity')
                                ->required()
                                ->numeric()->default(fn($record): float => $record->quantity)
                                ->minValue(0.1),
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


                    // Tables\Actions\Action::make('editPackageSize')
                    //     ->visible(fn(): bool => auth()->user()->email == 'admin@admin.com')
                    //     ->form([
                    //         \Filament\Forms\Components\TextInput::make('package_size')->required(),
                    //     ])->action(function ($record, $data) {
                    //         $record->update([
                    //             'package_size' => $data['package_size'],
                    //         ]);
                    //         \Filament\Notifications\Notification::make()
                    //             ->title('Store Updated')
                    //             ->success()
                    //             ->body('Store updated successfully.')
                    //             ->send();
                    //     })
                    //     ->label('Edit Package Size')
                    //     ->color('warning')
                    //     ->icon('heroicon-m-pencil-square'),

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
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
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
}
