<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Throwable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages\ListStockIssueOrders;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages\CreateStockIssueOrder;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages\EditStockIssueOrder;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages\ViewStockIssueOrder;
use App\Filament\Clusters\InventoryCluster;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\Pages;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockIssueOrderResource\RelationManagers;
use App\Models\Product;
use App\Models\StockIssueOrder;
use App\Models\Store;
use App\Models\UnitPrice;
use App\Models\User;
use App\Services\MultiProductsInventoryService;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class StockIssueOrderResource extends Resource
{
    protected static ?string $model = StockIssueOrder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 7;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->label('')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('order_date')
                            ->required()->default(now())
                            ->label('Order Date'),

                        Select::make('store_id')
                            // ->relationship('store', 'name')
                            ->options(
                                Store::active()
                                    ->withManagedStores()
                                    ->get(['name', 'id'])->pluck('name', 'id')
                            )
                            ->default(getDefaultStore())
                            ->required()
                            ->label('Store'),

                        DateTimePicker::make('created_at')
                            ->label('Created At')

                            ->visibleOn('view'),



                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),

                        Textarea::make('cancel_reason')
                            ->label('Cancel Reason')
                            ->hidden(fn($get) => $get('cancelled') == 0),

                        Repeater::make('details')
                            ->relationship('details')->columnSpanFull()
                            ->schema([
                                Select::make('product_id')
                                    ->required()->columnSpan(2)
                                    ->label('Product')->searchable()
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
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                                    ->reactive()
                                    ->afterStateUpdated(fn(callable $set) => $set('unit_id', null)),

                                Select::make('unit_id')->label('Unit')
                                    ->options(function (callable $get) {
                                        $product = Product::find($get('product_id'));
                                        if (! $product) return [];

                                        return $product?->outUnitPrices?->pluck('unit.name', 'unit_id') ?? [];
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, $state, $get) {
                                        $unitPrice = UnitPrice::where(
                                            'product_id',
                                            $get('product_id')
                                        ) 
                                            ->where('unit_id', $state)->first();
                                        if ($unitPrice) {

                                            $set('price', $unitPrice->price);

                                            $set('total_price', ((float) $unitPrice->price) * ((float) $get('quantity')));
                                            $set('package_size',  $unitPrice->package_size ?? 0);

                                            $service = new  MultiProductsInventoryService(null, $get('product_id'), $state, $get('../../store_id'));
                                            $remainingQty = $service->getInventoryForProduct($get('product_id'))[0]['remaining_qty'] ?? 0;
                                            $set('remaining_quantity', $remainingQty);
                                        }
                                    })->columnSpan(2)->required(),
                                TextInput::make('package_size')->type('number')->readOnly()->columnSpan(1)
                                    ->label(__('lang.package_size')),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.0001)
                                    ->label('Quantity')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($get, $set, $state) {
                                        $service = new  MultiProductsInventoryService(
                                            null,
                                            $get('product_id'),
                                            $get('unit_id'),
                                            $get('../../store_id')
                                        );
                                        $remainingQty = $service->getInventoryForProduct($get('product_id'))[0]['remaining_qty'] ?? 0;
                                        $set('remaining_quantity', $remainingQty);
                                    })
                                    ->rules([
                                        function ($get) {
                                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                                $remainingQty = (float) $get('remaining_quantity');
                                                if ($value > $remainingQty) {
                                                    $fail("Quantity cannot exceed remaining stock ({$remainingQty}).");
                                                }
                                            };
                                        },
                                    ]),
                                TextInput::make('remaining_quantity')
                                    ->numeric()
                                    ->readOnly()->visibleOn('create')
                                    ->label('Remaining Qty')



                            ])
                            ->minItems(1)
                            ->label('Issued Items')
                            ->columns(7),
                    ])
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->label('ID')->searchable(isIndividual: true)->alignCenter(true),
                TextColumn::make('order_date')->sortable()->label('Order Date'),
                TextColumn::make('store.name')->label('Store'),
                TextColumn::make('createdBy.name')->label('Created By'),
                TextColumn::make('item_count')->label('Products Count')->alignCenter(true),
                TextColumn::make('notes')->limit(50)->label('Notes'),
                IconColumn::make('cancelled')
                    ->label('Cancelled')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([

                    EditAction::make(),
                    ViewAction::make(),
                    Action::make('cancelAndReverse')
                        ->label('Cancel & Reverse')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->visible(fn(StockIssueOrder $record) => !$record->cancelled)
                        ->action(function (StockIssueOrder $record, array $data) {
                            try {
                                DB::transaction(function () use ($record, $data) {
                                    $record->cancelAndReverse($data['reason'] ?? 'Cancelled via Filament action');
                                });

                                showSuccessNotifiMessage(
                                    'Stock Issue Order cancelled and reversed successfully.'
                                );
                            } catch (Throwable $e) {
                                report($e);
                                showWarningNotifiMessage(
                                    'Failed to cancel and reverse the stock issue order: ' . $e->getMessage()
                                );
                            }
                        })
                        ->schema([
                            Textarea::make('reason')
                                ->label('Reason for cancellation')
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
            'index' => ListStockIssueOrders::route('/'),
            'create' => CreateStockIssueOrder::route('/create'),
            'edit' => EditStockIssueOrder::route('/{record}/edit'),
            'view' => ViewStockIssueOrder::route('/{record}'),

        ];
    }


    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListStockIssueOrders::class,
            CreateStockIssueOrder::class,
            EditStockIssueOrder::class,
            ViewStockIssueOrder::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
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

    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Red;
    }
}