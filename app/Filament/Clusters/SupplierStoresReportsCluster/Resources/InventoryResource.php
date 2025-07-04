<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryResource\RelationManagers;
use App\Imports\InventoryTransactionsImport;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action as HeaderAction;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class InventoryResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = SupplierStoresReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 150])
            ->defaultSort('id', 'desc')
            ->headerActions([
                HeaderAction::make('import_inventory')->hidden()
                    ->label('Import Inventory Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
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
                            \Filament\Notifications\Notification::make()
                                ->title('Import Successful')
                                ->success()
                                ->body('Inventory records were imported successfully.')
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('Inventory import failed', ['error' => $e->getMessage()]);
                            \Filament\Notifications\Notification::make()
                                ->title('Import Failed')
                                ->danger()
                                ->body('Failed to import inventory: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->columns([

                Tables\Columns\TextColumn::make('id')->sortable()
                    ->label('ID')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Product Code'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product'),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store'),
                Tables\Columns\TextColumn::make('movement_type_title')->alignCenter(true)
                    ->label('Movement Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')->alignCenter(true)
                    ->formatStateUsing(fn($state) => formatQunantity($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_quantity')
                    ->label('Remaining Qty')->sortable()
                    ->formatStateUsing(fn($state) => formatQunantity($state))
                    // ->description('The remaining quantity of the product at the time this transaction was recorded')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit'),

                Tables\Columns\TextColumn::make('package_size')->alignCenter(true)
                    ->label('Package Size'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Movement Date')->date('Y-m-d')
                    ->sortable(),




                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes'),
                Tables\Columns\TextColumn::make('transactionable_id')
                    ->label('Transaction ID')->searchable(isIndividual: true)
                    ->sortable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('formatted_transactionable_type')
                    ->label('Transaction Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('sourceTransaction.formatted_transactionable_type')
                    ->label('Source Transaction Type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sourceTransaction.transactionable_id')
                    ->label('Source ID')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),


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
                SelectFilter::make('store_id')->options(fn() => \App\Models\Store::active()
                    ->get(['id', 'name'])
                    ->pluck('name', 'id')

                    ->toArray())
                    ->label(__('lang.store')),
                Tables\Filters\TrashedFilter::make(),

            ], FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),

                ActionGroup::make([

                    Tables\Actions\Action::make('editQuantity')
                        ->visible(fn(): bool => auth()->user()->email === 'admin@admin.com')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('quantity')
                                ->required()
                                ->numeric()->default(fn($record): float => $record->quantity)
                                ->minValue(0.1),
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'quantity' => $data['quantity'],
                            ]);

                            \Filament\Notifications\Notification::make()
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListInventories::route('/'),
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